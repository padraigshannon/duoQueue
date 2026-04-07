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
        <a href="adminHome.php">Home</a>
        <a href="moderation.php">Moderation</a>
        <a href="search.php">Search</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="content">
        <div class="report-page">

            <div class="report-topbar">
                <a href="moderation.php" class="back-btn">&lt; Back</a>
                <span class="report-date">2025-04-07 14:32</span>
            </div>

            <div class="report-container">

                <div class="report-header">
                    <h3 class="report-title">Report Form: xXSlayerXx</h3>
                    <div class="report-meta">
                        <span class="report-detail">Reported by: <span>NoobMaster69</span></span>
                        <span class="report-detail">Reason: <span>Toxic behavior</span></span>
                    </div>
                </div>

                <div class="report-chatlog">
                    <span class="report-chatlog-title">Chatlogs</span>
                    <div class="report-messages">

                        <div class="message received">
                            Hey want to play some ranked?
                        </div>
                        <div class="message sent">
                            You're trash, uninstall
                        </div>
                        <div class="message received">
                            That's not cool...
                        </div>

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
