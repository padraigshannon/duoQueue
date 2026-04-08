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

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database. Please try again later.");
}

$stmt = $pdo->prepare("
    SELECT 
        r.report_id, r.reason, r.created_timestamp,
        reported.user_id AS reported_user_id,
        reported.first_name AS reported_name,
        reporter.user_id AS reporter_user_id,
        reporter.first_name AS reporter_name
    FROM reports r
    JOIN users reported ON r.reported_user_id = reported.user_id
    JOIN users reporter ON r.reporting_user_id = reporter.user_id
    WHERE r.report_id = :report_id
");
$stmt->execute(['report_id' => $_GET['report_id']]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT m.message, m.sender_id, m.created_timestamp
    FROM messages m
    JOIN matches ma ON m.match_id = ma.match_id
    WHERE (ma.user1_id = :u1 AND ma.user2_id = :u2)
       OR (ma.user1_id = :u3 AND ma.user2_id = :u4)
    ORDER BY m.created_timestamp ASC
");
$stmt->execute([
    'u1' => $report['reported_user_id'],
    'u2' => $report['reporter_user_id'],
    'u3' => $report['reporter_user_id'],
    'u4' => $report['reported_user_id']
]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);


$reported = $report['reported_name'];
$reported_user_id = $report['reported_user_id'];
$reporter = $report['reporter_name'];
$reason = $report['reason'];
$date = $report['created_timestamp'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>DuoQueue</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>

<body>

    <div class="content">
        <div class="report-page">

            <div class="report-topbar">
                <a href="moderation.php" class="back-btn">&lt; Back</a>
                <span class="report-date"><?=$date ?></span>
            </div>

            <div class="report-container">

                <div class="report-header">
                    <h3 class="report-title">Report Form: <?=$reported ?></h3>
                    <div class="report-meta">
                        <span class="report-detail">Reported by: <span><?=$reporter ?></span></span>
                        <span class="report-detail">Reason: <span><?=$reason ?></span></span>
                    </div>
                </div>

                <div class="report-chatlog">
                    <span class="report-chatlog-title">Chatlogs</span>
                    <div class="report-messages">
                        <?php foreach ($messages as $message):
                            $class = ($message['sender_id'] == $user_id) ? "sent" : "received";
                        ?>
                            <div class = "message <?= $class ?>">
                                <?= htmlspecialchars($message['message']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="report-actions">
                    <button class="btn-remove">Remove Report</button>
                    <button class="btn-ban">Ban User</button>
                </div>

            </div>

        </div>


    </div>

</body>
</html>
