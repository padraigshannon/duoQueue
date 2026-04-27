<?php
session_start();
$host = 'sql113.infinityfree.com';
$db   = 'if0_41396749_duoqueue_db';
$user = 'if0_41396749';
$pass = 'VQtMPg6j4SF2';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database. Please try again later.");
}

$gamesStmt = $pdo->query("SELECT game_id, game_name FROM available_games ORDER BY game_name");
$allGames  = $gamesStmt->fetchAll(PDO::FETCH_ASSOC);

$results       = [];
$query         = trim($_GET['query'] ?? '');
$selectedGames = array_map('intval', $_GET['filter_games'] ?? []);

if (!empty($query) || !empty($selectedGames)) {
    $params = [];
    $sql    = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email
               FROM users u";

    if (!empty($selectedGames)) {
        $sql .= " JOIN users_games ug ON u.user_id = ug.user_id";
    }

    $sql .= " WHERE 1=1";

    if (!empty($query)) {
        $sql             .= " AND CONCAT(u.first_name, ' ', u.last_name) LIKE :query";
        $params[':query'] = '%' . $query . '%';
    }

    if (!empty($selectedGames)) {
        $gameParams = [];
        foreach ($selectedGames as $i => $gameId) {
            $key          = ':game_' . $i;
            $gameParams[] = $key;
            $params[$key] = $gameId;
        }
        $sql .= " AND ug.game_id IN (" . implode(',', $gameParams) . ")";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DuoQueue - User Search</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .search-wrapper {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            justify-content: center;
            width: 90%;
            max-width: 1100px;
        }

        .search-filter-panel {
            width: 200px;
            border: 3px solid #00ffff;
            box-shadow: 0 0 8px #00ffff;
            padding: 15px;
            color: #ffffff;
            font-size: 10px;
            flex-shrink: 0;
        }

        .search-filter-panel h3 {
            margin-bottom: 15px;
            font-size: 10px;
        }

        .search-filter-panel label {
            display: block;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .search-filter-panel input[type="checkbox"] {
            margin-right: 8px;
        }

        .search-results {
            flex: 1;
        }
    </style>
</head>
<body>
    <nav>
    <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="adminHome.php">Home</a>
        <a href="moderation.php">Moderation</a>
        <a href="search.php">Search</a>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="home.php">Home</a>
        <a href="profilepage.php">Profile</a>
        <a href="matchmake.php">Matchmake</a>
        <a href="matches.php">My Duos</a>
        <a href="search.php">Search</a>
        <a href="aboutus.php">About Us</a>
        <a href="logout.php">Logout</a>
    <?php endif; ?>
</nav>

    <div class="content">
        <div class="search-wrapper">

            <!-- Game Filter Panel -->
            <div class="search-filter-panel">
                <h3>Filter by Game</h3>
                <?php if (empty($allGames)): ?>
                    <p>No games found.</p>
                <?php else: ?>
                    <input type="text" id="gameSearch" placeholder="Search Games..."
                           style="margin-bottom:10px; width:100%;" form="searchForm">

                    <div id="gamesList" style="max-height:220px; overflow-y:auto; border:1px solid #00ccff; padding:10px;">
                        <?php foreach ($allGames as $game): ?>
                            <label class="game-option" style="display:grid; grid-template-columns: 1fr 24px; align-items:center; column-gap:12px; margin-bottom:8px;">
                                <span><?= htmlspecialchars($game['game_name']) ?></span>
                                <input type="checkbox"
                                    name="filter_games[]"
                                    value="<?= $game['game_id'] ?>"
                                    form="searchForm"
                                    <?= in_array($game['game_id'], $selectedGames) ? 'checked' : '' ?>>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Search Box and Results -->
            <div class="search-results">
                <div class="login-box">
                    <h2>User Search</h2>

                    <form id="searchForm" method="GET" action="search.php">
                        <input type="text" name="query" placeholder="Search by name..."
                            value="<?= htmlspecialchars($query) ?>">
                        <button type="submit">Search</button>
                    </form>

                    <?php if (!empty($query) || !empty($selectedGames)): ?>
                        <?php if (empty($results)): ?>
                            <p style="color: #00ffff; margin-top: 20px;">No users found.</p>
                        <?php else: ?>
                            <ul style="list-style: none; padding: 0; margin-top: 20px;">
                                <?php foreach ($results as $result): ?>
                                    <li style="margin: 10px 0;">
                                        <a href="profilepage.php?user_id=<?= $result['user_id'] ?>" class="link">
                                            <?= htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        const gameSearch = document.getElementById("gameSearch");
        const gameOptions = document.querySelectorAll("#gamesList .game-option");

        gameSearch.addEventListener("input", function() {
            const search = this.value.toLowerCase();
            gameOptions.forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(search) ? "grid" : "none";
            });
        });
    </script>

</body>
</html>