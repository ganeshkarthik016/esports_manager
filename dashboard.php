<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 

// 1. Security Check
if (!isset($_SESSION['player_id'])) {
    header("Location: login.php");
    exit();
}

$player_id = $_SESSION['player_id'];

// 2. Fetch Player Info
$sql_player = "SELECT * FROM Player WHERE player_id = $player_id";
$player = $conn->query($sql_player)->fetch_assoc();

// 3. Fetch ALL Teams
$my_teams = []; 
$team_ids = []; 

$sql_team = "SELECT T.team_id, T.team_name 
             FROM Team_Members TM 
             JOIN Team T ON TM.team_id = T.team_id 
             WHERE TM.player_id = $player_id";
$result_team = $conn->query($sql_team);

if ($result_team->num_rows > 0) {
    while($row = $result_team->fetch_assoc()) {
        $my_teams[] = $row;
        $team_ids[] = $row['team_id'];
    }
}

// 4. Fetch My Tournaments (Based on all teams)
$my_tournaments = [];
if (!empty($team_ids)) {
    // Convert array of IDs to comma separated string (e.g., "1, 5, 8")
    $ids_string = implode(',', $team_ids);
    
    $sql_tourney = "SELECT T.tournament_name, T.start_date, P.registration_status, P.score, Team.team_name
                    FROM Participates P 
                    JOIN Tournament T ON P.tournament_id = T.tournament_id 
                    JOIN Team ON P.team_id = Team.team_id
                    WHERE P.team_id IN ($ids_string)";
                    
    $result_tourney = $conn->query($sql_tourney);
    if($result_tourney) {
        while($row = $result_tourney->fetch_assoc()) {
            $my_tournaments[] = $row;
        }
    }
}
?>

<div style="max-width: 1200px; margin: 50px auto; padding: 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    
    <div style="background: #1a1a24; padding: 30px; border-radius: 10px; height: fit-content;">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="width: 100px; height: 100px; background: #6c5ce7; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold;">
                <?php echo strtoupper(substr($player['gamer_tag'], 0, 1)); ?>
            </div>
            <h2><?php echo htmlspecialchars($player['gamer_tag']); ?></h2>
            <p style="color: #aaa;"><?php echo htmlspecialchars($player['email']); ?></p>
        </div>
        
        <hr style="border: 0; border-top: 1px solid #333; margin: 20px 0;">
        
        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($player['player_name']); ?></p>
        <p><strong>Country:</strong> <?php echo htmlspecialchars($player['country']); ?></p>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($player['contact_number']); ?></p>
    </div>

    <div>
        <div style="background: #1a1a24; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
            <h2 style="border-left: 5px solid #6c5ce7; padding-left: 15px; margin-bottom: 20px;">My Teams</h2>
            
            <?php if (!empty($my_teams)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <?php foreach($my_teams as $team): ?>
                        <div style="background: #252530; padding: 15px; border-radius: 8px; border: 1px solid #333;">
                            <h3 style="margin: 0; font-size: 1.2rem; color: #fff;">
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </h3>
                            <span style="font-size: 0.8rem; color: #aaa;">Team ID: <?php echo $team['team_id']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #aaa;">You are not in any teams yet.</p>
            <?php endif; ?>
        </div>

        <div style="background: #1a1a24; padding: 30px; border-radius: 10px;">
            <h2 style="border-left: 5px solid #2ed573; padding-left: 15px; margin-bottom: 20px;">Registered Tournaments</h2>
            
            <?php if (!empty($my_tournaments)): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #333;">
                            <th style="padding: 10px;">Tournament</th>
                            <th style="padding: 10px;">Playing As</th>
                            <th style="padding: 10px;">Status</th>
                            <th style="padding: 10px;">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($my_tournaments as $t): ?>
                            <tr style="border-bottom: 1px solid #333;">
                                <td style="padding: 10px;">
                                    <?php echo htmlspecialchars($t['tournament_name']); ?>
                                    <br><small style="color: #aaa;"><?php echo date('M d', strtotime($t['start_date'])); ?></small>
                                </td>
                                <td style="padding: 10px; color: #74b9ff;">
                                    <?php echo htmlspecialchars($t['team_name']); ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php 
                                    $color = match($t['registration_status']) {
                                        'Approved' => '#2ed573',
                                        'Rejected' => '#ff4757',
                                        default => '#ffa502'
                                    };
                                    echo "<span style='color: $color'>" . $t['registration_status'] . "</span>";
                                    ?>
                                </td>
                                <td style="padding: 10px; font-weight: bold;"><?php echo $t['score']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #aaa;">Your teams haven't joined any tournaments yet.</p>
                <a href="tournaments.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #2ed573; color: #1a1a24; text-decoration: none; border-radius: 5px; font-weight: bold;">Browse Tournaments</a>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>