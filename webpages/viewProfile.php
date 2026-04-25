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
    die("Database connection failed.");
}

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: home.php");
    exit;
}

if (!isset($_GET['user_id'])) {
    die("No user specified.");
}

$user_id = intval($_GET['user_id']);

$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.email, up.profile_photo, up.location, up.about_me, up.date_of_birth, up.gender, up.seeking
    FROM users u
    JOIN user_profiles up ON u.user_id = up.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$stmt = $pdo->prepare("
    SELECT photo 
    FROM user_photos 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - View User</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .top-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 15px;
            background: #00ffff;
            color: black;
            text-decoration: none;
            border-radius: 5px;
            font-family: 'Press Start 2P', cursive;
        }
        .profile-pic {
            width: 120px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .gallery img {
            width: 120px;
            border-radius: 5px;
        }
    </style>
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

    <div class="top-bar">
        <a href="search.php" class="btn">← Back</a>

        <a href="editProfile.php?user_id=<?= $user_id ?>" class="btn">Edit Profile</a>
    </div>

    <h2>Admin View: User Profile</h2>

    <?php if (!empty($user['profile_photo'])): ?>
        <img src="../uploads/profile_photos/<?= htmlspecialchars($user['profile_photo']) ?>" class="profile-pic">
    <?php else: ?>
        <p>No profile picture</p>
    <?php endif; ?>

    <p><strong>First Name:</strong> <?= htmlspecialchars($user['first_name']) ?></p>
    <p><strong>Last Name:</strong> <?= htmlspecialchars($user['last_name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Location:</strong> <?= htmlspecialchars($user['location'] ?? 'Not set') ?></p>
    <p><strong>About Me:</strong> <?= htmlspecialchars($user['about_me'] ?? 'Not set') ?></p>
    <p><strong>Date of Birth:</strong> <?= htmlspecialchars($user['date_of_birth'] ?? 'Not set') ?></p>
    <p><strong>Gender:</strong> <?= htmlspecialchars($user['gender'] ?? 'Not set') ?></p>
    <p><strong>Seeking:</strong> <?= htmlspecialchars($user['seeking'] ?? 'Not set') ?></p>

    <h3>Gallery</h3>

    <?php if (count($images) > 0): ?>
        <div class="gallery">
            <?php foreach ($images as $img): ?>
                <img src="../uploads/gallery_photos/<?= htmlspecialchars($img['photo']) ?>">
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No gallery images</p>
    <?php endif; ?>

</div>

</body>
</html>