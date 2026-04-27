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

$stmt = $pdo->query("SELECT game_id, game_name FROM available_games ORDER BY game_name");
$allGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT game_id FROM users_games WHERE user_id = ?");
$stmt->execute([$userId]);
$selectedGames = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT platform_id, platform_name FROM available_platforms ORDER BY platform_name");
$stmt->execute();
$allPlatforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT platform_id FROM user_platforms WHERE user_id = ?");
$stmt->execute([$userId]);
$selectedPlatforms = $stmt->fetchAll(PDO::FETCH_COLUMN);

$selectedGameNames = [];
foreach ($allGames as $game) {
    if (in_array($game['game_id'], $selectedGames, true)) {
        $selectedGameNames[] = $game['game_name'];
    }
}

$selectedPlatformNames = [];
foreach ($allPlatforms as $platform) {
    if (in_array($platform['platform_id'], $selectedPlatforms, true)) {
        $selectedPlatformNames[] = $platform['platform_name'];
    }
}

$stmt = $pdo->prepare("SELECT photo FROM user_photos WHERE user_id = ?");
$stmt->execute([$userId]);
$existingGalleryPhotos = $stmt->fetchAll(PDO::FETCH_COLUMN);


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['delete_gallery_photo'])) {
        $photoToDelete = $_POST['delete_gallery_photo'];

        $stmt = $pdo->prepare("SELECT photo FROM user_photos WHERE user_id = ? AND photo = ?");
        $stmt->execute([$userId, $photoToDelete]);
        $existingPhoto = $stmt->fetchColumn();

        if ($existingPhoto) {
            $stmt = $pdo->prepare("DELETE FROM user_photos WHERE user_id = ? AND photo = ?");
            $stmt->execute([$userId, $photoToDelete]);

            if (file_exists($existingPhoto)) {
                unlink($existingPhoto);
            }

            $success = "Gallery photo removed.";

            $stmt = $pdo->prepare("SELECT photo FROM user_photos WHERE user_id = ?");
            $stmt->execute([$userId]);
            $existingGalleryPhotos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $error = "Photo not found.";
        }
    }

    $location    = trim($_POST["location"] ?? '');
    $dateOfBirth = trim($_POST["date_of_birth"] ?? '');
    $gender      = trim($_POST["gender"] ?? '');
    $seeking     = trim($_POST["seeking"] ?? '');
    $aboutMe     = trim($_POST["about_me"] ?? '');
    $smoker      = isset($_POST["smoker"]) ? 1 : 0;
    $drinker     = isset($_POST["drinker"]) ? 1 : 0;
    $selectedGames = $_POST["games"] ?? [];
    $selectedPlatforms = $_POST["platforms"] ?? [];
    $profilePhoto = "";

    if (count($selectedGames) > 5) {
        $error = "You can select up to 5 games to add to your favourite games!.";
    }

    if (!empty($dateOfBirth)) {
        $dob = DateTime::createFromFormat('Y-m-d', $dateOfBirth);
        if (!$dob || $dob->format('Y-m-d') !== $dateOfBirth) {
            $error = "Invalid date format.";
        } else {
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($age < 18) {
                $error = "You must be at least 18 years old to use this service.";
            }
        }
    }

    // new profile, details required
    if ($isNewProfile && empty($error)) {
        if (empty($location))    $error = "Location is required.";
        elseif (empty($dateOfBirth)) $error = "Date of birth is required.";
        elseif (empty($gender))      $error = "Gender is required.";
        elseif (empty($seeking))     $error = "Seeking is required.";
        elseif (empty($aboutMe))     $error = "Bio is required.";
        elseif (empty($selectedGames))  $error = "Please select at least 1 favourite game.";
        elseif (empty($selectedPlatforms))  $error = "Please select at least 1 platform.";
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

    $galleryPhotos = [];
if (empty($error) && isset($_FILES['gallery_photos']) && !empty($_FILES['gallery_photos']['name'][0])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $uploadDir = 'uploads/gallery_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $totalExistingStmt = $pdo->prepare("SELECT COUNT(*) FROM user_photos WHERE user_id = ?");
    $totalExistingStmt->execute([$userId]);
    $existingCount = (int)$totalExistingStmt->fetchColumn();

    $newCount = count($_FILES['gallery_photos']['name']);
    $maxPhotos = 5;

    if (($existingCount + $newCount) > $maxPhotos) {
        $error = "You can have a maximum of 5 gallery photos. You currently have $existingCount.";
    } else {
        for ($i = 0; $i < count($_FILES['gallery_photos']['name']); $i++) {
            if ($_FILES['gallery_photos']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileType = $_FILES['gallery_photos']['type'][$i];
            $fileSize = $_FILES['gallery_photos']['size'][$i];
            $tmpName = $_FILES['gallery_photos']['tmp_name'][$i];
            $ogName = $_FILES['gallery_photos']['name'][$i];

            if (!in_array($fileType, $allowedTypes, true)) {
                $error = "Only image files (jpg, png, webp) are allowed for gallery photos.";
                break;
            }

            if ($fileSize > 2 * 1024 * 1024) {
                $error = "Each gallery photo must be under 2MB.";
                break;
            }

            $extension = pathinfo($ogName, PATHINFO_EXTENSION);
            $filename = $userId . '_' . time() . '_' . $i . '.' . $extension;
            $filePath = $uploadDir . $filename;

            if (move_uploaded_file($tmpName, $filePath)) {
                $galleryPhotos[] = $filePath;
            } else {
                $error = "Could not save a gallery photo.";
                break;
            }
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

        $stmt = $pdo->prepare("DELETE FROM users_games WHERE user_id = ?");
        $stmt->execute([$userId]);

        $stmt = $pdo->prepare("DELETE FROM user_platforms WHERE user_id = ?");
        $stmt->execute([$userId]);

        if (!empty($selectedGames)) {
            $stmt = $pdo->prepare("INSERT INTO users_games (user_id, game_id) VALUES (?, ?)");
            foreach ($selectedGames as $gameId) {
                $stmt->execute([$userId, $gameId]);
            }
        }

        if (!empty($selectedPlatforms)) {
            $stmt = $pdo->prepare("INSERT INTO user_platforms (user_id, platform_id, platform_username) VALUES (?, ?, ?)");
            foreach ($selectedPlatforms as $platformId) {
                $stmt->execute([$userId, $platformId, '']);
            }
        }

        if (!empty($galleryPhotos)) {
            $stmt = $pdo->prepare("INSERT INTO user_photos (user_id, photo) VALUES (?, ?)");
            foreach ($galleryPhotos as $photoPath) {
                $stmt->execute([$userId, $photoPath]);
            }
        }

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

        header("Location: profilepage.php?user_id=" . $userId);
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
        <a href="profilepage.php">Profile</a>
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
                <p id="previewGames">Favorite Games: <?= htmlspecialchars(!empty($selectedGameNames) ? implode(', ', $selectedGameNames) : '') ?></p>
                <p id="previewPlatforms">Platforms: <?= htmlspecialchars(!empty($selectedPlatformNames) ? implode(', ', $selectedPlatformNames) : '') ?></p>
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
                        <label>Photo Gallery:</label>
                        <input type="file" name="gallery_photos[]" accept="image/*" id="galleryInput" multiple>
                    </div>

                    <?php if (!empty($existingGalleryPhotos)): ?>
                        <div class="profile-form-group">
                            <label>Current Gallery Photos:</label>
                            <div style="display:flex; flex-wrap:wrap; gap:15px;">
                                <?php foreach ($existingGalleryPhotos as $galleryPhoto): ?>
                                    <div style="text-align:center;">
                                        <img src="<?= htmlspecialchars($galleryPhoto) ?>"
                                            alt="Gallery Photo"
                                            style="width:120px; height:120px; object-fit:cover; display:block; margin-bottom:8px;">

                                        <button type="submit"
                                            name="delete_gallery_photo"
                                            value="<?= htmlspecialchars($galleryPhoto) ?>"
                                            onclick="return confirm('Remove this photo from your gallery?');">
                                            Delete Photo
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="profile-form-group">
                        <input type="text" name="location" placeholder="Location" id="locationInput"

                            <div class="profile-form-group">
                        <input type="text" name="location" placeholder="Location" id="locationInput"
                            value="<?= htmlspecialchars($profile['location'] ?? '') ?>"
                            <?= $isNewProfile ? 'required' : '' ?>>
                    </div>

                    <div class="profile-form-group">
                        <label>Date of Birth:</label>
                        <?php $maxDob = date('Y-m-d', strtotime('-18 years')); ?>
                        <input type="date" name="date_of_birth"
                            max="<?= $maxDob ?>"
                            value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>"
                            <?= $isNewProfile ? 'required' : '' ?>>
                    </div>

                    <div class="profile-form-group">
                        <label>Gender:</label>
                        <select name="gender" id="genderInput" <?= $isNewProfile ? 'required' : '' ?>>
                            <option value="">Select Gender</option>
                            <option value="Male" <?= ($profile['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($profile['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($profile['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="profile-form-group">
                        <label>Seeking:</label>
                        <select name="seeking" id="seekingInput" <?= $isNewProfile ? 'required' : '' ?>>
                            <option value="">Select</option>
                            <option value="Male" <?= ($profile['seeking'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($profile['seeking'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($profile['seeking'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="profile-form-group">
                        <label>Favorite Games (Choose up to 5):</label>
                        <input type="text" id="gameSearch" placeholder="Search Games..." style="margin-bottom:10px; width:100%">
                        <div id="gamesList" style="max-height:220px; overflow-y:auto; border:1px solid #ccc; padding:10px; border-radius:6px;">
                            <?php foreach ($allGames as $game): ?>
                                <label class="game-option" style="display:grid; grid-template-columns: 1fr 24px; align-items:center; column-gap:12px; margin-bottom:8px;">
                                    <span><?= htmlspecialchars($game['game_name']) ?></span>
                                    <input type="checkbox" name="games[]" value="<?= htmlspecialchars($game['game_id']) ?>"
                                        <?= in_array($game['game_id'], $selectedGames) ? 'checked' : '' ?>>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="profile-form-group">
                        <label>Platforms:</label>
                        <input type="text" id="platformSearch" placeholder="Search Platforms..." style="margin-bottom:10px; width:100%">

                        <div id="platformsList" style="max-height:220px; overflow-y:auto; border:1px solid #ccc; padding:10px; border-radius:6px;">
                            <?php foreach ($allPlatforms as $platform): ?>
                                <label class="platform-option" style="display:grid; grid-template-columns: 1fr 24px; align-items:center; column-gap:12px; margin-bottom:8px;">
                                    <span><?= htmlspecialchars($platform['platform_name']) ?></span>
                                    <input type="checkbox" name="platforms[]" value="<?= htmlspecialchars($platform['platform_id']) ?>"
                                        <?= in_array($platform['platform_id'], $selectedPlatforms) ? 'checked' : '' ?>>
                                </label>
                            <?php endforeach; ?>
                        </div>
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

        const gameSearch = document.getElementById("gameSearch");
        const gameOptions = document.querySelectorAll("#gamesList .game-option");
        const gameCheckboxes = document.querySelectorAll('#gamesList input[type="checkbox"]');
        const previewGames = document.getElementById("previewGames");

        function updatePreviewGames() {
            const selected = Array.from(gameCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.parentElement.querySelector("span").textContent);

            previewGames.textContent = "Favorite Games: " + (selected.length ? selected.join(", ") : "");
        }

        gameSearch.addEventListener("input", function() {
            const search = this.value.toLowerCase();

            gameOptions.forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(search) ? "block" : "none";
            });
        });

        gameCheckboxes.forEach(checkbox => {
            checkbox.addEventListener("change", function() {
                const checkedCount = Array.from(gameCheckboxes).filter(cb => cb.checked).length;

                if (checkedCount > 5) {
                    this.checked = false;
                    alert("You can select up to 5 favourite games only.");
                }

                updatePreviewGames();
            });
        });
        updatePreviewGames();

        const platformSearch = document.getElementById("platformSearch");
        const platformOptions = document.querySelectorAll("#platformsList .platform-option");
        const platformCheckboxes = document.querySelectorAll('#platformsList input[type="checkbox"]');
        const previewPlatforms = document.getElementById("previewPlatforms");

        function updatePreviewPlatforms() {
            const selected = Array.from(platformCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.parentElement.querySelector("span").textContent);

            previewPlatforms.textContent = "Platforms: " + (selected.length ? selected.join(", ") : "");
        }

        platformSearch.addEventListener("input", function() {
            const search = this.value.toLowerCase();

            platformOptions.forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(search) ? "block" : "none";
            });
        });

        platformCheckboxes.forEach(checkbox => {
            checkbox.addEventListener("change", function() {
                updatePreviewPlatforms();
            });
        });

        updatePreviewPlatforms();
    </script>
</body>

</html>