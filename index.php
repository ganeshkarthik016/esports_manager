<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 
?>

<section class="hero">
    <h1>Dominate the Arena</h1>
    <p>Join the ultimate Esports Tournament Management System. Create teams, register for tournaments, and climb the leaderboard.</p>
    
    <?php if(!isset($_SESSION['player_id'])): ?>
        <!-- Player Actions -->
        <div>
            <a href="register.php" class="btn">Join Now</a>
            <a href="tournaments.php" class="btn btn-secondary">View Tournaments</a>
        </div>
        
        <!-- NEW: Organizer Login Link (Added this section) -->
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; width: 100%; max-width: 300px;">
            <p style="color: #888; font-size: 0.9rem; margin-bottom: 5px;">Organizing a tournament?</p>
            <a href="org_login.php" style="color: #e056fd; font-weight: bold; text-decoration: none; border-bottom: 1px dashed #e056fd;">
                Login to Organizer Panel &rarr;
            </a>
        </div>

    <?php else: ?>
        <div>
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</section>

<!-- Featured Tournaments Section -->
<section style="padding: 50px 5%;">
    <h2 style="margin-bottom: 30px; border-left: 5px solid #6c5ce7; padding-left: 15px;">Upcoming Tournaments</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <?php
        // Fetch 3 upcoming tournaments
        $sql = "SELECT * FROM Tournament WHERE start_date > NOW() ORDER BY start_date ASC LIMIT 3";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<div style="background: #1a1a24; padding: 20px; border-radius: 10px;">';
                echo '<h3>' . htmlspecialchars($row['tournament_name']) . '</h3>';
                echo '<p style="color: #aaa; margin: 10px 0;">Prize Pool: $' . number_format($row['prize_pool']) . '</p>';
                echo '<p>Starts: ' . date('M d, Y', strtotime($row['start_date'])) . '</p>';
                echo '<a href="tournament_view.php?id=' . $row['tournament_id'] . '" class="btn btn-secondary" style="margin-top: 15px; display:block; text-align:center;">View Details</a>';
                echo '</div>';
            }
        } else {
            echo '<p>No upcoming tournaments at the moment.</p>';
        }
        ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>