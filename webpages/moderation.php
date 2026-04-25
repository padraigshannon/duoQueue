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
    header("Location: login.php");
    exit;
}

$sql = "
    SELECT 
        r.report_id,
        r.reason,
        r.created_timestamp,
        reported.user_id AS reported_user_id,
        reported.first_name AS reported_name,
        reporter.first_name AS reporter_name
    FROM reports r
    JOIN users reported ON r.reported_user_id = reported.user_id
    JOIN users reporter ON r.reporting_user_id = reporter.user_id
    WHERE reported.is_banned = FALSE
    ORDER BY r.created_timestamp DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <a href="adminHome.php">Home</a>
        <a href="manageGames.php">Games</a>
        <a href="managePlatforms.php">Platforms</a>
        <a href="moderation.php">Moderation</a>
        <a href="search.php">Search</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="content">
        <div class="matches-container" style="grid-template-columns: 1fr;">
            <div class="report-short">
                <?php foreach ($reports as $report):
                    $reported_username = $report['reported_name'];
                    $reported_user_id = $report['reported_user_id'];
                    $reporting_user = $report['reporter_name'];
                    $reason = $report['reason'];
                    $date = $report['created_timestamp'];
                ?>
                    <div class="report-row">
                        <img src="../assets/profile.jpg" class="profile-pic">
                        <div class="report-info">
                            <span class="username"><?= htmlspecialchars($reported_username) ?></span>
                            <span class="report-detail">Reported by: <span><?= htmlspecialchars($reporting_user) ?></span></span>
                            <span class="report-detail">Reason: <span><?= htmlspecialchars($reason) ?></span></span>
                            <span class="report-detail"><?= htmlspecialchars($date) ?></span>
                        </div>
                        <div class="header-buttons">
                            <a href="report.php?report_id=<?= $report['report_id'] ?>"><button>View Report</button></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>         
        </div>
    </div>

</body>
</html>
