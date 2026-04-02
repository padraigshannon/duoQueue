<!DOCTYPE html>
<html>
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$host = 'localhost';
$db   = 'duoqueue_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT 
            u.first_name,
            u.last_name,
            u.email,
            p.location,
            p.profile_photo,
            p.date_of_birth,
            p.gender,
            p.seeking,
            p.about_me,
            p.smoker,
            p.drinker
        FROM users u
        LEFT JOIN user_profiles p ON u.user_id = p.user_id
        WHERE u.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$profile = $result->fetch_assoc();

$stmt->close();

$games = [];

$sql_games = "SELECT ag.game_name
              FROM users_games ug
              JOIN available_games ag ON ug.game_id = ag.game_id
              WHERE ug.user_id = ?
              LIMIT 5";

$stmt_games = $conn->prepare($sql_games);

if (!$stmt_games) {
    die("Prepare failed (games): " . $conn->error);
}

$stmt_games->bind_param("i", $user_id);
$stmt_games->execute();
$result_games = $stmt_games->get_result();

$games = [];
while ($row = $result_games->fetch_assoc()) {
    $games[] = $row['game_name'];
}

$stmt_games->close();

$pictures = [];
$sql_pictures = "SELECT photo FROM user_photos WHERE user_id = ?";
$stmt_pictures = $conn->prepare($sql_pictures);
$stmt_pictures->bind_param("i", $user_id);
$stmt_pictures->execute();
$result_pictures = $stmt_pictures->get_result();

while ($row = $result_pictures->fetch_assoc()) {
    $pictures[] = $row['photo'];
}
$stmt_pictures->close();
$conn->close();

$age = "";
if (!empty($profile['date_of_birth'])) {
    $dob = new DateTime($profile['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
}
?>

<head>
    <title>DuoQueue</title>
    <link rel="stylesheet" href="assets/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>

    <div class="screen">

        <nav>
            <a href="home.php">Home</a>
            <a href="profilepage.php">Profile</a>
            <a href="matchmake.php">Matchmake</a>
            <a href="matches.php">My Duo's</a>
            <a href="aboutus.php">About Us</a>
        </nav>

        <div class="content container py-4">
    <div class="row g-4">

        <!-- Left column -->
        <div class="col-lg-4">

            <!-- Profile photo card -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <?php if (!empty($profile['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($profile['profile_photo']); ?>"
                             alt="Profile Photo"
                             class="img-fluid rounded-circle mb-3">
                    <?php else: ?>
                        <img src="assets/296fe121-5dfa-43f4-98b5-db50019738a7.jpg"
                             alt="Default Profile Photo"
                             class="img-fluid rounded-circle mb-3">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Basic info card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title">
                        <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                    </h2>

                    <p class="mb-1">
                        <strong>Age:</strong>
                        <?php echo htmlspecialchars($age !== "" ? $age : "Not specified"); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Location:</strong>
                        <?php echo htmlspecialchars($profile['location'] ?? "Not specified"); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Gender:</strong>
                        <?php echo htmlspecialchars($profile['gender'] ?? "Not specified"); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Seeking:</strong>
                        <?php echo htmlspecialchars($profile['seeking'] ?? "Not specified"); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Smoker:</strong>
                        <?php echo isset($profile['smoker']) ? ($profile['smoker'] ? "Yes" : "No") : "Not specified"; ?>
                    </p>
                    <p class="mb-1">
                        <strong>Drinker:</strong>
                        <?php echo isset($profile['drinker']) ? ($profile['drinker'] ? "Yes" : "No") : "Not specified"; ?>
                    </p>
                </div>
            </div>

        </div>

        <!-- Right column -->
        <div class="col-lg-8">

            <!-- Bio card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">About Me</h3>
                    <p>
                        <?php echo nl2br(htmlspecialchars($profile['about_me'] ?? "No bio provided.")); ?>
                    </p>
                </div>
            </div>

            <!-- Favorite games card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Favorite Games</h3>
                    <?php if (!empty($games)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($games as $game): ?>
                                <li class="list-group-item">
                                    <?php echo htmlspecialchars($game); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No favorite games listed.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>
</body>

</html>