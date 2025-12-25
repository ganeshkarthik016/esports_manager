<?php 
include __DIR__ . '/includes/db_connect.php'; 

// 1. Session & Security Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['organizer_id'])) {
    header("Location: org_login.php");
    exit();
}

$org_id = $_SESSION['organizer_id'];
$msg = "";

// 2. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_tourney'])) {
    $name = trim($_POST['tournament_name']);
    $game_id = intval($_POST['game_id']);
    
    // Convert HTML datetime-local to MySQL format
    $start = str_replace('T', ' ', $_POST['start_date']) . ":00";
    $end = str_replace('T', ' ', $_POST['end_date']) . ":00";
    $prize = floatval($_POST['prize_pool']);
    
    // Get current time for future-date constraint
    $current_time = date("Y-m-d H:i:s");
    
    // Validation Logic
    if (empty($name) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
        $msg = "<div style='color: #ff4757; margin-bottom: 20px;'>⚠️ Please fill in all fields.</div>";
    } elseif ($start < $current_time) {
        // FUTURE DATE CONSTRAINT
        $msg = "<div style='color: #ff4757; margin-bottom: 20px;'>❌ Error: Start date must be in the future!</div>";
    } elseif ($end <= $start) {
        $msg = "<div style='color: #ff4757; margin-bottom: 20px;'>❌ Error: End date must be after the start date.</div>";
    } else {
        // SQL Insert
        $stmt = $conn->prepare("INSERT INTO Tournament (tournament_name, game_id, organizer_id, start_date, end_date, prize_pool) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siissd", $name, $game_id, $org_id, $start, $end, $prize);
        
        if ($stmt->execute()) {
            header("Location: org_dashboard.php?msg=created");
            exit();
        } else {
            $msg = "<div style='color: #ff4757; margin-bottom: 20px;'>Error: " . $conn->error . "</div>";
        }
    }
}

// 3. Fetch Games for the selection dropdown
$games = [];
$sql_games = "SELECT game_id, game_name, platform FROM Game ORDER BY game_name ASC";
$result = $conn->query($sql_games);
while($row = $result->fetch_assoc()) {
    $games[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Launch Tournament | ORGPANEL</title>
    <style>
        body { background-color: #0f0f13; color: white; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 650px; margin: 40px auto; }
        .card { background: #1a1a24; padding: 40px; border-radius: 12px; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        label { display: block; color: #aaa; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        input, select { 
            width: 100%; padding: 12px; margin-bottom: 20px; 
            background: #252530; border: 1px solid #444; color: white; 
            border-radius: 6px; box-sizing: border-box; font-size: 1rem;
        }
        input:focus { border-color: #e056fd; outline: none; }
        
        .btn-submit { 
            width: 100%; padding: 15px; background: #e056fd; color: white; 
            border: none; border-radius: 6px; font-size: 1.1rem; font-weight: bold; 
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { background: #ce40ed; transform: translateY(-2px); }
        .back-link { color: #888; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <a href="org_dashboard.php" class="back-link">&larr; Back to Control Panel</a>

    <div class="card">
        <h2 style="margin: 0 0 10px 0;">Create Tournament</h2>
        <p style="color: #666; margin-bottom: 30px;">Set up your event details and entry requirements.</p>
        
        <?php echo $msg; ?>

        <form method="POST">
            <label>Event Name</label>
            <input type="text" name="tournament_name" required placeholder="e.g. Pro League Season 1">

            <label>Game Title</label>
            <select name="game_id" required>
                <option value="" disabled selected>Select Game</option>
                <?php foreach($games as $g): ?>
                    <option value="<?php echo $g['game_id']; ?>">
                        <?php echo htmlspecialchars($g['game_name']); ?> (<?php echo $g['platform']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label>Starts At</label>
                    <input type="datetime-local" name="start_date" min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                </div>
                <div>
                    <label>Ends At</label>
                    <input type="datetime-local" name="end_date" required>
                </div>
            </div>

            <label>Total Prize Pool ($)</label>
            <input type="number" name="prize_pool" min="0" step="0.01" required placeholder="50000.00">

            <button type="submit" name="create_tourney" class="btn-submit">
                🚀 Launch Tournament
            </button>
        </form>
    </div>
</div>

</body>
</html>