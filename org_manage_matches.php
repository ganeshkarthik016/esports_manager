<?php 
include __DIR__ . '/includes/db_connect.php'; 

// 1. Security Check
if (!isset($_SESSION['organizer_id'])) {
    header("Location: org_login.php");
    exit();
}

$tourney_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";

// 2. LOGIC: Create a New Match
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_match'])) {
    $m_date = $_POST['match_date'];
    $m_time = $_POST['match_time'];
    $selected_teams = $_POST['participating_teams'] ?? []; // Array of team IDs
    
    // Validate teams selected
    if (empty($selected_teams)) {
        $msg = "<p style='color: #ff4757; text-align:center;'>Error: Please select at least one team for the match.</p>";
    } else {
        // A. Insert Match into Matches table
        $stmt = $conn->prepare("INSERT INTO Matches (tournament_id, match_date, match_time, status) VALUES (?, ?, ?, 'Scheduled')");
        $stmt->bind_param("iss", $tourney_id, $m_date, $m_time);
        
        if($stmt->execute()) {
            $new_match_id = $conn->insert_id;
            $insert_count = 0;
            
            // B. Link selected teams to the Match (Insert into Match_plays)
            foreach($selected_teams as $team_id) {
                $team_id = intval($team_id);
                $stmt_team = $conn->prepare("INSERT INTO Match_plays (match_id, team_id, match_score) VALUES (?, ?, 0)");
                $stmt_team->bind_param("ii", $new_match_id, $team_id);
                if ($stmt_team->execute()) {
                    $insert_count++;
                }
            }
            
            $msg = "<p style='color: #2ed573; text-align:center;'>Match Scheduled Successfully with $insert_count teams!</p>";
            
        } else {
            $msg = "<p style='color: #ff4757; text-align:center;'>Database Error: " . $conn->error . "</p>";
        }
    }
}

// 3. LOGIC: Update Scores & Status (remains the same)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_scores'])) {
    $match_id = $_POST['match_id'];
    $status = $_POST['match_status'];
    $scores = $_POST['scores']; // Array [team_id => score]

    // A. Update Match Status
    $stmt = $conn->prepare("UPDATE Matches SET status = ? WHERE match_id = ?");
    $stmt->bind_param("si", $status, $match_id);
    $stmt->execute();

    // B. Update Scores for each team
    foreach ($scores as $team_id => $score) {
        $score = intval($score);
        // Using ON DUPLICATE KEY UPDATE to handle both insert and update
        $sql_score = "INSERT INTO Match_plays (match_id, team_id, match_score) VALUES ($match_id, $team_id, $score)
                      ON DUPLICATE KEY UPDATE match_score = $score";
        $conn->query($sql_score);
    }

    // C. Recalculate Leaderboard
    $sql_calc = "UPDATE Participates P 
                 SET score = (
                    SELECT COALESCE(SUM(MP.match_score), 0)
                    FROM Match_plays MP
                    JOIN Matches M ON MP.match_id = M.match_id
                    WHERE MP.team_id = P.team_id AND M.tournament_id = $tourney_id
                 )
                 WHERE P.tournament_id = $tourney_id";
    $conn->query($sql_calc);

    $msg = "<p style='color: #2ed573; text-align:center;'>Scores Updated & Leaderboard Recalculated!</p>";
}

// 4. DATA: Get Tournament, Teams, and Matches
$tourney = $conn->query("SELECT * FROM Tournament WHERE tournament_id = $tourney_id")->fetch_assoc();
if(!$tourney) die("Tournament not found");

// Get Approved Teams (These are the ones available to be played)
$teams = [];
$res_teams = $conn->query("SELECT Team.team_id, Team.team_name FROM Participates JOIN Team ON Participates.team_id = Team.team_id WHERE tournament_id = $tourney_id AND registration_status = 'Approved'");
while($r = $res_teams->fetch_assoc()) $teams[] = $r;

// Get Matches
$matches = [];
$res_matches = $conn->query("SELECT * FROM Matches WHERE tournament_id = $tourney_id ORDER BY match_id DESC");
while($r = $res_matches->fetch_assoc()) $matches[] = $r;

