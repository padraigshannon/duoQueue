<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$db   = 'duoqueue_db';
$user = 'root';
$pass = '';

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
$limit = 1; 

$sql = '
 SELECT
        u.user_id,
        u.first_name,
        u.last_name,
        up.about_me,
        up.location,
        up.gender,
        up.seeking,
        up.date_of_birth,

        (
            COALESCE(games_score.pts, 0)
            + COALESCE(plat_score.pts, 0)
            + IF(up.gender = seeker.seeking AND seeker.gender = up.seeking, 20, 0)
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
        AND (up.gender = seeker.seeking OR seeker.seeking = \'any\')
        AND (seeker.gender = up.seeking OR up.seeking = \'any\')
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
    LIMIT :limit';

$stmt = $pdo->prepare("$sql");
$stmt->bindValue(':uid_seeker', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_games', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_platforms', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_exclude', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_likes', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_dislikes', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_matches1', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_matches2', $userId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$potentialMatch = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt->closeCursor();

if ($potentialMatch) {
    // Fetch profile photo for the potential match
    $stmt_profile = $pdo->prepare("SELECT profile_photo FROM user_profiles WHERE user_id = :user_id");
    $stmt_profile->execute(['user_id' => $potentialMatch['user_id']]);
    $profile_photo = $stmt_profile->fetchColumn();

    // Fetch games for the potential match
    $stmt_games = $pdo->prepare("SELECT ag.game_name FROM available_games ag JOIN users_games ug ON ag.game_id = ug.game_id WHERE ug.user_id = :user_id ORDER BY ag.game_name");
    $stmt_games->execute(['user_id' => $potentialMatch['user_id']]);
    $games = $stmt_games->fetchAll(PDO::FETCH_COLUMN);

    // Fetch platforms for the potential match
    $stmt_platforms = $pdo->prepare("SELECT ap.platform_name FROM available_platforms ap JOIN user_platforms up ON ap.platform_id = up.platform_id WHERE up.user_id = :user_id ORDER BY ap.platform_name");
    $stmt_platforms->execute(['user_id' => $potentialMatch['user_id']]);
    $platforms = $stmt_platforms->fetchAll(PDO::FETCH_COLUMN);

    // Fetch gallery photos
    $stmt_photos = $pdo->prepare("SELECT photo FROM user_photos WHERE user_id = :user_id LIMIT 5");
    $stmt_photos->execute(['user_id' => $potentialMatch['user_id']]);
    $photos = $stmt_photos->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Matchmake</title>

    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>

<body>

    <nav>
        <a href="home.php">Home</a>
        <a href="profilepage.php">Profile</a>
        <a href="matchmake.php">Matchmake</a>
        <a href="matches.php">My Duo's</a>
        <a href="aboutus.php">About Us</a>
        <a href="logout.php">Logout</a>
    </nav>

<div class="content">

<?php if(isset($_GET['matched'])): ?>
    <div class="match-notification">
        <h3>It's a Match with <?php echo htmlspecialchars($_GET['name']); ?>!</h3>
    </div>
<?php endif; ?>

<?php if ($potentialMatch): ?>

<div class="match-card">

    <h2>Player Found</h2>

    <p>Score: <?php echo $potentialMatch['match_score']; ?></p>

    <input type="text" value="<?php echo htmlspecialchars($potentialMatch['first_name']); ?>" readonly>
    <input type="text" value="<?php echo htmlspecialchars($potentialMatch['location']); ?>" readonly>

    <?php if (!empty($profile_photo)): ?>
    <div class="profile-picture">
        <h3>Profile Picture:</h3>
        <img src="../uploads/profile_photos/<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Picture" style="max-width: 200px; max-height: 200px;">
    </div>
    <?php endif; ?>

    <?php if (!empty($photos)): ?>
    <div class="gallery">
        <h3>Gallery Photos:</h3>
        <?php foreach ($photos as $photo): ?>
        <img src="../uploads/gallery_photos/<?php echo htmlspecialchars($photo); ?>" alt="Gallery Photo" style="max-width: 100px; max-height: 100px; margin: 5px;">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <textarea readonly><?php echo htmlspecialchars($potentialMatch['about_me']); ?></textarea>

    <?php if (!empty($games)): ?>
    <h3>Games:</h3>
    <ul>
        <?php foreach ($games as $game): ?>
        <li><?php echo htmlspecialchars($game); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if (!empty($platforms)): ?>
    <h3>Platforms:</h3>
    <ul>
        <?php foreach ($platforms as $platform): ?>
        <li><?php echo htmlspecialchars($platform); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <div class="match-actions">

        <!-- LIKE -->
        <form action="like.php" method="POST">
            <input type="hidden" name="liked_user_id" value="<?php echo $potentialMatch['user_id']; ?>">
            <button class="like-button">👍 Like</button>
        </form>

        <!-- DISLIKE -->
        <form action="dislike.php" method="POST">
            <input type="hidden" name="disliked_user_id" value="<?php echo $potentialMatch['user_id']; ?>">
            <button class="dislike-button">👎 Dislike</button>
        </form>

    </div>

</div>

<?php else: ?>

<div class="match-card">
    <h2>No Matches Available</h2>
</div>

<?php endif; ?>

</div>

</body>
</html>