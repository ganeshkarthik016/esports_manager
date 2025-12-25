<?php 
include __DIR__ . '/includes/db_connect.php'; 

// 1. Security & Session Check
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['organizer_id'])) {
    header("Location: org_login.php");
    exit();
}

$tourney_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";

// 2. FETCH TOURNAMENT DATA
$tourney_sql = "SELECT T.*, G.game_name, G.min_teams_per_match 
                FROM Tournament T 
                JOIN game G ON T.game_id = G.game_id 
                WHERE T.tournament_id = $tourney_id";
$tourney = $conn->query($tourney_sql)->fetch_assoc();
if(!$tourney) die("Tournament not found");

// 3. LOGIC: Disqualify Team
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['disqualify_team'])) {
    $target_team_id = intval($_POST['target_team_id']);
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM Match_plays WHERE team_id = $target_team_id");
        $conn->query("DELETE FROM Participates WHERE team_id = $target_team_id AND tournament_id = $tourney_id");
        $conn->query("DELETE FROM Team_Members WHERE team_id = $target_team_id");
        $conn->commit();
        $msg = "<div style='background: #ff4757; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;'>🚨 Team Disqualified.</div>";
    } catch (Exception $e) { $conn->rollback(); }
}

// 4. LOGIC: Create Match (Manual End Time)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_match'])) {
    $m_date = $_POST['match_date'];
    $m_time = $_POST['match_time'];
    $e_time = $_POST['end_time']; 
    $selected_teams = $_POST['participating_teams'] ?? []; 
    
    $stmt = $conn->prepare("INSERT INTO Matches (tournament_id, match_date, match_time, end_time, status) VALUES (?, ?, ?, ?, 'Scheduled')");
    $stmt->bind_param("isss", $tourney_id, $m_date, $m_time, $e_time);
    if($stmt->execute()) {
        $new_id = $conn->insert_id;
        foreach($selected_teams as $tid) {
            $stmt_mp = $conn->prepare("INSERT INTO Match_plays (match_id, team_id, match_score) VALUES (?, ?, 0)");
            $stmt_mp->bind_param("ii", $new_id, $tid);
            $stmt_mp->execute();
        }
        $msg = "<div style='background: #2ed573; color: #1a1a24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;'>✅ Match Scheduled!</div>";
    }
}

// 5. LOGIC: Update Scores & Sync Standings
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_scores'])) {
    $mid = $_POST['match_id'];
    $scores = $_POST['scores']; 
    foreach ($scores as $tid => $s) {
        $s = intval($s);
        $conn->query("INSERT INTO Match_plays (match_id, team_id, match_score) VALUES ($mid, $tid, $s) ON DUPLICATE KEY UPDATE match_score = $s");
    }
    // Auto-update the total score in Participates table
    $conn->query("UPDATE Participates P SET score = (SELECT COALESCE(SUM(MP.match_score), 0) FROM Match_plays MP JOIN Matches M ON MP.match_id = M.match_id WHERE MP.team_id = P.team_id AND M.tournament_id = $tourney_id) WHERE P.tournament_id = $tourney_id");
    $msg = "<div style='background: #2ed573; color: #1a1a24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;'>✅ Scores Updated!</div>";
}

// 6. FETCH DATA - SORTED BY NEAREST MATCH
$teams = [];
$res_t = $conn->query("SELECT Team.team_id, Team.team_name FROM Participates JOIN Team ON Participates.team_id = Team.team_id WHERE tournament_id = $tourney_id AND registration_status = 'Approved'");
while($r = $res_t->fetch_assoc()) $teams[] = $r;

// SQL Logic: Sort by the match closest to current date and time
$sql_m = "SELECT * FROM Matches WHERE tournament_id = $tourney_id 
          ORDER BY ABS(TIMESTAMPDIFF(SECOND, NOW(), CONCAT(match_date, ' ', match_time))) ASC";
