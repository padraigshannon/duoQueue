<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$host = 'sql113.infinityfree.com';
$db   = 'if0_41396749_duoqueue_db';
$user = 'if0_41396749';
$pass = 'VQtMPg6j4SF2';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database. Please try again later.");
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName      = trim($_POST["first_name"]);
    $lastName       = trim($_POST["last_name"]);
    $email          = trim($_POST["email"]);
    $password       = $_POST["password"];
    $repeatPassword = $_POST["repeat_password"];

    if ($password !== $repeatPassword) {
        $error = "Passwords do not match!";
    } else {
        // Check if email is already registered
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "An account with that email already exists.";
        } else {
            try {
                // is_admin and is_banned default to false for every new user
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, is_admin, is_banned) VALUES (?, ?, ?, ?, ?, ?)");
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt->execute([$firstName, $lastName, $email, $hashedPassword, 0, 0]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                header("Location: profile.php");
                exit;
            } catch (PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="assets/main.css">
</head>
<body>
<nav>
    <a href="#">Home</a>
    <a href="#">Login</a>
</nav>
<div class="content">
    <div class="login-box">
        <h2>Register</h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color: lightgreen;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="repeat_password" placeholder="Repeat Password" required>
            <button type="submit">Create my account</button>
        </form>
    </div>
</div>
</body>
</html>