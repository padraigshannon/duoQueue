<?php
session_start();

$host = 'localhost';
$db   = 'duoqueue_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database. Please try again later.");
}

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: home.php");
    exit;
}

$results = [];
$query   = trim($_GET['query'] ?? '');

if (!empty($query)) {
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email 
                           FROM users 
                           WHERE CONCAT(first_name, ' ', last_name) LIKE ?");
    $stmt->execute(['%' . $query . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DuoQueue - User Search</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>
<body>
    <nav>
        <a href="adminHome.php">Home</a>
        <a href="manageGames.php">Games</a>
        <a href="managePlatforms.php">Platforms</a>
        <a href="moderation.php">Moderation</a>
        <a href="search.php">Search</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="login-box">
        <h2>User Search</h2>

        <form method="GET" action="search.php">
            <input type="text" name="query" placeholder="Search by name..."
                value="<?= htmlspecialchars($query) ?>">
            <button type="submit">Search</button>
        </form>

        <?php if (!empty($query)): ?>
            <?php if (empty($results)): ?>
                <p style="color: #00ffff; margin-top: 20px;">No users found for "<?= htmlspecialchars($query) ?>"</p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin-top: 20px;">
                    <?php foreach ($results as $result): ?>
                        <li style="margin: 10px 0;">
                            <a href="viewProfile.php?user_id=<?= $result['user_id'] ?>" class="link">
                                <?= htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>