$res_m = $conn->query($sql_m);
$matches = [];
while($m = $res_m->fetch_assoc()) {
    $start_ts = strtotime($m['match_date'] . " " . $m['match_time']);
    $end_ts = strtotime($m['match_date'] . " " . $m['end_time']);
    $now = time();

    // LIVE Status Logic
    if ($now < $start_ts) { $m['auto_status'] = 'Scheduled'; $m['color'] = '#ffa502'; }
    elseif ($now >= $start_ts && $now <= $end_ts) { $m['auto_status'] = 'Live'; $m['color'] = '#ff4757'; }
    else { $m['auto_status'] = 'Completed'; $m['color'] = '#2ed573'; }
    $matches[] = $m;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Match Manager | ORGPANEL</title>
    <style>
        body { background: #0f0f13; color: #fff; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .banner { background: linear-gradient(45deg, #1a1a24, #000); padding: 30px; border-bottom: 2px solid #e056fd; }
        .container { display: grid; grid-template-columns: 320px 1fr; gap: 30px; max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .card { background: #1a1a24; padding: 20px; border-radius: 12px; border: 1px solid #333; margin-bottom: 25px; }
        .team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin: 15px 0; }
        .team-label { background: #252530; padding: 10px; border-radius: 8px; border: 1px solid #444; display: flex; align-items: center; cursor: pointer; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; color: #fff; }
        input { background: #000; color: #fff; border: 1px solid #444; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>

<div class="banner">
    <div style="max-width:1400px; margin:0 auto; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1 style="color:#e056fd; margin:0;"><?php echo htmlspecialchars($tourney['tournament_name']); ?></h1>
            <p style="color:#888;">Tournament Management Dashboard</p>
        </div>
        <a href="org_dashboard.php" class="btn" style="background:#333; text-decoration:none;">&larr; Back</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h3 style="color:#ff4757; border-bottom:1px solid #333; padding-bottom:10px;">🛡️ Approved Teams</h3>
        <?php foreach($teams as $t): ?>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; background:#0f0f13; padding:10px; border-radius:8px; border:1px solid #222;">
                <span><?php echo htmlspecialchars($t['team_name']); ?></span>
                <form method="POST" onsubmit="return confirm('Disqualify?');">
                    <input type="hidden" name="target_team_id" value="<?php echo $t['team_id']; ?>">
                    <button type="submit" name="disqualify_team" style="background:none; border:none; color:#ff4757; cursor:pointer;">🗑️</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div>
        <?php echo $msg; ?>
        
        <div class="card" style="border-left: 5px solid #2ed573;">
            <h3>📅 Schedule New Match</h3>
            <form method="POST">
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:20px;">
                    <div><label>Date</label><input type="date" name="match_date" required style="width:100%;"></div>
                    <div><label>Start</label><input type="time" name="match_time" required style="width:100%;"></div>
                    <div><label>End</label><input type="time" name="end_time" required style="width:100%;"></div>
                </div>
                <div class="team-grid">
                    <?php foreach($teams as $t): ?>
                        <label class="team-label">
                            <input type="checkbox" name="participating_teams[]" value="<?php echo $t['team_id']; ?>" style="margin-right:10px;">
                            <?php echo htmlspecialchars($t['team_name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="create_match" class="btn" style="background:#2ed573; color:#1a1a24; width:100%;">Launch Match</button>
            </form>
        </div>

        <?php foreach($matches as $m): ?>
            <div class="card" style="border-right: 5px solid <?php echo $m['color']; ?>;">
                <form method="POST">
                    <input type="hidden" name="match_id" value="<?php echo $m['match_id']; ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">
                        <div>
                            <span style="color:#e056fd; font-weight:bold;">Match #<?php echo $m['match_id']; ?></span>
                            <span style="color:#fff; font-size:0.9rem; margin-left:15px; background: #333; padding: 2px 8px; border-radius: 4px;">
                                📅 <?php echo date('M d, Y', strtotime($m['match_date'])); ?>
                            </span>
                            <span style="color:#888; font-size:0.85rem; margin-left:10px;">
                                ⏰ <?php echo date('H:i', strtotime($m['match_time'])); ?> - <?php echo date('H:i', strtotime($m['end_time'])); ?>
                            </span>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <span style="background:<?php echo $m['color']; ?>; color:#000; padding:2px 10px; border-radius:10px; font-size:0.7rem; font-weight:bold; text-transform:uppercase;">
                                <?php echo $m['auto_status']; ?>
                            </span>
                            <button type="submit" name="update_scores" class="btn" style="background:#e056fd; padding:5px 12px; font-size:0.8rem;">Save Scores</button>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:12px;">
                        <?php 
                        $res_p = $conn->query("SELECT MP.*, T.team_name FROM Match_plays MP JOIN Team T ON MP.team_id = T.team_id WHERE match_id = {$m['match_id']}");
                        while($p = $res_p->fetch_assoc()): ?>
                            <div style="background:#0f0f13; padding:12px; border-radius:8px; display:flex; justify-content:space-between; border:1px solid #222; align-items:center;">
                                <span><?php echo htmlspecialchars($p['team_name']); ?></span>
                                <input type="number" name="scores[<?php echo $p['team_id']; ?>]" value="<?php echo $p['match_score']; ?>" style="width:70px; background:#1a1a24; color:#2ed573; border:1px solid #333; text-align:center;">
                            </div>
                        <?php endwhile; ?>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>