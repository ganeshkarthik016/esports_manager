<?php 
include __DIR__ . '/includes/db_connect.php'; 

// --- 1. HANDLE POST ACTIONS (REJECT/APPROVE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $post_team_id = intval($_POST['team_id']);
    $post_t_id = intval($_POST['tournament_id']);
    $status = $_POST['action_type']; // 'Approved' or 'Rejected'

    $update_stmt = $conn->prepare("UPDATE participates SET registration_status = ? WHERE team_id = ? AND tournament_id = ?");
    $update_stmt->bind_param("sii", $status, $post_team_id, $post_t_id);
    
    if ($update_stmt->execute()) {
        // Redirect to self to prevent form resubmission on refresh
        header("Location: evaluate_team.php?team_id=$post_team_id&t_id=$post_t_id&status_updated=1");
        exit();
    }
}

// --- 2. GET DATA FROM URL ---
$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
$t_id = isset($_GET['t_id']) ? intval($_GET['t_id']) : 0;

if ($team_id == 0 || $t_id == 0) {
    die("Invalid Team or Tournament ID.");
}

// --- 3. FETCH TOURNAMENT & RULES ---
$sql_info = "SELECT T.tournament_name, G.game_name, G.min_age 
             FROM tournament T 
             JOIN game G ON T.game_id = G.game_id 
             WHERE T.tournament_id = $t_id";
$info = $conn->query($sql_info)->fetch_assoc();

// --- 4. FETCH TEAM MEMBERS & CHECK ELIGIBILITY ---
$sql_members = "SELECT P.player_id, P.player_name, P.age 
                FROM team_members TM 
                JOIN player P ON TM.player_id = P.player_id 
                WHERE TM.team_id = $team_id";
$members_res = $conn->query($sql_members);

$team_eligible = true; 
$member_data = [];

while($m = $members_res->fetch_assoc()) {
    $p_id = $m['player_id'];
    
    // Conflict Check: Is player in another team for THIS tournament?
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
        body { background: #0f0f13; color: white; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .card { background: #1a1a24; padding: 30px; border-radius: 12px; max-width: 800px; margin: auto; border: 1px solid #333; }
        .member-item { background: #252530; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid #444; }
        .pass { border-left-color: #2ed573; }
        .fail { border-left-color: #ff4757; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.3s; text-decoration: none; display: inline-block; font-size: 14px;}
        .btn-accept { background: #2ed573; color: #1a1a24; }
        .btn-reject { background: #ff4757; color: white; }
        .btn:hover { opacity: 0.8; transform: translateY(-1px); }
        .alert { background: #2ed573; color: #0f0f13; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>

<div class="card">
    <?php if(isset($_GET['status_updated'])): ?>
        <div class="alert">Status Updated Successfully!</div>
    <?php endif; ?>

    <h1 style="margin-top:0;">Evaluate: <?php echo htmlspecialchars($info['tournament_name']); ?></h1>
    <p style="color: #ffa502;">Game: <?php echo htmlspecialchars($info['game_name']); ?> | Requirement: <?php echo $info['min_age']; ?>+ Years</p>
    <hr style="border: 0; border-top: 1px solid #333; margin: 20px 0;">

    <?php foreach($member_data as $member): ?>
        <div class="member-item <?php echo ($member['age_ok'] && !$member['conflict']) ? 'pass' : 'fail'; ?>">
            <div>
                <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($member['player_name']); ?></strong><br>
                <small style="color: #aaa;">Age: <?php echo $member['age']; ?></small>
            </div>
            <div>
                <?php if(!$member['age_ok']): ?> <span style="color:#ff4757; font-weight:bold;">❌ Underage</span> <?php endif; ?>
                <?php if($member['conflict']): ?> <span style="color:#ff4757; font-weight:bold;">🚨 Multi-team Conflict</span> <?php endif; ?>
                <?php if($member['age_ok'] && !$member['conflict']): ?> <span style="color:#2ed573; font-weight:bold;">✔ Eligible</span> <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div style="margin-top: 40px; display: flex; justify-content: flex-end; gap: 15px;">
        <form action="" method="POST">
            <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
            <input type="hidden" name="tournament_id" value="<?php echo $t_id; ?>">
            <input type="hidden" name="action_type" value="Rejected">
            <button type="submit" class="btn btn-reject">Reject Team</button>
        </form>

        <form action="" method="POST">
            <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
            <input type="hidden" name="tournament_id" value="<?php echo $t_id; ?>">
            <input type="hidden" name="action_type" value="Approved">
            
            <?php if($team_eligible): ?>
                <button type="submit" class="btn btn-accept">Approve Team</button>
            <?php else: ?>
                <button type="button" class="btn" style="background: #444; color: #888; cursor: not-allowed;">🚫 Ineligible</button>
            <?php endif; ?>
        </form>
    </div>

    <div style="text-align: right; margin-top: 20px;">
        <a href="org_dashboard.php" style="color: #888; text-decoration: none; font-size: 0.9rem;">&larr; Back to Dashboard</a>
    </div>
</div>

</body>
</html>