<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $age = $_POST["age"];
    $location = $_POST["location"];
    $orientation = $_POST["orientation"];
    $bio = $_POST["bio"];

    $success = "Profile saved successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Setup</title>

<link rel="stylesheet" href="../assets/arcade.css">

<style>
/* Page-specific styles (keep layout) */
.main-container {
    position: relative;
    width: 90%;
    max-width: 1100px;
    height: 80vh;
    display: flex;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(15px);
    border-radius: 15px;
    overflow: hidden;
}

.preview {
    flex: 1;
    padding: 40px;
    background: rgba(0,0,0,0.7);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-right: 2px solid #00ffff;
}

.preview img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 20px;
    border: 3px solid #00ffff;
}

.form-section {
    flex: 1;
    padding: 40px;
    color: white;
}

.form-group {
    margin-bottom: 15px;
}

textarea {
    resize: none;
    height: 80px;
}
</style>

</head>

<body>

<div class="content">
<div class="main-container">

    <!-- Preview Panel -->
    <div class="preview">
        <img src="https://via.placeholder.com/150" id="profileImage">
        <h2 id="previewName">Your Name</h2>
        <p id="previewAge">Age</p>
        <p id="previewLocation">Location</p>
        <p id="previewOrientation">Orientation</p>
        <p id="previewBio">Your bio will appear here...</p>
    </div>

    <!-- Form Section -->
    <div class="form-section">
        <h2>Set Up Your Profile</h2>

        <?php if (!empty($success)): ?>
            <p style="color: lightgreen;"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="name" placeholder="Display Name" id="nameInput" required>
            </div>

            <div class="form-group">
                <input type="number" name="age" placeholder="Age" id="ageInput" required>
            </div>

            <div class="form-group">
                <input type="text" name="location" placeholder="Location" id="locationInput" required>
            </div>

            <div class="form-group">
                <select name="orientation" id="orientationInput" required>
                    <option disabled selected>Sexual Orientation</option>
                    <option>Heterosexual</option>
                    <option>Homosexual</option>
                    <option>Lesbian</option>
                    <option>Bisexual</option>
                    <option>Pansexual</option>
                    <option>Other</option>
                </select>
            </div>

            <div class="form-group">
                <textarea name="bio" placeholder="Write a short bio..." id="bioInput"></textarea>
            </div>

            <button type="submit">Save Profile</button>
        </form>
    </div>

</div>
</div>

<!-- Live Preview Script -->
<script>
document.getElementById("nameInput").oninput = e =>
    document.getElementById("previewName").textContent = e.target.value || "Your Name";

document.getElementById("ageInput").oninput = e =>
    document.getElementById("previewAge").textContent = e.target.value ? "Age: " + e.target.value : "Age";

document.getElementById("locationInput").oninput = e =>
    document.getElementById("previewLocation").textContent = e.target.value || "Location";

document.getElementById("orientationInput").onchange = e =>
    document.getElementById("previewOrientation").textContent = e.target.value || "Orientation";

document.getElementById("bioInput").oninput = e =>
    document.getElementById("previewBio").textContent = e.target.value || "Bio";
</script>

</body>
</html>
