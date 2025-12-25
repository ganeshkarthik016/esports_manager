<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 

if (!isset($_GET['id'])) { die("Tournament ID missing."); }

$t_id = intval($_GET['id']);
$player_id = isset($_SESSION['player_id']) ? $_SESSION['player_id'] : 0;

// Fetch Tournament Info
$sql = "SELECT T.*, G.game_name, G.platform, O.organizer_name 
         FROM Tournament T 
         JOIN Game G ON T.game_id = G.game_id 
         JOIN Organizer O ON T.organizer_id = O.organizer_id 
         WHERE T.tournament_id = $t_id";
$tournament = $conn->query($sql)->fetch_assoc();
if (!$tournament) { die("Tournament not found."); }
?>

<div style="max-width: 1200px; margin: 40px auto; padding: 20px;">
    
    <div style="background: linear-gradient(45deg, #1a1a24, #0f0f13); padding: 40px; border-radius: 10px; border-left: 5px solid #6c5ce7; margin-bottom: 30px; border: 1px solid #333;">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo htmlspecialchars($tournament['tournament_name']); ?></h1>
        <p style="color: #2ed573; font-weight: bold; font-size: 1.1rem;">Prize Pool: $<?php echo number_format($tournament['prize_pool']); ?></p>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 30px;">
        
        <div>
            <h2 style="border-left: 5px solid #2ed573; padding-left: 15px; margin-bottom: 20px;">Standings</h2>
            <div style="background: #1a1a24; padding: 20px; border-radius: 10px; border: 1px solid #333;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #333; text-align: left; color: #aaa;">
                            <th style="padding: 12px;">Rank</th>
                            <th style="padding: 12px;">Team</th>
                            <th style="padding: 12px; text-align: right;">Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_rank = "SELECT P.score, T.team_name FROM Participates P 
                                     JOIN Team T ON P.team_id = T.team_id 
                                     WHERE P.tournament_id = $t_id AND P.registration_status = 'Approved'
                                     ORDER BY P.score DESC";
                        $ranks = $conn->query($sql_rank);
                        
                        if ($ranks && $ranks->num_rows > 0) {
                            $count = 1;
                            while($r = $ranks->fetch_assoc()) {
                                $rank_text = "#" . $count;
                                if($count == 1) $rank_text = "🥇 1st";
                                elseif($count == 2) $rank_text = "🥈 2nd";
                                elseif($count == 3) $rank_text = "🥉 3rd";

                                echo "<tr style='border-bottom: 1px solid #333;'>
                                        <td style='padding: 15px; font-weight: bold;'>$rank_text</td>
                                        <td style='padding: 15px; font-weight: bold; color: #6c5ce7;'>".htmlspecialchars($r['team_name'])."</td>
                                        <td style='padding: 15px; text-align: right; color: #2ed573; font-weight: bold;'>".$r['score']." PTS</td>
                                      </tr>";
                                $count++;
                            }
                        } else { echo "<tr><td colspan='3' style='padding:40px; text-align:center; color: #888;'>No teams approved yet.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 style="border-left: 5px solid #ffa502; padding-left: 15px; margin-bottom: 20px;">Match History</h2>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php
                // Fetching matches with the new end_time column
                $sql_match = "SELECT M.match_id, M.match_date, M.match_time, M.end_time,
                                     GROUP_CONCAT(T.team_name SEPARATOR ' vs ') AS team_list,
                                     GROUP_CONCAT(CONCAT(T.team_name, ' (', MP.match_score, ')') ORDER BY MP.match_score DESC SEPARATOR ' vs ') AS score_summary
                              FROM Matches M 
                              LEFT JOIN Match_plays MP ON M.match_id = MP.match_id 
                              LEFT JOIN Team T ON MP.team_id = T.team_id
                              WHERE M.tournament_id = $t_id 
                              GROUP BY M.match_id
                              ORDER BY M.match_date DESC, M.match_time DESC";
                
                $matches = $conn->query($sql_match);
                $now = time();
                
                if ($matches && $matches->num_rows > 0) {
                    while($m = $matches->fetch_assoc()) {
                        // Logic for status based on Date, Time, and End Time
                        $start_ts = strtotime($m['match_date'] . ' ' . $m['match_time']);
                        $end_ts = strtotime($m['match_date'] . ' ' . $m['end_time']);
                        
                        if ($now < $start_ts) {
                            $status = "Scheduled";
                            $color = "#ffa502";
                            $display_summary = $m['team_list'] ? htmlspecialchars($m['team_list']) : "Teams TBD";
                        } elseif ($now >= $start_ts && $now <= $end_ts) {
                            $status = "Live";
                            $color = "#ff4757";
                            $display_summary = htmlspecialchars($m['team_list']) . " (In Progress)";
                        } else {
                            $status = "Completed";
                            $color = "#2ed573";
                            // Only show scores for completed matches
                            $display_summary = $m['score_summary'] ? htmlspecialchars($m['score_summary']) : htmlspecialchars($m['team_list']);
                        }
                        
                        echo "<div style='background: #1a1a24; padding: 15px; border-radius: 10px; border: 1px solid #333; border-left: 4px solid $color;'>
                                <div style='display:flex; justify-content:space-between; margin-bottom: 8px;'>
                                    <span style='font-size: 0.8rem; color: #888;'>".date('M d, H:i', $start_ts)." - ".date('H:i', $end_ts)."</span>
                                    <span style='color: $color; font-weight: bold; font-size: 0.8rem; text-transform: uppercase;'>$status</span>
                                </div>
                                <p style='font-weight: bold; margin: 0; color: #fff; line-height: 1.4;'>$display_summary</p>
                              </div>";
                    }
                } else { echo "<p style='color: #666; text-align: center; padding: 20px;'>No matches found.</p>"; }
                ?>
            </div>
        </div>

    </div>
</div>
