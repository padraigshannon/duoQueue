<!DOCTYPE html>
<html>

<head>
    <title>DuoQueue</title>
    <link rel="stylesheet" href="../assets/arcade.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>

<body>

        <nav>
            <a href="home.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="matchmake.php">Matchmake</a>
            <a href="matches.php">My Duo's</a>
            <a href="aboutus.php">About Us</a>
        </nav>

        <div class="matches-container">

    <!-- LEFT: MATCH LIST -->
    <div class="matches-sidebar">
        <div class="match-user">User 1</div>
        <div class="match-user">User 2</div>
        <div class="match-user">User 3</div>
    </div>

    <!-- RIGHT: CHAT -->
    <div class="chat-area">

        <!-- HEADER -->
        <div class="chat-header">
            <img src="../assets/profile.png" class="profile-pic">
            <span class="username">MatchedUser</span>

            <div class="header-buttons">
                <button>View Profile</button>
                <button class="danger">Unmatch</button>
            </div>
        </div>

        <!-- MESSAGES -->
        <div class="chat-messages">
            <p>Hello 👋</p>
            <p>Hey!</p>
        </div>

        <!-- INPUT -->
        <div class="chat-input">
            <input type="text" placeholder="Type message...">
            <button>Send</button>
        </div>

    </div>
</div>

    </div>

</body>
</html>