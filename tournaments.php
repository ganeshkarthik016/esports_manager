<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 
?>

<div style="max-width: 1200px; margin: 50px auto; padding: 20px;">
    <h1 style="border-left: 5px solid #6c5ce7; padding-left: 15px; margin-bottom: 30px;">All Tournaments</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
        <?php
        $sql = "SELECT T.*, G.game_name, G.platform 
                FROM Tournament T 
                JOIN Game G ON T.game_id = G.game_id 
                ORDER BY T.start_date DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Determine Status Color
                $status_color = '#2ed573'; // Green for Upcoming
                $status_text = 'Upcoming';
                $now = date("Y-m-d H:i:s");
                
                if ($now > $row['start_date'] && $now < $row['end_date']) {
                    $status_text = 'Ongoing';
                    $status_color = '#ffa502'; // Orange
                } elseif ($now > $row['end_date']) {
                    $status_text = 'Completed';
                    $status_color = '#ff4757'; // Red
                }

                echo '
                <div style="background: #1a1a24; border-radius: 10px; overflow: hidden; transition: 0.3s; border: 1px solid #333;">
                    <div style="padding: 20px;">
                        <span style="background: '.$status_color.'; color: #000; padding: 3px 10px; border-radius: 10px; font-size: 0.8rem; font-weight: bold;">
                            '.$status_text.'
                        </span>
                        <h3 style="margin: 10px 0; font-size: 1.4rem;">'.htmlspecialchars($row['tournament_name']).'</h3>
                        <p style="color: #888; font-size: 0.9rem; margin-bottom: 5px;">
                            '.$row['game_name'].' ('.$row['platform'].')
                        </p>
                        <p style="color: #aaa; margin-bottom: 15px;">
                            Prize Pool: <span style="color: #2ed573; font-weight: bold;">$'.number_format($row['prize_pool']).'</span>
                        </p>
                        <a href="tournament_view.php?id='.$row['tournament_id'].'" class="btn" style="display: block; text-align: center;">View Details</a>
                    </div>
                </div>';
            }
        } else {
            echo "<p>No tournaments found.</p>";
        }
        ?>
    </div>
</div>

</body>
</html>