<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'sql113.infinityfree.com';
$db   = 'if0_41396749_duoqueue_db';
$user = 'if0_41396749';
$pass = 'VQtMPg6j4SF2';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$userId = $_SESSION['user_id'];
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $location = trim($_POST["location"]);
    $dateOfBirth = trim($_POST["date_of_birth"]);
    $gender = trim($_POST["gender"]);
    $seeking = trim($_POST["seeking"]);
    $aboutMe = trim($_POST["about_me"]);
    $smoker = isset($_POST["smoker"]) ? 1 : 0;
    $drinker = isset($_POST["drinker"]) ? 1 : 0;
    $profilePhoto = "";

    try {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_Id, location, date_of_birth, gender, seeking, about_me, smoker, drinker, profile_photo) VALUES (?,?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $location, $dateOfBirth, $gender, $seeking, $aboutMe, $smoker, $drinker, $profilePhoto]);
        $success = "Profile edited successfully!";
    } catch (PDOException $e) {
        $error = "Profile update failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Setup</title>

    <link rel="stylesheet" href="../assets/arcade.css">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Press Start 2P', cursive;
        }

        .content {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* ✅ FIXED CONTAINER */
        .main-container {
            position: relative;
            width: 90%;
            max-width: 1100px;

            min-height: 80vh; /* FIX */
            height: auto;

            display: flex;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border-radius: 15px;

            overflow: hidden;
        }

        /* LEFT PREVIEW */
        .preview {
            flex: 1;
            padding: 40px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 2px solid #00ffff;

            overflow-y: auto; /* FIX */
            text-align: center;
        }

        .preview img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid #00ffff;
        }

        /* RIGHT FORM */
        .form-section {
            flex: 1;
            padding: 40px;
            color: white;

            overflow-y: auto; /* FIX */
            max-height: 80vh;
        }

        .form-group {
            margin-bottom: 15px;
        }

        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            background-color: #222;
            color: white;
            border: 1px solid #00ffff;
            border-radius: 5px;
        }

        textarea {
            resize: none;
            height: 80px;
        }

        button {
            margin-top: 10px;
            padding: 10px;
            background-color: #00ffff;
            border: none;
            cursor: pointer;
        }
    </style>

</head>

<body>

<div class="content">
    <div class="main-container">

        <!-- Preview -->
        <div class="preview">
            <img src="https://via.placeholder.com/150" id="profileImage">
            <h2 id="previewName">Your Name</h2>
            <p id="previewAge">Age</p>
            <p id="previewLocation">Location</p>
            <p id="previewOrientation">Orientation</p>
            <p id="previewBio">Your bio will appear here...</p>
        </div>

        <!-- Form -->
        <div class="form-section">
            <h2>Set Up Your Profile</h2>

            <?php if (!empty($success)): ?>
                <p style="color: lightgreen;"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="text" name="location" placeholder="Location" id="locationInput" required>
                </div>

                <div class="form-group">
                    <label>Date of Birth:</label>
                    <input type="date" name="date_of_birth" required>
                </div>

                <div class="form-group">
                    <label>Gender:</label>
                    <select name="gender" id="genderInput" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Seeking:</label>
                    <select name="seeking" id="seekingInput" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <textarea name="about_me" placeholder="Write a short bio..." id="bioInput" required></textarea>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="smoker"> Smoker</label>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="drinker"> Drinker</label>
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
    document.getElementById("previewName").textContent = e.target.value || "Your Profile";

document.getElementById("seekingInput").onchange = e =>
    document.getElementById("previewOrientation").textContent = "Seeking: " + (e.target.value || "Nobody selected");

document.getElementById("bioInput").oninput = e =>
    document.getElementById("previewBio").textContent = e.target.value || "Your bio will appear here...";
</script>

</body>
</html>