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

if (isset($_POST['delete_image'])) {

    $photo_name = $_POST['delete_image'];

    $file = "../uploads/gallery_photos/" . $photo_name;
    if (file_exists($file)) {
        unlink($file);
    }

    $stmt = $pdo->prepare("DELETE FROM user_photos WHERE user_id = ? AND photo = ?");
    $stmt->execute([$user_id, $photo_name]);
}

if (!empty($_FILES['gallery_images']['name'][0])) {

    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {

        $filename = time() . "_" . basename($_FILES['gallery_images']['name'][$key]);
        $target = "../uploads/gallery_photos/" . $filename;

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {

            if (move_uploaded_file($tmp_name, $target)) {

                $stmt = $pdo->prepare("
                    INSERT INTO user_photos (user_id, photo)
                    VALUES (?, ?)
                ");
                $stmt->execute([$user_id, $filename]);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_image'])) {

    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];

    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$first_name, $last_name, $email, $user_id]);

    if (!empty($_FILES['profile_pic']['name'])) {

        $filename = time() . "_" . basename($_FILES['profile_pic']['name']);
        $target = "../uploads/profile_photos/" . $filename;

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {

                $stmt = $pdo->prepare("
                    UPDATE user_profiles SET profile_photo = ? WHERE user_id = ?
                ");
                $stmt->execute([$filename, $user_id]);
            }
        }
    }
    $success = "User updated successfully!";
    }

$stmt = $pdo->prepare("SELECT u.first_name, u.last_name, u.email, up.profile_photo FROM users u JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$stmt = $pdo->prepare("SELECT photo FROM user_photos WHERE user_id = ?");
$stmt->execute([$user_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Edit User</title>
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
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        .gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .img-box {
            position: relative;
        }
        .img-box img {
            width: 120px;
            border-radius: 5px;
        }
        .delete-btn {
            position: absolute;
            top: 0;
            right: 0;
            background: red;
            color: white;
            border: none;
            cursor: pointer;
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
        <a href="viewProfile.php?user_id=<?= $user_id ?>" class="btn">← Back</a>
    </div>

    <h2>Edit User</h2>

    <?php if (!empty($success)): ?>
        <p style="color: lime;"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Profile Picture</label>
        <input type="file" name="profile_pic">

        <?php if (!empty($user['profile_photo'])): ?>
            <p>Current:</p>
            <img src="../uploads/profile_photos/<?= htmlspecialchars($user['profile_photo']) ?>" width="100">
        <?php endif; ?>

        <br><br>
        <button type="submit" class="btn">Save Changes</button>
    </form>

    <h3>User Gallery</h3>

    <div class="gallery">
        <?php foreach ($images as $img): ?>
            <div class="img-box">
                <img src="../uploads/gallery_photos/<?= htmlspecialchars($img['photo']) ?>">

                <form method="POST">
                    <input type="hidden" name="delete_image" value="<?= htmlspecialchars($img['photo']) ?>">
                    <button class="delete-btn">X</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <h4>Add New Images</h4>

    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="gallery_images[]" multiple>
        <button class="btn">Upload</button>
    </form>

</div>

</body>
</html>