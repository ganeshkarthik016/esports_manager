<?php
// Note: Session is already started in db_connect.php which should be included before this
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esports Manager</title>
    <link rel="stylesheet" href="/esports_manager/css/style.css">
</head>
<body>
    <header>
        <div class="logo">Pro<span>Gamer</span></div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="tournaments.php">Tournaments</a></li>
                
                <?php if(isset($_SESSION['player_id'])): ?>
                    <!-- If user is logged in -->
                    <li><a href="dashboard.php">My Dashboard</a></li>
                    <li><a href="logout.php" class="btn">Logout</a></li>
                <?php else: ?>
                    <!-- If user is logged out -->
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>