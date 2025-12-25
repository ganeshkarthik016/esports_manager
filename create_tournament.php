<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; // Optional: Uncomment if you have a global header

// 1. Security Check (Organizer Only)
session_start();
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
    
    // Convert HTML datetime-local (YYYY-MM-DDTHH:MM) to MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
    $start = str_replace('T', ' ', $_POST['start_date']) . ":00";
    $end = str_replace('T', ' ', $_POST['end_date']) . ":00";
    
    $prize = floatval($_POST['prize_pool']);
    
    // Basic Validation
    if (empty($name) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
        $msg = "<p style='color: #ff4757;'>Please fill in all fields.</p>";
    } elseif ($end < $start) {
        $msg = "<p style='color: #ff4757;'>End date cannot be before start date.</p>";
    } else {
        // Insert into Database
        $stmt = $conn->prepare("INSERT INTO Tournament (tournament_name, game_id, organizer_id, start_date, end_date, prize_pool) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siissd", $name, $game_id, $org_id, $start, $end, $prize);
        
        if ($stmt->execute()) {
            // Redirect to dashboard with success flag
            header("Location: org_dashboard.php?msg=created");
            exit();
        } else {
            $msg = "<p style='color: #ff4757;'>Error: " . $conn->error . "</p>";
        }
    }
}

// 3. Fetch Games for Dropdown
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
    <title>Create Tournament</title>
    <style>
        body { background-color: #0f0f13; color: white; font-family: sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 40px auto; }
        
        .card { background: #1a1a24; padding: 40px; border-radius: 10px; border: 1px solid #333; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        
        input, select { 
            width: 100%; padding: 12px; margin-bottom: 20px; 
            background: #252530; border: 1px solid #444; color: white; 
            border-radius: 5px; box-sizing: border-box; font-size: 1rem;
        }
        
        input:focus, select:focus { outline: none; border-color: #e056fd; }
        
        label { display: block; color: #ccc; margin-bottom: 8px; font-weight: bold; }
        
        .btn-submit { 
            width: 100%; padding: 15px; background: #e056fd; color: white; 
            border: none; border-radius: 5px; font-size: 1.1rem; font-weight: bold; 
            cursor: pointer; transition: 0.3s; 
        }
        .btn-submit:hover { background: #ce40ed; }
        
        .back-link { color: #aaa; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>

<div class="container">
    
    <a href="org_dashboard.php" class="back-link">&larr; Back to Dashboard</a>

    <div class="card">
        <h2 style="margin-top: 0; color: #fff; border-bottom: 3px solid #e056fd; padding-bottom: 10px; display: inline-block;">Create New Tournament</h2>
        
        <?php echo $msg; ?>

        <form method="POST" style="margin-top: 20px;">
            
            <div>
                <label>Tournament Name</label>
                <input type="text" name="tournament_name" required placeholder="e.g. Winter Championship 2025">
            </div>

            <div>
                <label>Select Game</label>
                <select name="game_id" required>
                    <option value="" disabled selected>-- Choose a Game --</option>
                    <?php foreach($games as $g): ?>
                        <option value="<?php echo $g['game_id']; ?>">
                            <?php echo htmlspecialchars($g['game_name']); ?> (<?php echo $g['platform']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label>Start Date & Time</label>
                    <input type="datetime-local" name="start_date" required>
                </div>
                <div>
                    <label>End Date & Time</label>
                    <input type="datetime-local" name="end_date" required>
                </div>
            </div>

            <div>
                <label>Prize Pool ($)</label>
                <input type="number" name="prize_pool" min="0" step="0.01" required placeholder="0.00">
            </div>

            <button type="submit" name="create_tourney" class="btn-submit">
                🚀 Launch Tournament
            </button>

        </form>
    </div>
</div>

</body>
</html>