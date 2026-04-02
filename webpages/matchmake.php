<?php
session_start();

// DB connection
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

    <style>
        /* MATCH CARD (REGISTER STYLE) */
        .match-card {
            width: 400px;
            margin: auto;
            padding: 20px;
            background-color: #111;
            border: 2px solid #0ff;
            border-radius: 10px;
            text-align: center;

            overflow: hidden;
        }

        .match-card label {
            display: block;
            margin-top: 10px;
            text-align: left;
        }

        .match-card input,
        .match-card textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            background-color: #222;
            color: #fff;
            border: 1px solid #0ff;
            border-radius: 5px;

            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .match-card textarea {
            min-height: 100px;
            resize: none;
        }

        .profile-photo {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-top: 10px;
        }

        .match-actions {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }

        .like-button {
            background-color: #0f0;
            border: none;
            padding: 10px;
            cursor: pointer;
        }

        .dislike-button {
            background-color: #f00;
            border: none;
            padding: 10px;
            cursor: pointer;
        }

        .match-notification {
            background-color: #222;
            border: 2px solid #0f0;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
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