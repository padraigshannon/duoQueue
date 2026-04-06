<!DOCTYPE html>
<html>
    <?php
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit;
        }
    ?>

<head>
    <title>DuoQueue</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>

<body>

    <nav>
        <a href="home.php">Home</a>
        <a href="profilepage.php">Profile</a>
        <a href="matchmake.php">Matchmake</a>
        <a href="matches.php">My Duo's</a>
        <a href="aboutus.php">About Us</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="content">
            
    

    </div>

</body>
</html>