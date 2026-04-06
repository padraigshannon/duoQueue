<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Check login
if (!isset($_SESSION['user_id'])) {
   header("Location: login.php");
   exit();
}

$userId = $_SESSION['user_id'];

// CALL MATCHMAKING STORED PROCEDURE
$stmt = $pdo->prepare("CALL GetMatchmakingCandidates(:uid, 1)");
$stmt->execute(['uid' => $userId]);

$potentialMatch = $stmt->fetch(PDO::FETCH_ASSOC);

// IMPORTANT FIX
$stmt->closeCursor();
?>

<!DOCTYPE html>
<html>

<head>
    <title>DuoQueue - Matchmake</title>

    <link rel="stylesheet" href="../assets/arcade.css">
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

        .match-card {
            width: 400px;
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

        <!-- OPTIONAL: SHOW SCORE -->
        <p>Match Score: <?php echo $potentialMatch['match_score']; ?></p>

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
            <form action="like_action.php" method="POST">
                <input type="hidden" name="target_user_id" value="<?php echo $potentialMatch['user_id']; ?>">
                <button type="submit" name="action" value="like" class="like-button">👍 Like</button>
            </form>

            <form action="like_action.php" method="POST">
                <input type="hidden" name="target_user_id" value="<?php echo $potentialMatch['user_id']; ?>">
                <button type="submit" name="action" value="dislike" class="dislike-button">👎 Dislike</button>
            </form>
        </div>

    </div>

<?php else: ?>
    <div class="match-card">
        <h2>No More Potential Duo Partners</h2>
        <p>You've seen all available profiles. Check back later!</p>
    </div>
<?php endif; ?>

</div>

</body>
</html>