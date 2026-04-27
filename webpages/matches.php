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
    u2.first_name AS user2_first, u2.last_name AS user2_last,
    p1.profile_photo AS user1_photo,
    p2.profile_photo AS user2_photo
    FROM matches m
    JOIN users u1 ON m.user1_id = u1.user_id
    JOIN users u2 ON m.user2_id = u2.user_id
    LEFT JOIN user_profiles p1 ON u1.user_id = p1.user_id
    LEFT JOIN user_profiles p2 ON u2.user_id = p2.user_id
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

$other_user_id = null;
$other_user_name = "No Match Selected";
$other_user_photo = null;


if ($selected_match_id) {
    foreach ($matches as $m) {
        if ($m['match_id'] == $selected_match_id) {
            if ($m['user1_id'] == $user_id) {
                $other_user_id = $m['user2_id'];
                $other_user_name = $m['user2_first'] . ' ' . $m['user2_last'];
                $other_user_photo = $m['user2_photo'];
            } else {
                $other_user_id = $m['user1_id'];
                $other_user_name = $m['user1_first'] . ' ' . $m['user1_last'];
                $other_user_photo = $m['user1_photo'];
            }
            break;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $match_id = $_POST['match_id'];
    $message = $_POST['message'];

    $normalized = preg_replace('/[\s\-()]/', '', $message);


    if (preg_match('/(\+?\d{1,3})?\d{9,}/', $normalized)) {
        $_SESSION['error'] = "Sharing phone numbers is not allowed.";
        header("Location: matches.php?match_id=" . $match_id);
        exit;
    }

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
$messageError = $_SESSION['error'] ?? "";
unset($_SESSION['error']);


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
        <a href="profilepage.php">Profile</a>
        <a href="matchmake.php">Matchmake</a>
        <a href="matches.php">My Duos</a>
        <a href="search.php">Search</a>
        <a href="aboutus.php">About Us</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="content">
        <div class="matches-container">

            <!-- Messaging Sidebar (Matched User Display) -->
            <div class="matches-sidebar">
                <?php foreach ($matches as $match):
                    $sidebar_name = ($match['user1_id'] == $user_id)
                        ? $match['user2_first'] . ' ' . $match['user2_last']
                        : $match['user1_first'] . ' ' . $match['user1_last'];
                ?>
                    <a href="matches.php?match_id=<?= $match['match_id'] ?>" class="match-user">
                        <?= $sidebar_name ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <!-- Messaging Screen -->
            <div class="chat-area">
                <div class="chat-header">
                    <img src="<?= $other_user_photo ? htmlspecialchars($other_user_photo) : '../assets/profile.png' ?>" class="profile-pic">
                    <span class="username"><?= htmlspecialchars($other_user_name) ?></span>

                    <div class="header-buttons">
                        <?php if ($selected_match_id && $other_user_id): ?>
                            <a href="profilepage.php?user_id=<?= $other_user_id ?>">
                                <button>View Profile</button>
                            </a>
                            <form action="unmatch.php" method="POST"
                                onsubmit="return confirm('Are you sure you want to unmatch?');"
                                style="display:inline;">
                                <input type="hidden" name="match_id" value="<?= $selected_match_id ?>">
                                <button type="submit" class="danger">Unmatch</button>
                            </form>
                            <a href="reportForm.php?user_id=<?= $other_user_id ?>">
                                <button class="danger">Report</button>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <p style="color:red; text-align:center; margin:10px;">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </p>
                <?php endif; ?>

                <div class="chat-messages">
                    <?php foreach ($messages as $message):
                        $class = ($message['sender_id'] == $user_id) ? "sent" : "received";
                    ?>
                        <div class="message <?= $class ?>">
                            <?= htmlspecialchars($message['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-input" style="display:flex; flex-direction:column; width:100%;">

                    <?php if (isset($_SESSION['error'])): ?>
                        <p style="color:red; margin:0 0 10px 0; text-align:center;">
                            <?php
                            echo htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']);
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($selected_match_id): ?>
                        <form method="POST" style="display:flex; width:100%;">
                            <input type="hidden" name="match_id" value="<?= htmlspecialchars($selected_match_id) ?>">
                            <input
                                type="text"
                                name="message"
                                class="<?= !empty($messageError) ? 'input-error' : '' ?>"
                                placeholder="<?= htmlspecialchars($messageError ?: 'Type message...') ?>"
                                required>

                            <button type="submit">Send</button>
                        </form>
                    <?php endif; ?>

                </div>




            </div>
        </div>
    </div>



</body>

</html>