<?php 
include __DIR__ . '/includes/db_connect.php'; 

$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
$t_id = isset($_GET['t_id']) ? intval($_GET['t_id']) : 0;

if ($team_id == 0 || $t_id == 0) {
    die("Invalid Team or Tournament ID.");
}

// 1. Fetch Tournament and Game Age Rule
$sql_info = "SELECT T.tournament_name, G.game_name, G.min_age 
             FROM tournament T 
             JOIN game G ON T.game_id = G.game_id 
             WHERE T.tournament_id = $t_id";
$info = $conn->query($sql_info)->fetch_assoc();

// 2. Fetch Team Members
// UPDATED: Using 'player_name' and 'age' from your screenshot
$sql_members = "SELECT P.player_id, P.player_name, P.age 
                FROM team_members TM 
                JOIN player P ON TM.player_id = P.player_id 
                WHERE TM.team_id = $team_id";
$members_res = $conn->query($sql_members);

if (!$members_res) {
    die("Query Error: " . $conn->error);
}

$team_eligible = true; 
$member_data = [];

while($m = $members_res->fetch_assoc()) {
    $p_id = $m['player_id'];
    
    // 3. Conflict Check: Is player in another team for this tournament?
    $conflict_sql = "SELECT T.team_name FROM team_members TM 
                     JOIN participates Part ON TM.team_id = Part.team_id 
                     JOIN team T ON TM.team_id = T.team_id
                     WHERE TM.player_id = $p_id AND Part.tournament_id = $t_id AND T.team_id != $team_id";
    $conflict_res = $conn->query($conflict_sql);
    $has_conflict = ($conflict_res->num_rows > 0);
    
    $age_ok = ($m['age'] >= $info['min_age']);
    
    if(!$age_ok || $has_conflict) {
        $team_eligible = false;
    }

    $member_data[] = [
        'player_name' => $m['player_name'],
        'age' => $m['age'],
        'age_ok' => $age_ok,
        'conflict' => $has_conflict
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Evaluate Team | ORGPANEL</title>
    <style>
        body { background: #0f0f13; color: white; font-family: sans-serif; padding: 40px; }
        .card { background: #1a1a24; padding: 30px; border-radius: 12px; max-width: 800px; margin: auto; border: 1px solid #333; }
        .member-item { background: #252530; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid #444; }
        .pass { border-left-color: #2ed573; }
        .fail { border-left-color: #ff4757; }
        .btn { padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-accept { background: #2ed573; color: #1a1a24; }
        .btn-disabled { background: #444; color: #888; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="card">
    <h1>Evaluate: <?php echo htmlspecialchars($info['tournament_name']); ?></h1>
    <p style="color: #ffa502;">Rule: <?php echo htmlspecialchars($info['game_name']); ?> (Min Age: <?php echo $info['min_age']; ?>+)</p>
    <hr style="border: 0; border-top: 1px solid #333; margin: 20px 0;">

    <?php foreach($member_data as $member): ?>
        <div class="member-item <?php echo ($member['age_ok'] && !$member['conflict']) ? 'pass' : 'fail'; ?>">
            <div>
                <strong><?php echo htmlspecialchars($member['player_name']); ?></strong><br>
                <small>Age: <?php echo $member['age']; ?></small>
            </div>
            <div>
                <?php if(!$member['age_ok']): ?> <span style="color:#ff4757;">❌ Underage</span> <?php endif; ?>
                <?php if($member['conflict']): ?> <span style="color:#ff4757;">🚨 Multi-team Conflict</span> <?php endif; ?>
                <?php if($member['age_ok'] && !$member['conflict']): ?> <span style="color:#2ed573;">✔ Eligible</span> <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div style="margin-top: 30px; text-align: right;">
        <form action="org_dashboard.php" method="POST">
            <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
            <input type="hidden" name="tournament_id" value="<?php echo $t_id; ?>">
            <input type="hidden" name="action_type" value="Approved">
            
            <?php if($team_eligible): ?>
                <button type="submit" class="btn btn-accept">✅ Approve Team</button>
            <?php else: ?>
                <button type="button" class="btn btn-disabled">🚫 Cannot Approve</button>
            <?php endif; ?>
        </form>
        <br>
        <a href="org_dashboard.php" style="color: #888; text-decoration: none;">&larr; Back to Dashboard</a>
    </div>
</div>

</body>
</html>