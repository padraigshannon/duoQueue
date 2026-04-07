<?php
session_start();

// DB connection
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

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
   header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liked_user_id'])) {
    $likedUserId = (int)$_POST['liked_user_id'];

    // Don't allow liking yourself
    if ($likedUserId === $currentUserId) {
        header("Location: matchmake.php");
        exit();
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert like record
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, liked_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_timestamp = CURRENT_TIMESTAMP");
        $stmt->execute([$currentUserId, $likedUserId]);

        // Check if the liked user has already liked us back
        $stmt = $pdo->prepare("SELECT like_id FROM likes WHERE user_id = ? AND liked_user_id = ?");
        $stmt->execute([$likedUserId, $currentUserId]);
        $mutualLike = $stmt->fetch(PDO::FETCH_ASSOC);

        $isMatch = false;
        $matchedUserName = '';

        if ($mutualLike) {
            // Create match record
            $stmt = $pdo->prepare("INSERT INTO matches (user1_id, user2_id) VALUES (?, ?)");
            $stmt->execute([min($currentUserId, $likedUserId), max($currentUserId, $likedUserId)]);

            // Update both like records to MATCHED status
            $stmt = $pdo->prepare("UPDATE likes SET status = 'MATCHED' WHERE (user_id = ? AND liked_user_id = ?) OR (user_id = ? AND liked_user_id = ?)");
            $stmt->execute([$currentUserId, $likedUserId, $likedUserId, $currentUserId]);

            $isMatch = true;

            // Get the matched user's name
            $stmt = $pdo->prepare("SELECT first_name FROM users WHERE user_id = ?");
            $stmt->execute([$likedUserId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $matchedUserName = $user['first_name'];
        }

        $pdo->commit();

        // Redirect back to matchmake with match notification if applicable
        if ($isMatch) {
            header("Location: matchmake.php?matched=true&name=" . urlencode($matchedUserName));
        } else {
            header("Location: matchmake.php");
        }
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database error: " . $e->getMessage());
    }
} else {
    // Invalid request
    header("Location: matchmake.php");
    exit();
}
?>