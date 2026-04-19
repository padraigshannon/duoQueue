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

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
$is_own_profile = ($user_id === $_SESSION['user_id']);

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

$sql_platforms = "SELECT ap.platform_name, up.platform_username
                  FROM user_platforms up
                  JOIN available_platforms ap ON up.platform_id = ap.platform_id
                  WHERE up.user_id = ?";

$stmt_platforms = $conn->prepare($sql_platforms);

if (!$stmt_platforms) {
    die("Prepare failed (platforms): " . $conn->error);
}

$stmt_platforms->bind_param("i", $user_id);
$stmt_platforms->execute();
$result_platforms = $stmt_platforms->get_result();

$platforms = [];
while ($row = $result_platforms->fetch_assoc()) {
    $platforms[] = $row;
}
$stmt_platforms->close();
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
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body.profile-page {
            justify-content: flex-start;
            overflow: hidden;
        }

        body.profile-page .content {
            height: 82vh;
            margin-top: 10vh;
            min-height: 82vh;
            padding-bottom: 25px;
            padding-top: 5px;
            justify-content: flex-start;
            align-items: stretch;
            overflow-y: auto;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        body.profile-page .content>.container {
            padding-top: 20px;
        }

        /* body.profile-page nav {
            top: 8vh;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px 16px;
            border-radius: 10px;
        } */

        body.profile-page nav {
            z-index: 100;
        }
    </style>
</head>

<body class="profile-page">



    <nav>
        <a href="home.php">Home</a>
        <a href="profilepage.php">Profile</a>
        <?php if ($is_own_profile): ?>
            <a href="profile.php">Edit Profile</a>
        <?php endif; ?>
        <a href="matchmake.php">Matchmake</a>
        <a href="matches.php">My Duo's</a>
        <a href="aboutus.php">About Us</a>
        <a href="logout.php">Logout</a>
    </nav>
    <div class="content">
        <div class="container py-4">
            <div class="row g-4">

                <div class="col-lg-4">

                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <?php if (!empty($profile['profile_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($profile['profile_photo']); ?>"
                                    alt="Profile Photo"
                                    class="img-fluid mb-3">
                            <?php else: ?>
                                <img src="assets/296fe121-5dfa-43f4-98b5-db50019738a7.jpg"
                                    alt="Default Profile Photo"
                                    class="img-fluid rounded-circle mb-3">
                            <?php endif; ?>
                        </div>
                    </div>

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

                <div class="col-lg-8">

                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="card-title">About Me</h3>
                            <p>
                                <?php echo nl2br(htmlspecialchars($profile['about_me'] ?? "No bio provided.")); ?>
                            </p>
                        </div>
                    </div>

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
                                <p>No games listed.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
            <div class="row g-4 mt-1">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="card-title">Photo Gallery</h3>

                            <?php if (!empty($pictures)): ?>
                                <div class="row g-3 mb-3">
                                    <?php foreach (array_slice($pictures, 0, 2) as $picture): ?>
                                        <div class="col-6">
                                            <img src="<?php echo htmlspecialchars($picture); ?>"
                                                alt="User Photo"
                                                class="img-fluid rounded">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (count($pictures) > 2): ?>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#photoGalleryModal">
                                        View All Photos
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>No photos uploaded.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="card-title">Platforms</h3>

                            <?php if (!empty($platforms)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($platforms as $platform): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo htmlspecialchars($platform['platform_name']); ?></strong>
                                            <?php if (!empty($platform['platform_username'])): ?>
                                                - <?php echo htmlspecialchars($platform['platform_username']); ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No platforms listed.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="photoGalleryModal" tabindex="-1" aria-labelledby="photoGalleryModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title" id="photoGalleryModalLabel">Photo Gallery</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <?php if (!empty($pictures)): ?>
                                    <div class="row g-3">
                                        <?php foreach ($pictures as $picture): ?>
                                            <div class="col-md-6">
                                                <img src="<?php echo htmlspecialchars($picture); ?>"
                                                    alt="User Photo"
                                                    class="img-fluid rounded">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p>No photos uploaded.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

</body>

</html>