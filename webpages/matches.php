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
    die("Could not connect to the database. Please try again later.");
}

if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}
$user_id = $_SESSION['user_id'];

$sql = "
    SELECT m.*,
       u1.first_name AS user1_first, u1.last_name AS user1_last,
       u2.first_name AS user2_first, u2.last_name AS user2_last
    FROM matches m
    JOIN users u1 ON m.user1_id = u1.user_id
    JOIN users u2 ON m.user2_id = u2.user_id
    WHERE m.user1_id = ? OR m.user2_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $user_id]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_match_id = $_GET['match_id'] ?? null;
$messages = [];

if ($selected_match_id) {
    $check = $pdo->prepare("
        SELECT * FROM matches 
        WHERE match_id = ? 
        AND (user1_id = ? OR user2_id = ?)
    ");
    $check->execute([$selected_match_id, $user_id, $user_id]);

    if ($check->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE match_id = ?
            ORDER BY created_timestamp ASC
        ");
        $stmt->execute([$selected_match_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $match_id = $_POST['match_id'];
    $message = $_POST['message'];

    $stmt = $pdo->prepare("SELECT user1_id, user2_id FROM matches WHERE match_id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    $receiver_id = ($match['user1_id'] == $user_id)
        ? $match['user2_id']
        : $match['user1_id'];

    
    $stmt = $pdo->prepare("
        INSERT INTO messages (match_id, message, sender_id, receiver_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$match_id, $message, $user_id, $receiver_id]);

    header("Location: matches.php?match_id=" . $match_id);
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>DuoQueue</title>
    <link rel="stylesheet" href="../assets/arcade.css">
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

        <div class="matches-container">

    <!-- Messaging Sidebar (Matched User Display) -->
            <div class="matches-sidebar">
                <?php foreach ($matches as $match):
                    $other_user_name = ($match['user1_id'] == $user_id) 
                        ? $match['user2_first'] . ' ' . $match['user2_last']
                        : $match['user1_first'] . ' ' . $match['user1_last'];
                ?>
                    <a href="matches.php?match_id=<?= $match['match_id'] ?>" class="match-user">
                        <?=$other_user_name ?>
                    </a>
                <?php endforeach; ?>
            </div>

    <!-- Messaging Screen -->
            <div class="chat-area">
                <div class="chat-header">
                    <img src="../assets/profile.png" class="profile-pic">
                    <span class="username">MatchedUser</span>

                    <div class="header-buttons">
                        <button>View Profile</button>
                        <button class="danger">Unmatch</button>
                    </div>
                </div>

                <div class="chat-messages">
                    <?php foreach ($messages as $message):
                        $class = ($message['sender_id'] == $user_id) ? "sent" : "received";
                    ?>
                        <div class = "message <?= $class ?>">
                            <?= htmlspecialchars($message['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-input">
                    <?php if ($selected_match_id): ?>
                        <form method="POST" style="display:flex; width:100%;">
                            <input type="hidden" name="match_id" value="<?= $selected_match_id ?>">
                            <input type="text" name="message" placeholder="Type message..." required>
                            <button type="submit">Send</button>
                    </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    

</body>
</html>