// Function to fetch current match participants (for display purposes)
function get_participants($conn, $match_id) {
    $sql = "SELECT T.team_name FROM Match_plays MP JOIN Team T ON MP.team_id = T.team_id WHERE MP.match_id = $match_id";
    $result = $conn->query($sql);
    $names = [];
    while($row = $result->fetch_assoc()) {
        $names[] = $row['team_name'];
    }
    return $names;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Matches</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .match-card { background: #1a1a24; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #333; }
        .score-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 15px; }
        .team-input { background: #0f0f13; padding: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #444; }
        .team-input input { width: 60px; padding: 5px; background: #000; color: #2ed573; border: 1px solid #333; text-align: center; font-weight: bold; }
        .team-select { height: 150px; background: #0f0f13; border: 1px solid #444; color: #fff; padding: 5px; }
    </style>
</head>
<body style="background-color: #0f0f13; color: #fff;">

<div style="max-width: 1000px; margin: 40px auto; padding: 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: #e056fd;"><?php echo htmlspecialchars($tourney['tournament_name']); ?> <span style="color: #fff; font-size: 1.2rem;">Manager</span></h1>
        <a href="org_dashboard.php" class="btn" style="background: #333;">← Back to Dashboard</a>
    </div>

    <?php echo $msg; ?>

    <!-- 1. CREATE MATCH SECTION -->
    <div style="background: #1a1a24; padding: 20px; border-radius: 10px; border-left: 5px solid #2ed573; margin-bottom: 40px;">
        <h3>📅 Schedule New Match</h3>
        <form method="POST" action="">
            <div style="display: flex; gap: 15px; margin-top: 15px; align-items: stretch;">
                
                <!-- Date/Time Fields -->
                <div style="flex: 1;">
                    <label style="display:block; margin-bottom:5px;">Date</label>
                    <input type="date" name="match_date" required style="width:100%; padding: 8px; background: #000; border: 1px solid #444; color: #fff;">
                </div>
                <div style="flex: 1;">
                    <label style="display:block; margin-bottom:5px;">Time</label>
                    <input type="time" name="match_time" required style="width:100%; padding: 8px; background: #000; border: 1px solid #444; color: #fff;">
                </div>
                
                <!-- Team Selection Field -->
                <div style="flex: 2;">
                    <label style="display:block; margin-bottom:5px;">Select Teams (Hold CTRL/CMD to select multiple)</label>
                    <select multiple name="participating_teams[]" required class="team-select" style="width: 100%;">
                        <?php foreach($teams as $team): ?>
                            <option value="<?php echo $team['team_id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
            </div>
            <button type="submit" name="create_match" class="btn" style="margin-top: 15px; width: 100%;">Add Match</button>
        </form>
        
        <?php if(empty($teams)): ?>
            <p style="color: #ff4757; margin-top: 15px;">Note: No teams have been approved for this tournament yet!</p>
        <?php endif; ?>
    </div>

    <!-- 2. EXISTING MATCHES LIST -->
    <h2 style="border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px;">Match List & Scores</h2>
    
    <?php if(empty($matches)): ?>
        <p style="color: #666;">No matches scheduled yet.</p>
    <?php endif; ?>

    <?php foreach($matches as $match): ?>
        <div class="match-card">
            <form method="POST" action="">
                <input type="hidden" name="match_id" value="<?php echo $match['match_id']; ?>">
                
                <!-- Match Header Controls -->
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 15px;">
                    <div>
                        <h3 style="margin: 0;">Match #<?php echo $match['match_id']; ?></h3>
                        <small style="color:#aaa;">Participants: <?php echo implode(', ', get_participants($conn, $match['match_id'])); ?></small>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select name="match_status" style="padding: 5px; background: #000; color: #fff; border: 1px solid #444;">
                            <option value="Scheduled" <?php if($match['status']=='Scheduled') echo 'selected'; ?>>Scheduled</option>
                            <option value="Live" <?php if($match['status']=='Live') echo 'selected'; ?>>🔴 Live</option>
                            <option value="Completed" <?php if($match['status']=='Completed') echo 'selected'; ?>>✅ Completed</option>
                        </select>
                        <button type="submit" name="update_scores" class="btn" style="background: #e056fd; font-size: 0.8rem;">Save Changes</button>
                    </div>
                </div>

                <!-- Teams Score Grid -->
                <div class="score-grid">
                    <?php 
                    // Fetch only teams participating in THIS specific match (from Match_plays)
                    $match_participants = $conn->query("SELECT MP.team_id, T.team_name, MP.match_score 
                                                        FROM Match_plays MP 
                                                        JOIN Team T ON MP.team_id = T.team_id 
                                                        WHERE MP.match_id = {$match['match_id']}");
                    while($team_data = $match_participants->fetch_assoc()):
                    ?>
                        <div class="team-input">
                            <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($team_data['team_name']); ?></span>
                            <!-- Input name links team_id to the score: scores[101] = 15 -->
                            <input type="number" name="scores[<?php echo $team_data['team_id']; ?>]" value="<?php echo $team_data['match_score']; ?>" min="0">
                        </div>
                    <?php endwhile; ?>
                </div>

            </form>
        </div>
    <?php endforeach; ?>

</div>

</body>
</html>