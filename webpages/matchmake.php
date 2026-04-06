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

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];

// Get one potential match
$stmt = $pdo->prepare("
    SELECT u.user_id, u.first_name, u.last_name,
           up.profile_photo, up.about_me, up.location
    FROM users u
    JOIN user_profiles up ON u.user_id = up.user_id
    WHERE u.user_id != :me
      AND u.is_banned = 0
      AND u.user_id NOT IN (
            SELECT liked_user_id FROM likes WHERE user_id = :me
      )
      AND u.user_id NOT IN (
            SELECT disliked_user_id FROM dislikes WHERE user_id = :me
      )
      AND u.user_id NOT IN (
            SELECT user2_id FROM matches WHERE user1_id = :me
            UNION
            SELECT user1_id FROM matches WHERE user2_id = :me
      )
    LIMIT 1
");

$stmt->execute(['me' => $userId]);
$potentialMatch = $stmt->fetch(PDO::FETCH_ASSOC);
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
        <a href="matches.php">My Duo's</a>
        <a href="aboutus.php">About Us</a>
        <a href="logout.php">Logout</a>
    </nav>

<div class="content">

<?php if(isset($_GET['matched']) && $_GET['matched'] == 'true'): ?>
    <div class="match-notification">
        <h2>🎉 It's a Match!</h2>
        <p>You and <?php echo htmlspecialchars($_GET['name']); ?> have liked each other!</p>
        <a href="matches.php">Go to My Matches</a>
    </div>
<?php endif; ?>

<?php if ($potentialMatch): ?>

    <div class="match-card">

        <h2>Player Profile</h2>

        <label>First Name</label>
        <input type="text" value="<?php echo htmlspecialchars($potentialMatch['first_name']); ?>" readonly>

        <label>Last Name</label>
        <input type="text" value="<?php echo htmlspecialchars($potentialMatch['last_name']); ?>" readonly>

        <label>Location</label>
        <input type="text" value="<?php echo htmlspecialchars($potentialMatch['location']); ?>" readonly>

        <label>About Me</label>
        <textarea readonly><?php echo htmlspecialchars($potentialMatch['about_me'] ?? 'No bio provided'); ?></textarea>

        <img src="<?php echo htmlspecialchars($potentialMatch['profile_photo']); ?>" 
             alt="Profile Photo" 
             class="profile-photo">

        <div class="match-actions">
            <form action="like.php" method="POST">
                <input type="hidden" name="liked_user_id" value="<?php echo $potentialMatch['user_id']; ?>">
                <button type="submit" class="like-button"> Like</button>
            </form>

            <form action="dislike.php" method="POST">
                <input type="hidden" name="disliked_user_id" value="<?php echo $potentialMatch['user_id']; ?>">
                <button type="submit" class="dislike-button"> Dislike</button>
            </form>
        </div>

    </div>

<?php else: ?>
    <div class="no-matches">
        <h2>No More Potential Duo Partners</h2>
        <p>You've seen all available profiles. Check back later!</p>
    </div>
<?php endif; ?>

</div>

</body>
</html>