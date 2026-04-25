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

// Handle Delete Game
if (isset($_GET['delete'])) {
    $game_id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM available_games WHERE game_id = :id");
    $stmt->execute(['id' => $game_id]);

    header("Location: manageGames.php");
    exit();
}

// Handle Add Game
if (isset($_POST['add_game'])) {
    $game_name = trim($_POST['game_name']);

    if (!empty($game_name)) {

        // Check if game already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM available_games WHERE game_name = :name");
        $stmt->execute(['name' => $game_name]);

        if ($stmt->fetchColumn() > 0) {
            $_SESSION['message'] = "Game already exists.";
        } else {
            // Insert new game
            $stmt = $pdo->prepare("INSERT INTO available_games (game_name) VALUES (:name)");
            $stmt->execute(['name' => $game_name]);

            $_SESSION['message'] = "Game added successfully!";
        }

    } else {
        $_SESSION['message'] = "Game name cannot be empty.";
    }

    header("Location: manageGames.php");
    exit();
}

// Fetch all games
$stmt = $pdo->query("SELECT * FROM available_games ORDER BY game_name ASC");
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Games</title>
    <link rel="stylesheet" href="../assets/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

</head>
<body>
 <nav>
        <a href="adminHome.php">Home</a>
        <a href="manageGames.php">Games</a>
        <a href="moderation.php">Moderation</a>
        <a href="search.php">Search</a>
        <a href="logout.php">Logout</a>
    </nav>
<h1>Manage Games</h1>

<!-- Add Game Form -->
<form method="POST">
    <input type="text" name="game_name" placeholder="Enter game name" required>
    <button type="submit" name="add_game">Add Game</button>
</form>

<?php if (isset($message)) echo "<p>$message</p>"; ?>

<h2>Available Games</h2>

<ul>
    <?php foreach ($games as $game): ?>
        <li>
            <?php echo htmlspecialchars($game['game_name']); ?>
            <a href="?delete=<?php echo $game['game_id']; ?>" 
               onclick="return confirm('Delete this game?');">
               Remove
            </a>
        </li>
    <?php endforeach; ?>
</ul>

</body>
</html>