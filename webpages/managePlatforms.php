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

// Handle Delete Platform
if (isset($_GET['delete'])) {
    $platform_id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM available_platforms WHERE platform_id = :id");
    $stmt->execute(['id' => $platform_id]);

    header("Location: managePlatforms.php");
    exit();
}

// Handle Add Platform
if (isset($_POST['add_platform'])) {
    $platform_name = trim($_POST['platform_name']);

    if (!empty($platform_name)) {

        // Check if platform already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM available_platforms WHERE platform_name = :name");
        $stmt->execute(['name' => $platform_name]);

        if ($stmt->fetchColumn() > 0) {
            $_SESSION['message'] = "Platform already exists.";
        } else {
            // Insert new platform
            $stmt = $pdo->prepare("INSERT INTO available_platforms (platform_name) VALUES (:name)");
            $stmt->execute(['name' => $platform_name]);

            $_SESSION['message'] = "Platform added successfully!";
        }

    } else {
        $_SESSION['message'] = "Platform name cannot be empty.";
    }

    header("Location: managePlatforms.php");
    exit();
}

// Fetch all platforms
$stmt = $pdo->query("SELECT * FROM available_platforms ORDER BY platform_name ASC");
$platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Platforms</title>
    <link rel="stylesheet" href="../assets/main.css">
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
<h1>Manage Platforms</h1>

<!-- Add Platform Form -->
<form method="POST">
    <input type="text" name="platform_name" placeholder="Enter platform name" required>
    <button type="submit" name="add_platform">Add Platform</button>
</form>

<?php if (isset($_SESSION['message'])) echo "<p>" . $_SESSION['message'] . "</p>"; unset($_SESSION['message']); ?>

<h2>Available Platforms</h2>

<ul>
    <?php foreach ($platforms as $platform): ?>
        <li>
            <?php echo htmlspecialchars($platform['platform_name']); ?>
            <a href="?delete=<?php echo $platform['platform_id']; ?>" 
               onclick="return confirm('Delete this platform?');">
               Remove
            </a>
        </li>
    <?php endforeach; ?>
</ul>

</body>
</html>