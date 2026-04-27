<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'sql113.infinityfree.com';
$db   = 'if0_41396749_duoqueue_db';
$user = 'if0_41396749';
$pass = 'VQtMPg6j4SF2';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$limit  = 1;

// Fetch the logged in user's games for the filter panel
$gamesStmt = $pdo->prepare("
    SELECT ag.game_id, ag.game_name 
    FROM users_games ug
    JOIN available_games ag ON ug.game_id = ag.game_id
    WHERE ug.user_id = ?
");
$gamesStmt->execute([$userId]);
$userGames = $gamesStmt->fetchAll(PDO::FETCH_ASSOC);

// Read excluded game IDs from the form submission — default to empty array
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exclude_games'])) {
    $_SESSION['exclude_games'] = array_map('intval', $_POST['exclude_games']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liked_user_id'], $_POST['disliked_user_id'])) {
    // like/dislike submitted — keep existing session value, don't clear it
}

$excludedGameIds = $_SESSION['exclude_games'] ?? [];

// Build the games score subquery — if games are excluded, add a WHERE clause to filter them out
$excludeParams = [];
if (!empty($excludedGameIds)) {
    $paramNames = [];
    foreach ($excludedGameIds as $i => $gameId) {
        $paramNames[]           = ':excl_' . $i;
        $excludeParams[':excl_' . $i] = $gameId;
    }
    $gamesExclusion = "AND ug1.game_id NOT IN (" . implode(',', $paramNames) . ")";
} else {
    $gamesExclusion = "";
}

$sql = "
    SELECT
        u.user_id,
        u.first_name,
        u.last_name,
        up.profile_photo,
        up.about_me,
        up.location,
        up.gender,
        up.seeking,
        up.date_of_birth,

        (
            COALESCE(games_score.pts, 0)
            + COALESCE(plat_score.pts, 0)
            + IF(up.gender = seeker.seeking, 20, 0)
            + CASE
                WHEN ABS(YEAR(NOW()) - YEAR(up.date_of_birth)) <= 5 THEN 15
                WHEN ABS(YEAR(NOW()) - YEAR(up.date_of_birth)) <= 10 THEN 5
                ELSE 0
              END
            + IF(up.location = seeker.location, 5, 0)
        ) AS match_score

    FROM users u
    JOIN user_profiles up ON u.user_id = up.user_id

    JOIN (
        SELECT gender, seeking, location, date_of_birth
        FROM user_profiles
        WHERE user_id = :uid_seeker
    ) AS seeker ON 1=1

    LEFT JOIN (
        SELECT ug2.user_id, COUNT(*) * 10 AS pts
        FROM users_games ug1
        JOIN users_games ug2 ON ug1.game_id = ug2.game_id
        WHERE ug1.user_id = :uid_games
        $gamesExclusion
        GROUP BY ug2.user_id
    ) AS games_score ON games_score.user_id = u.user_id

    LEFT JOIN (
        SELECT up2.user_id, COUNT(*) * 5 AS pts
        FROM user_platforms up1
        JOIN user_platforms up2 ON up1.platform_id = up2.platform_id
        WHERE up1.user_id = :uid_platforms
        GROUP BY up2.user_id
    ) AS plat_score ON plat_score.user_id = u.user_id

    WHERE u.user_id <> :uid_exclude
        AND u.is_banned = 0
        AND u.user_id NOT IN (
            SELECT liked_user_id FROM likes WHERE user_id = :uid_likes
        )
        AND u.user_id NOT IN (
            SELECT disliked_user_id FROM dislikes WHERE user_id = :uid_dislikes
        )
        AND u.user_id NOT IN (
            SELECT user2_id FROM matches WHERE user1_id = :uid_matches1
            UNION
            SELECT user1_id FROM matches WHERE user2_id = :uid_matches2
        )

    ORDER BY match_score DESC
    LIMIT :limit";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid_seeker',    $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_games',     $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_platforms', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_exclude',   $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_likes',     $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_dislikes',  $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_matches1',  $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_matches2',  $userId, PDO::PARAM_INT);
$stmt->bindValue(':limit',         $limit,  PDO::PARAM_INT);

// Bind each excluded game ID individually
foreach ($excludeParams as $param => $value) {
    $stmt->bindValue($param, $value, PDO::PARAM_INT);
}

$stmt->execute();
$potentialMatch = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Matchmake</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .matchmake-wrapper {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            justify-content: center;
            width: 90%;
            max-width: 1100px;
        }

        .filter-panel {
            width: 200px;
            border: 3px solid #00ffff;
            box-shadow: 0 0 8px #00ffff;
            padding: 15px;
            color: #ffffff;
            font-size: 10px;
            flex-shrink: 0;
        }

        .filter-panel h3 {
            margin-bottom: 15px;
            font-size: 10px;
        }

        .filter-panel label {
            display: block;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .filter-panel input[type="checkbox"] {
            margin-right: 8px;
        }

        .filter-panel button {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 2px solid #ffffff;
            color: #ffffff;
            font-family: 'Press Start 2P', cursive;
            font-size: 10px;
            cursor: pointer;
        }

        .filter-panel button:hover {
            background: #00ccff;
            color: black;
        }
    </style>
</head>

<body>
    <nav>
        <a href="home.php">Home</a>
        <a href="profilepage.php">Profile</a>
        <a href="matchmake.php">Matchmake</a>
        <a href="matches.php">My Duos</a>
        <a href="search.php">Search</a>
        <a href="aboutus.php">About Us</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="content">

        <?php if (isset($_GET['matched'])): ?>
            <div class="match-notification">
                <h3>It's a Match with <?= htmlspecialchars($_GET['name']) ?>!</h3>
            </div>
        <?php endif; ?>

        <div class="matchmake-wrapper">

            <!-- Game Filter Panel -->
            <form method="POST" action="matchmake.php">
                <div class="filter-panel">
                    <h3>Exclude Games</h3>
                    <?php if (empty($userGames)): ?>
                        <p>No games on your profile yet.</p>
                    <?php else: ?>
                        <?php foreach ($userGames as $game): ?>
                            <label>
                                <input type="checkbox" name="exclude_games[]"
                                    value="<?= $game['game_id'] ?>"
                                    <?= in_array($game['game_id'], $excludedGameIds) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($game['game_name']) ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <button type="submit">Apply</button>
                </div>

                <!-- Pass like/dislike actions through the same form -->
                <?php if ($potentialMatch): ?>
                    <div class="match-card">
                        <h2>Match Found</h2>
                        <p>Score: <?= $potentialMatch['match_score'] ?></p>

                        <input type="text" value="<?= htmlspecialchars($potentialMatch['first_name']) ?>" readonly>
                        <input type="text" value="<?= htmlspecialchars($potentialMatch['location']) ?>" readonly>
                        <textarea readonly><?= htmlspecialchars($potentialMatch['about_me']) ?></textarea>

                        <div class="match-actions">
                            <form action="like.php" method="POST">
                                <input type="hidden" name="liked_user_id" value="<?= $potentialMatch['user_id'] ?>">
                                <button class="like-button">👍 Like</button>
                            </form>
                            <form action="dislike.php" method="POST">
                                <input type="hidden" name="disliked_user_id" value="<?= $potentialMatch['user_id'] ?>">
                                <button class="dislike-button">👎 Dislike</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="match-card">
                        <h2>No Matches Available</h2>
                    </div>
                <?php endif; ?>

            </form>

        </div>
    </div>
</body>
</html>