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

// Call stored procedure to get matchmaking candidates
$stmt = $pdo->prepare("CALL GetMatchmakingCandidates(:uid, 1)");
$stmt->execute(['uid' => $userId]);

$potentialMatch = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt->closeCursor();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Matchmake</title>

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
        }

        input, textarea {
            width: 100%;
            margin-top: 5px;
            margin-bottom: 10px;
            background: #222;
            color: white;
            border: 1px solid #0ff;
        }

        textarea {
            height: 80px;
            resize: none;
        }

        .match-actions {
            display: flex;
            justify-content: space-between;
        }

        .like-button {
            background: green;
            padding: 10px;
            border: none;
            cursor: pointer;
        }

        .dislike-button {
            background: red;
            padding: 10px;
            border: none;
            cursor: pointer;
        }

        .match-notification {
            border: 2px solid lime;
            padding: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <nav>
        <a href="home.php">Home</a>
        <a href="profilepage.php">Profile</a>
        <a href="matchmake.php">Matchmake</a>
        <a href="matches.php">My Duo's</a>
        <a href="aboutus.php">About Us</a>
    </nav>

<div class="content">

<?php if(isset($_GET['matched'])): ?>
    <div class="match-notification">
        <h3>It's a Match with <?php echo htmlspecialchars($_GET['name']); ?>!</h3>
    </div>
<?php endif; ?>

<?php if ($potentialMatch): ?>

<div class="match-card">

    <h2>Match Found</h2>

    <p>Score: <?php echo $potentialMatch['match_score']; ?></p>

    <input type="text" value="<?php echo htmlspecialchars($potentialMatch['first_name']); ?>" readonly>
    <input type="text" value="<?php echo htmlspecialchars($potentialMatch['location']); ?>" readonly>

    <textarea readonly><?php echo htmlspecialchars($potentialMatch['about_me']); ?></textarea>

    <div class="match-actions">

        <!-- LIKE -->
        <form action="like.php" method="POST">
            <input type="hidden" name="liked_user_id" value="<?php echo $potentialMatch['user_id']; ?>">
            <button class="like-button">👍 Like</button>
        </form>

        <!-- DISLIKE -->
        <form action="dislike.php" method="POST">
            <input type="hidden" name="disliked_user_id" value="<?php echo $potentialMatch['user_id']; ?>">
            <button class="dislike-button">👎 Dislike</button>
        </form>

    </div>

</div>

<?php else: ?>

<div class="match-card">
    <h2>No Matches Available</h2>
</div>

<?php endif; ?>

</div>

</body>
</html>