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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/arcade-theme.css">
</head>

<body>

    <nav class="arcade-nav">
        <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
            <a href="adminHome.php" class="nav-link">Home</a>
            <a href="manageGames.php" class="nav-link">Games</a>
            <a href="managePlatforms.php" class="nav-link">Platforms</a>
            <a href="moderation.php" class="nav-link">Moderation</a>
            <a href="search.php" class="nav-link">Search</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="arcade-screen px-3">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">

                <div class="neon-box neon-box-lg p-4">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="viewProfile.php?user_id=<?= $user_id ?>" class="btn-arcade btn-arcade-cyan" style="font-size: 10px; padding: 8px 14px;">&lt; Back</a>
                        <h2 class="text-white mb-0" style="letter-spacing: 2px; font-size: clamp(12px, 1.5vw, 18px);">Edit User</h2>
                        <div style="width: 80px;"></div>
                    </div>

                    <?php if (!empty($success)): ?>
                        <p class="arcade-success text-center mb-3"><?= $success ?></p>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-glow mb-1" style="font-size: 10px;">First Name</label>
                                <input type="text" name="first_name" class="form-control arcade-input" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="text-glow mb-1" style="font-size: 10px;">Last Name</label>
                                <input type="text" name="last_name" class="form-control arcade-input" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="text-glow mb-1" style="font-size: 10px;">Email</label>
                                <input type="email" name="email" class="form-control arcade-input" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="text-glow mb-1" style="font-size: 10px;">Profile Picture</label>
                                <input type="file" name="profile_pic" class="form-control arcade-input">
                            </div>

                            <?php if (!empty($user['profile_photo'])): ?>
                                <div class="col-12">
                                    <p class="text-glow mb-1" style="font-size: 10px;">Current:</p>
                                    <img src="../uploads/profile_photos/<?= htmlspecialchars($user['profile_photo']) ?>"
                                        class="img-fluid" style="max-width: 120px; border-radius: 8px; border: 2px solid var(--cyan);">
                                </div>
                            <?php endif; ?>

                            <div class="col-12 mt-3">
                                <div class="d-grid">
                                    <button type="submit" class="btn-arcade">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <hr style="border-color: rgba(0, 255, 255, 0.2); margin: 25px 0;">

                    <h3 class="mb-3" style="font-size: clamp(10px, 1.2vw, 14px);">User Gallery</h3>

                    <?php if (!empty($images)): ?>
                        <div class="row g-3 mb-4">
                            <?php foreach ($images as $img): ?>
                                <div class="col-4 col-md-3">
                                    <div class="position-relative">
                                        <img src="../uploads/gallery_photos/<?= htmlspecialchars($img['photo']) ?>"
                                            class="img-fluid rounded" style="border: 2px solid var(--cyan);">
                                        <form method="POST" class="position-absolute top-0 end-0">
                                            <input type="hidden" name="delete_image" value="<?= htmlspecialchars($img['photo']) ?>">
                                            <button type="submit" class="btn-arcade-danger p-1" style="font-size: 8px; line-height: 1;"
                                                onclick="return confirm('Delete this photo?');">X</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="font-size: 10px; color: rgba(255,255,255,0.6);">No gallery photos.</p>
                    <?php endif; ?>

                    <h4 class="mb-3" style="font-size: clamp(9px, 1vw, 12px);">Add New Images</h4>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="d-flex flex-column flex-md-row gap-3 align-items-start">
                            <input type="file" name="gallery_images[]" multiple class="form-control arcade-input flex-fill">
                            <button type="submit" class="btn-arcade" style="font-size: 10px; white-space: nowrap;">Upload</button>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div>

</body>
</html>