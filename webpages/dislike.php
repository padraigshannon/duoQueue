<?php
session_start();

// DB connection
$host = 'localhost';
$db   = 'cs4116';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

//Check if user is logged in
if (!isset($_SESSION["user_id"])) {
   header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disliked_user_id'])) {
    $dislikedUserId = (int)$_POST['disliked_user_id'];

    // Don't allow disliking yourself
    if ($dislikedUserId === $currentUserId) {
        header("Location: matchmake.php");
        exit();
    }

    try {
        // Insert dislike record
        $stmt = $pdo->prepare("INSERT INTO dislikes (user_id, disliked_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_timestamp = CURRENT_TIMESTAMP");
        $stmt->execute([$currentUserId, $dislikedUserId]);

        // Redirect back to matchmake
        header("Location: matchmake.php");
        exit();

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    // Invalid request
    header("Location: matchmake.php");
    exit();
}
?>