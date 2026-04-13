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

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $found_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($found_user && password_verify($password, $found_user['password'])) {

        if ($found_user['is_banned']) {
            $stmt = $pdo->prepare("
                SELECT created_timestamp, ban_duration 
                FROM banned 
                WHERE user_id = ? AND deleted_timestamp IS NULL
            ");
            $stmt->execute([$found_user['user_id']]);
            $ban = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ban) {
                $ban_start = new DateTime($ban['created_timestamp']);
                $ban_end = clone $ban_start;
                $ban_end->modify('+' . $ban['ban_duration'] . ' days');
                $now = new DateTime();

                if ($now >= $ban_end) {
                    $stmt = $pdo->prepare("UPDATE banned SET deleted_timestamp = NOW() WHERE user_id = ? AND deleted_timestamp IS NULL");
                    $stmt->execute([$found_user['user_id']]);

                    $stmt = $pdo->prepare("UPDATE users SET is_banned = FALSE WHERE user_id = ?");
                    $stmt->execute([$found_user['user_id']]);
                } else {
                    // Still banned
                    $remaining = $now->diff($ban_end);
                    $error = "Your account is banned. " . $remaining->days . " day(s) remaining.";
                }
            }
        }

        if (empty($error)) {
            $_SESSION['user_id'] = $found_user['user_id'];
            $_SESSION['is_admin'] = $found_user['is_admin'];

            if ($found_user['is_admin']) {
                header("Location: adminHome.php");
            } else {
                header("Location: home.php");
            }
            exit;
        }

    } else {
        $error = "Invalid email or password.";
    }
}



?>
<!DOCTYPE html>
<html>
<head>
    <title>DuoQueue</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
            <a href="register.php" class="link">Sign up</a>
        </form>
    </div>
</body>
</html>