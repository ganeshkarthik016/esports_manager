<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 

if (!isset($_GET['id'])) {
    die("Tournament ID missing.");
}

$t_id = intval($_GET['id']);
$player_id = isset($_SESSION['player_id']) ? $_SESSION['player_id'] : 0;

// 1. Fetch Tournament Info
$sql = "SELECT T.*, G.game_name, G.platform, O.organizer_name 
         FROM Tournament T 
         JOIN Game G ON T.game_id = G.game_id 
         JOIN Organizer O ON T.organizer_id = O.organizer_id 
         WHERE T.tournament_id = $t_id";
$tournament = $conn->query($sql)->fetch_assoc();

if (!$tournament) {
    die("Tournament not found.");
}
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'registered'): ?>
    <script>
        alert("✅ SUCCESS!\n\nYour team has been created and registered successfully!");
        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState(null, '', url.toString());
        }
    </script>
    <div style="background: #2ed573; color: #1a1a24; text-align: center; padding: 15px; font-weight: bold; font-size: 1.1rem;">
        🎉 Team Registered Successfully! Good luck in the tournament.
    </div>
<?php endif; ?>

<div style="max-width: 1200px; margin: 40px auto; padding: 20px;">
    
    <div style="background: linear-gradient(45deg, #1a1a24, #0f0f13); padding: 40px; border-radius: 10px; border-left: 5px solid #6c5ce7; margin-bottom: 30px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo htmlspecialchars($tournament['tournament_name']); ?></h1>
        <p style="font-size: 1.2rem; color: #aaa;">
            <?php echo $tournament['game_name']; ?> • Organized by <?php echo $tournament['organizer_name']; ?>
        </p>
        <p style="margin-top: 10px; color: #2ed573; font-weight: bold; font-size: 1.1rem;">
            Prize Pool: $<?php echo number_format($tournament['prize_pool']); ?>
        </p>
        <p style="color: #aaa; margin-top: 5px;">
            Starts: <?php echo date('M d, Y', strtotime($tournament['start_date'])); ?>
        </p>
        
        <div style="margin-top: 20px;">
            <?php 
                // DATE CHECK LOGIC
                $current_date = date("Y-m-d");
                $start_date = $tournament['start_date'];
                $is_started = ($current_date >= $start_date);

                // CHECK REGISTRATION STATUS
                $already_in = false;
                if ($player_id > 0) {
                    $check_sql = "SELECT * FROM Participates P 
                                     JOIN Team_Members TM ON P.team_id = TM.team_id 
                                     WHERE TM.player_id = $player_id AND P.tournament_id = $t_id";
                    if($conn->query($check_sql)->num_rows > 0) {
                        $already_in = true;
                    }
                }
            ?>

            <?php if ($is_started): ?>
                <button class="btn" style="background: #333; color: #aaa; cursor: not-allowed; border: 1px solid #555;">
                    🚫 Registration Closed (Event Started)
                </button>

            <?php elseif ($player_id == 0): ?>
                <a href="login.php" class="btn btn-secondary">Login to Join</a>

            <?php elseif ($already_in): ?>
                <button class="btn" style="background: #2ed573; color: #1a1a24; cursor: default; font-weight: bold;">
                    ✅ You are Registered
                </button>

            <?php else: ?>
                <a href="register_team.php?id=<?php echo $t_id; ?>" class="btn" style="text-decoration: none; display: inline-block;">
                    Register / Create Team
                </a>
            <?php endif; ?>
            </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        
        <div>
            <h2 style="border-left: 5px solid #2ed573; padding-left: 15px; margin-bottom: 20px;">Standings</h2>
            <div style="background: #1a1a24; padding: 20px; border-radius: 10px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #333; text-align: left;">
                            <th style="padding: 10px;">Rank</th>
                            <th style="padding: 10px;">Team</th>
                            <th style="padding: 10px;">Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // (Standings code remains the same as it correctly calculates total scores)
                        $sql_rank = "SELECT P.score, T.team_name 
                                     FROM Participates P 
                                     JOIN Team T ON P.team_id = T.team_id 
                                     WHERE P.tournament_id = $t_id AND P.registration_status = 'Approved'
                                     ORDER BY P.score DESC";
                        $ranks = $conn->query($sql_rank);
                        
                        if ($ranks->num_rows > 0) {
                            $count = 1;
                            while($r = $ranks->fetch_assoc()) {
                                $badge = "";
                                if($count == 1) $badge = "🥇";
                                elseif($count == 2) $badge = "🥈";
                                elseif($count == 3) $badge = "🥉";

                                echo "<tr style='border-bottom: 1px solid #333;'>
                                        <td style='padding: 15px;'>#$count $badge</td>
                                        <td style='padding: 15px; font-weight: bold;'>".htmlspecialchars($r['team_name'])."</td>
                                        <td style='padding: 15px; color: #2ed573;'>".$r['score']." pts</td>
                                      </tr>";
                                $count++;
                            }
                        } else {
                            echo "<tr><td colspan='3' style='padding:20px; text-align:center; color: #aaa;'>No approved teams yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 style="border-left: 5px solid #ffa502; padding-left: 15px; margin-bottom: 20px;">Match Schedule</h2>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php
                // 2. Updated SQL Query to fetch match data, team names, and scores
                // GROUP_CONCAT is used to combine all team data for a single match into one row
                $sql_match = "SELECT M.match_id, M.match_date, M.match_time, M.status,
                                     GROUP_CONCAT(CONCAT(T.team_name, ' (', MP.match_score, ')') ORDER BY MP.match_score DESC SEPARATOR ' vs ') AS match_summary
                              FROM Matches M 
                              LEFT JOIN match_plays MP ON M.match_id = MP.match_id 
                              LEFT JOIN team T ON MP.team_id = T.team_id
                              WHERE M.tournament_id = $t_id 
                              GROUP BY M.match_id, M.match_date, M.match_time, M.status
                              ORDER BY M.match_date DESC";
                
                $matches = $conn->query($sql_match);
                
                if ($matches->num_rows > 0) {
                    while($m = $matches->fetch_assoc()) {
                        // Logic for status text and style
                        $status_text = htmlspecialchars($m['status']); // Use the status from the Matches table
                        $status_style = 'color: #aaa;';
                        if ($status_text === 'Completed') {
                            $status_style = 'color: #2ed573; font-weight: bold;';
                        } elseif ($status_text === 'Scheduled') {
                            // Check if scheduled match is today (LIVE status logic)
                            if ($m['match_date'] == date('Y-m-d')) {
                                $status_text = 'LIVE';
                                $status_style = 'color: #ff4757; font-weight: bold;';
                            } else {
                                $status_style = 'color: #ffa502; font-weight: bold;'; // Scheduled but not today
                            }
                        }
                        
                        $match_summary = $m['match_summary'] ? htmlspecialchars($m['match_summary']) : "TBD";
                        
                        echo "<div style='background: #1a1a24; padding: 15px; border-radius: 8px; border-left: 3px solid #6c5ce7;'>
                                <div style='display:flex; justify-content:space-between; margin-bottom: 5px;'>
                                    <span style='font-weight: bold;'>Match #".$m['match_id']."</span>
                                    <span style='$status_style'>$status_text</span>
                                </div>
                                <p style='color: #888; font-size: 0.9rem; margin-bottom: 5px;'>
                                    ".date('M d, Y', strtotime($m['match_date']))." @ ".date('H:i', strtotime($m['match_time']))."
                                </p>
                                <p style='font-weight: bold; color: #fff;'>
                                    ".$match_summary."
                                </p>
                              </div>";
                    }
                } else {
                    echo "<p style='color: #aaa;'>No matches scheduled yet.</p>";
                }
                ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>