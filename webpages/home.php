<?php
    session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
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

    $uid = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE user1_id = ? OR user2_id = ?");
    $stmt->execute([$uid, $uid]);
    $matchCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
    $stmt->execute([$uid]);
    $likesSent = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$uid]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    $profileComplete = !empty($profile['about_me']) && !empty($profile['profile_photo']);
    ?>

<!DOCTYPE html>
<html>

<head>
    <title>DuoQueue</title>
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
        <div class="home-container">

            <h2 class="welcome-text">Welcome back, Player!</h2>

            <?php if (!$profileComplete): ?>
                <div class="home-alert">
                    Complete your profile to get more matches!
                    <a href="profile.php">Edit Profile</a>
                </div>
            <?php endif; ?>

            <div class="home-stats">
                <div class="stat-box">
                    <span class="stat-number"><?= $matchCount ?></span>
                    <span class="stat-label">Duo's</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= $likesSent ?></span>
                    <span class="stat-label">Likes Sent</span>
                </div>
            </div>

            <div class="home-actions">
                <a href="matchmake.php" class="home-btn">Start Matchmaking</a>
                <a href="matches.php" class="home-btn">My Duo's</a>
                <a href="profilepage.php" class="home-btn">View Profile</a>
            </div>

        </div>
    </div>

</body>
</html>