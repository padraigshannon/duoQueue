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
    exit;
}

$userId  = $_SESSION['user_id'];
$success = "";
$error   = "";

// Fetch existing profile data
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's name
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

$isNewProfile = empty($profile);

$currentPhoto = !empty($profile['profile_photo'])
    ? '/' . $profile['profile_photo']
    : 'https://via.placeholder.com/150';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $location    = trim($_POST["location"] ?? '');
	$dateOfBirth = trim($_POST["date_of_birth"] ?? '');
	$gender      = trim($_POST["gender"] ?? '');
	$seeking     = trim($_POST["seeking"] ?? '');
	$aboutMe     = trim($_POST["about_me"] ?? '');
    $smoker      = isset($_POST["smoker"]) ? 1 : 0;
    $drinker     = isset($_POST["drinker"]) ? 1 : 0;
    $profilePhoto = "";

    // new profile, details required
    if ($isNewProfile) {
        if (empty($location))    $error = "Location is required.";
        if (empty($dateOfBirth)) $error = "Date of birth is required.";
        if (empty($gender))      $error = "Gender is required.";
        if (empty($seeking))     $error = "Seeking is required.";
        if (empty($aboutMe))     $error = "Bio is required.";
    }

    // profile picture upload handling
    if (empty($error) && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            $error = "Only image files (jpg, png, gif, webp) are allowed.";
        }
        if (empty($error) && $file['size'] > 2 * 1024 * 1024) {
            $error = "Photo must be under 2MB.";
        }

        if (empty($error)) {
            $uploadDir = 'uploads/profile_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension    = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename     = $userId . '_' . time() . '.' . $extension;
            $profilePhoto = 'uploads/profile_photos/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                if (!empty($profile['profile_photo']) && file_exists($profile['profile_photo'])) {
                    unlink($profile['profile_photo']);
                }
            } else {
                $error = "Could not save the photo.";
            }
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, location, date_of_birth, gender, seeking, about_me, smoker, drinker, profile_photo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    location      = VALUES(location),
                    date_of_birth = VALUES(date_of_birth),
                    gender        = VALUES(gender),
                    seeking       = VALUES(seeking),
                    about_me      = VALUES(about_me),
                    smoker        = VALUES(smoker),
                    drinker       = VALUES(drinker),
                    profile_photo = IF(VALUES(profile_photo) = '', profile_photo, VALUES(profile_photo))");
            $stmt->execute([$userId, $location, $dateOfBirth, $gender, $seeking, $aboutMe, $smoker, $drinker, $profilePhoto]);

            $profile = [
                'location'      => $location,
                'date_of_birth' => $dateOfBirth,
                'gender'        => $gender,
                'seeking'       => $seeking,
                'about_me'      => $aboutMe,
                'smoker'        => $smoker,
                'drinker'       => $drinker,
                'profile_photo' => $profilePhoto ?: ($profile['profile_photo'] ?? '')
            ];

            $isNewProfile = false;
            $success = "Profile saved successfully!";

            if (!empty($profilePhoto)) {
                $currentPhoto = '/' . $profilePhoto;
            }

        } catch (PDOException $e) {
            $error = "Profile update failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isNewProfile ? 'Set Up Profile' : 'Edit Profile' ?></title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link rel="stylesheet" href="../assets/editProfile.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>

<body>
    
        <nav>
        <a href="home.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="matchmake.php">Matchmake</a>
        <a href="matches.php">My Duo's</a>
        <a href="aboutus.php">About Us</a>
    </nav>
    
    <div class="content">
        <div class="profile-main-container">

            <div class="profile-preview">
                <img src="<?= htmlspecialchars($currentPhoto) ?>" 
     				id="profileImage" 
     				style="width:150px; height:150px; object-fit:cover; border-radius:25%;">
                <h2><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?></h2>
                <p id="previewGender"><?= htmlspecialchars($profile['gender'] ?? 'Gender') ?></p>
                <p id="previewLocation"><?= htmlspecialchars($profile['location'] ?? 'Location') ?></p>
                <p id="previewOrientation">Seeking: <?= htmlspecialchars($profile['seeking'] ?? '') ?></p>
                <p id="previewBio"><?= htmlspecialchars($profile['about_me'] ?? 'Your bio will appear here...') ?></p>
            </div>

            <div class="profile-form-section">
                <h2><?= $isNewProfile ? 'Set Up Your Profile' : 'Edit Profile' ?></h2>

                <?php if ($isNewProfile): ?>
                    <p class="profile-notice">Welcome! Please fill in all fields to get started.</p>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <p style="color: lightgreen;"><?= htmlspecialchars($success) ?></p>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="profile-form-group">
                        <label>Profile Photo:</label>
                        <input type="file" name="profile_photo" accept="image/*" id="photoInput">
                    </div>

                    <div class="profile-form-group">
                        <input type="text" name="location" placeholder="Location" id="locationInput"
                            value="<?= htmlspecialchars($profile['location'] ?? '') ?>"
                            <?= $isNewProfile ? 'required' : '' ?>>
                    </div>

                    <div class="profile-form-group">
                        <label>Date of Birth:</label>
                        <input type="date" name="date_of_birth"
                            value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>"
                            <?= $isNewProfile ? 'required' : '' ?>>
                    </div>

                    <div class="profile-form-group">
                        <label>Gender:</label>
                        <select name="gender" id="genderInput" <?= $isNewProfile ? 'required' : '' ?>>
                            <option value="">Select Gender</option>
                            <option value="Male"   <?= ($profile['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($profile['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other"  <?= ($profile['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="profile-form-group">
                        <label>Seeking:</label>
                        <select name="seeking" id="seekingInput" <?= $isNewProfile ? 'required' : '' ?>>
                            <option value="">Select</option>
                            <option value="Male"   <?= ($profile['seeking'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($profile['seeking'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other"  <?= ($profile['seeking'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="profile-form-group">
                        <textarea name="about_me" placeholder="Write a short bio..." id="bioInput"
                            <?= $isNewProfile ? 'required' : '' ?>><?= htmlspecialchars($profile['about_me'] ?? '') ?></textarea>
                    </div>

                    <div class="profile-form-group">
                        <label><input type="checkbox" name="smoker" <?= !empty($profile['smoker']) ? 'checked' : '' ?>> Smoker</label>
                    </div>

                    <div class="profile-form-group">
                        <label><input type="checkbox" name="drinker" <?= !empty($profile['drinker']) ? 'checked' : '' ?>> Drinker</label>
                    </div>

                    <button type="submit">Save Profile</button>

                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("locationInput").oninput = e =>
            document.getElementById("previewLocation").textContent = e.target.value || "Location";

        document.getElementById("genderInput").onchange = e =>
            document.getElementById("previewGender").textContent = e.target.value || "Gender";

        document.getElementById("seekingInput").onchange = e =>
            document.getElementById("previewOrientation").textContent = "Seeking: " + (e.target.value || "");

        document.getElementById("bioInput").oninput = e =>
            document.getElementById("previewBio").textContent = e.target.value || "Your bio will appear here...";

        document.getElementById("photoInput").onchange = function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => document.getElementById("profileImage").src = e.target.result;
                reader.readAsDataURL(file);
            }
        };
    </script>

</body>
</html>