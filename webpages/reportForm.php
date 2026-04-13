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

$reporting_user_id = $_SESSION['user_id'];
$reported_user_id = $_GET['user_id'] ?? null;

if (!$reported_user_id || $reported_user_id == $reporting_user_id) {
    die("Invalid report target.");
}

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->execute([$reported_user_id]);
$reported_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reported_user) {
    die("User not found.");
}

$reported_name = htmlspecialchars($reported_user['first_name'] . ' ' . $reported_user['last_name']);
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    $posted_user_id = $_POST['reported_user_id'] ?? null;

    if (!$reason) {
        $error = "Please provide a reason for your report.";
    } elseif ($posted_user_id != $reported_user_id) {
        $error = "Invalid report submission.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO reports (reporting_user_id, reported_user_id, reason)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$reporting_user_id, $reported_user_id, $reason]);
        $success = true;
    }
}

$date = date('Y-m-d');
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
                <a href="matches.php" class="back-btn">&lt; Back</a>
                <span class="report-date"><?= $date ?></span>
            </div>

            <div class="report-container">

                <div class="report-header">
                    <h3 class="report-title">Report: <?= $reported_name ?></h3>
                </div>

                <?php if ($success): ?>
                    <p class="success-msg">Report submitted. Thank you.</p>
                <?php else: ?>

                    <?php if ($error): ?>
                        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>

                    <form method="POST" action="reportForm.php?user_id=<?= $reported_user_id ?>">
                        <input type="hidden" name="reported_user_id" value="<?= $reported_user_id ?>">

                        <label for="reason">Reason for report:</label>
                        <textarea name="reason" id="reason" required
                            placeholder="Describe the issue..."></textarea>

                        <div class="report-actions">
                            <button type="submit" class="btn-remove">Submit Report</button>
                        </div>
                    </form>

                <?php endif; ?>

            </div>

        </div>
    </div>


</body>
</html>
