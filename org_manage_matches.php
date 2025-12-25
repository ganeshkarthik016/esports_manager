<?php 
include __DIR__ . '/includes/db_connect.php'; 

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['organizer_id'])) {
    header("Location: org_login.php");
    exit();
}

$tourney_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";

// 1. FETCH TOURNAMENT & GAME RULES
$tourney_sql = "SELECT T.*, G.game_name, G.min_teams_per_match 
                FROM Tournament T 
                JOIN game G ON T.game_id = G.game_id 
                WHERE T.tournament_id = $tourney_id";
$tourney = $conn->query($tourney_sql)->fetch_assoc();
if(!$tourney) die("Tournament not found");

// 2. LOGIC: Disqualify Team (Existing)
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

// 3. LOGIC: Create Match with MIN TEAMS Constraint
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_match'])) {
    $m_date = $_POST['match_date'];
    $m_time = $_POST['match_time'];
    $selected_teams = $_POST['participating_teams'] ?? []; 
    $num_selected = count($selected_teams);
    
    $match_ts = strtotime($m_date . " " . $m_time);
    $now = time();

    // VALIDATION: Check against min_teams_per_match
    if ($num_selected < $tourney['min_teams_per_match']) {
        $msg = "<div style='background: #ffa502; color: #000; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold;'>
                ⚠️ Error: {$tourney['game_name']} requires at least {$tourney['min_teams_per_match']} teams per match! (You selected $num_selected)
                </div>";
    } 
    elseif ($match_ts < $now) {
        $msg = "<div style='background: #ff4757; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;'>❌ Error: Match must be in the future!</div>";
    }
    else {
        // SQL INSERT
        $stmt = $conn->prepare("INSERT INTO Matches (tournament_id, match_date, match_time, status) VALUES (?, ?, ?, 'Scheduled')");
        $stmt->bind_param("iss", $tourney_id, $m_date, $m_time);
        
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
}

// 4. LOGIC: Update Scores (Existing)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_scores'])) {
    $mid = $_POST['match_id'];
    $status = $_POST['match_status'];
    $scores = $_POST['scores']; 
    $conn->query("UPDATE Matches SET status = '$status' WHERE match_id = $mid");
    foreach ($scores as $tid => $s) {
        $s = intval($s);
        $conn->query("INSERT INTO Match_plays (match_id, team_id, match_score) VALUES ($mid, $tid, $s) ON DUPLICATE KEY UPDATE match_score = $s");
    }
    $conn->query("UPDATE Participates P SET score = (SELECT COALESCE(SUM(MP.match_score), 0) FROM Match_plays MP JOIN Matches M ON MP.match_id = M.match_id WHERE MP.team_id = P.team_id AND M.tournament_id = $tourney_id) WHERE P.tournament_id = $tourney_id");
}

// 5. FETCH DATA
$teams = [];
$res_t = $conn->query("SELECT Team.team_id, Team.team_name FROM Participates JOIN Team ON Participates.team_id = Team.team_id WHERE tournament_id = $tourney_id AND registration_status = 'Approved'");
while($r = $res_t->fetch_assoc()) $teams[] = $r;
$matches = [];
$res_m = $conn->query("SELECT * FROM Matches WHERE tournament_id = $tourney_id ORDER BY match_id DESC");
while($r = $res_m->fetch_assoc()) $matches[] = $r;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Matches | ORGPANEL</title>
    <style>
        body { background: #0f0f13; color: #fff; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .banner { background: linear-gradient(45deg, #1a1a24, #000); padding: 25px; border-bottom: 2px solid #e056fd; }
        .container { display: grid; grid-template-columns: 320px 1fr; gap: 30px; max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .card { background: #1a1a24; padding: 20px; border-radius: 12px; border: 1px solid #333; margin-bottom: 20px; }
        .team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 15px; }
        .team-label { background: #252530; padding: 10px; border-radius: 6px; border: 1px solid #444; display: flex; align-items: center; cursor: pointer; }
        .team-label input { margin-right: 10px; accent-color: #2ed573; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; color: #fff; text-decoration: none; }
    </style>
</head>
<body>

<div class="banner">
    <div style="max-width:1400px; margin:0 auto; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1 style="color:#e056fd; margin:0;"><?php echo htmlspecialchars($tourney['tournament_name']); ?></h1>
            <p style="color:#888; margin:5px 0 0 0;">Rule: Min <?php echo $tourney['min_teams_per_match']; ?> teams for <?php echo $tourney['game_name']; ?></p>
        </div>
        <a href="org_dashboard.php" class="btn" style="background:#333;">&larr; Dashboard</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h3 style="color:#ff4757; border-bottom:1px solid #333; padding-bottom:10px;">Active Teams</h3>
        <?php foreach($teams as $t): ?>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; background:#0f0f13; padding:10px; border-radius:6px;">
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
            <h3>Schedule New Match</h3>
            <form method="POST">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <input type="date" name="match_date" min="<?php echo date('Y-m-d'); ?>" required style="background:#000; color:#fff; padding:10px; border:1px solid #444;">
                    <input type="time" name="match_time" required style="background:#000; color:#fff; padding:10px; border:1px solid #444;">
                </div>
                <div class="team-grid">
                    <?php foreach($teams as $t): ?>
                        <label class="team-label">
                            <input type="checkbox" name="participating_teams[]" value="<?php echo $t['team_id']; ?>">
                            <?php echo htmlspecialchars($t['team_name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="create_match" class="btn" style="background:#2ed573; color:#1a1a24; width:100%; margin-top:20px;">Launch Match</button>
            </form>
        </div>

        <?php foreach($matches as $m): ?>
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="match_id" value="<?php echo $m['match_id']; ?>">
                    <div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">
                        <span>Match #<?php echo $m['match_id']; ?> (<?php echo $m['match_date']; ?>)</span>
                        <div style="display:flex; gap:10px;">
                            <select name="match_status" style="background:#000; color:#fff;">
                                <option value="Scheduled" <?php if($m['status']=='Scheduled') echo 'selected'; ?>>Scheduled</option>
                                <option value="Live" <?php if($m['status']=='Live') echo 'selected'; ?>>Live</option>
                                <option value="Completed" <?php if($m['status']=='Completed') echo 'selected'; ?>>Completed</option>
                            </select>
                            <button type="submit" name="update_scores" class="btn" style="background:#e056fd; padding:5px 10px;">Save</button>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:10px;">
                        <?php 
                        $res_p = $conn->query("SELECT MP.*, T.team_name FROM Match_plays MP JOIN Team T ON MP.team_id = T.team_id WHERE match_id = {$m['match_id']}");
                        while($p = $res_p->fetch_assoc()): ?>
                            <div style="background:#0f0f13; padding:10px; border-radius:6px; display:flex; justify-content:space-between;">
                                <span><?php echo htmlspecialchars($p['team_name']); ?></span>
                                <input type="number" name="scores[<?php echo $p['team_id']; ?>]" value="<?php echo $p['match_score']; ?>" style="width:50px; background:#1a1a24; color:#2ed573; border:none; text-align:center;">
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