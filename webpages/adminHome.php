<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: home.php");
    exit;
}


$host = 'localhost';
$db   = 'duoqueue_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed.");
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$totalBanned = $pdo->query("SELECT COUNT(*) FROM users WHERE is_banned = 1")->fetchColumn();

$totalGames = $pdo->query("SELECT COUNT(*) FROM available_games")->fetchColumn();

$totalReports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
?>

<!DOCTYPE html>
<html>

<head>
    <title>DuoQueue Admin</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>

<body>

<nav>
    <a href="adminHome.php">Home</a>
    <a href="manageGames.php">Games
    <a href="moderation.php">Moderation</a>
    <a href="search.php">Search</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="content">
    <div class="home-container">

        <h2 class="welcome-text">Welcome back, Admin!</h2>

        <div class="home-stats">

    <div class="stat-box">
        <span class="stat-number"><?= $totalUsers ?></span>
        <span class="stat-label">Users</span>
    </div>

    <div class="stat-box">
        <span class="stat-number"><?= $totalBanned ?></span>
        <span class="stat-label">Banned</span>
    </div>

    <div class="stat-box">
        <span class="stat-number"><?= $totalGames ?></span>
        <span class="stat-label">Games</span>
    </div>

    <div class="stat-box">
        <span class="stat-number"><?= $totalReports ?></span>
        <span class="stat-label">Reports</span>
    </div>

</div>


        <div class="home-actions">
            <a href="moderation.php" class="home-btn">Moderate Users</a>
            <a href="search.php" class="home-btn">Search Users</a>
            <a href="manageGames.php" class="home-btn">Manage Games</a>
        </div>

    </div>
</div>

</body>
</html>