<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 

// 1. Security & Setup
if (!isset($_SESSION['player_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("<p style='color:red; padding:20px;'>Tournament ID missing.</p>");
}

$player_id = $_SESSION['player_id'];
$tournament_id = intval($_GET['id']);
$msg = "";

// 2. Fetch Tournament Details (For Max Team Size)
$sql_tourney = "SELECT T.tournament_name, G.game_name, G.max_team_size
                FROM Tournament T 
                JOIN Game G ON T.game_id = G.game_id 
                WHERE T.tournament_id = $tournament_id";
$tourney = $conn->query($sql_tourney)->fetch_assoc();

if (!$tourney) {
    die("Tournament not found.");
}
$max_players = $tourney['max_team_size'];
$slots_available = $max_players - 1; // Exclude Captain

// 3. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_squad'])) {
    $team_name = trim($_POST['team_name']);
    $teammate_tags = $_POST['teammates']; // Array of strings
    
    // Clean up empty inputs (if user didn't fill all slots)
    $teammate_tags = array_filter($teammate_tags, fn($value) => !is_null($value) && $value !== '');
    
    // A. Basic Validation
    if (empty($team_name)) {
        $msg = "<p style='color: #ff4757;'>Team Name is required.</p>";
    } 
    else {
        // B. Validate Teammate Gamer Tags
        $valid_teammate_ids = [];
        $errors = [];
        $seen_tags = []; // To check for duplicates in input

        // Check Captain isn't adding themselves
        // (Assuming session player is already known, we skip logic for that, but checking DB is safer)

        foreach ($teammate_tags as $tag) {
            $tag = trim($tag);
            
            // Check for duplicates in form
            if (in_array(strtolower($tag), $seen_tags)) {
                $errors[] = "You entered '$tag' twice.";
                continue;
            }
            $seen_tags[] = strtolower($tag);

            // Database Lookup
            $stmt = $conn->prepare("SELECT player_id FROM Player WHERE gamer_tag = ? AND player_id != ?");
            $stmt->bind_param("si", $tag, $player_id); // Ensure we don't add captain again
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $valid_teammate_ids[] = $row['player_id'];
            } else {
                $errors[] = "Player with Gamer Tag '<strong>" . htmlspecialchars($tag) . "</strong>' not found.";
            }
        }

        // C. If No Errors, Create Team
        if (empty($errors)) {
            
            // 1. Create Team
            $stmt = $conn->prepare("INSERT INTO Team (team_name, team_captain_id) VALUES (?, ?)");
            $stmt->bind_param("si", $team_name, $player_id);
            
            if ($stmt->execute()) {
                $new_team_id = $stmt->insert_id;
                
                // 2. Add Captain (You)
                $conn->query("INSERT INTO Team_Members (team_id, player_id) VALUES ($new_team_id, $player_id)");
                
                // 3. Add Teammates
                foreach($valid_teammate_ids as $pid) {
                    $conn->query("INSERT INTO Team_Members (team_id, player_id) VALUES ($new_team_id, $pid)");
                }

                // 4. Register for Tournament
                $sql_part = "INSERT INTO Participates (tournament_id, team_id, registration_status, score) 
                             VALUES ($tournament_id, $new_team_id, 'Pending', 0)";
                
                if ($conn->query($sql_part)) {
                    header("Location: tournament_view.php?id=$tournament_id&msg=registered");
                    exit();
                } else {
                    $msg = "<p style='color: #ff4757;'>Team created, but failed to register: " . $conn->error . "</p>";
                }

            } else {
                $msg = "<p style='color: #ff4757;'>Error creating team: " . $conn->error . "</p>";
            }

        } else {
            // Display Errors
            $msg = "<div style='color: #ff4757; background: rgba(255, 71, 87, 0.1); padding: 10px; border-radius: 5px; margin-bottom: 15px;'>";
            foreach($errors as $e) {
                $msg .= "<p style='margin: 5px 0;'>⚠️ $e</p>";
            }
            $msg .= "</div>";
        }
    }
}
?>

<div style="max-width: 600px; margin: 50px auto; padding: 20px;">
    
    <div style="margin-bottom: 30px; text-align: center;">
        <h1 style="color: #fff;">Register Squad</h1>
        <p style="color: #aaa;">
            Event: <strong style="color: #2ed573;"><?php echo htmlspecialchars($tourney['tournament_name']); ?></strong>
        </p>
    </div>

    <div style="background: #1a1a24; padding: 40px; border-radius: 10px; border: 1px solid #333;">
        <?php echo $msg; ?>

        <form method="POST">
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; color: #fff; margin-bottom: 10px; font-weight: bold;">Team Name</label>
                <input type="text" name="team_name" placeholder="Enter Team Name" required 
                       value="<?php echo isset($_POST['team_name']) ? htmlspecialchars($_POST['team_name']) : ''; ?>"
                       style="width: 100%; padding: 15px; background: #252530; border: 1px solid #444; color: white; border-radius: 5px; font-size: 1rem;">
            </div>

            <hr style="border: 0; border-top: 1px solid #333; margin: 20px 0;">

            <h3 style="margin-bottom: 15px; font-size: 1.1rem;">Add Teammates</h3>
            <p style="font-size: 0.9rem; color: #aaa; margin-bottom: 20px;">
                Enter the exact <strong>Gamer Tag</strong> of your teammates.
                <br><em>(Max Team Size: <?php echo $max_players; ?> • You + <?php echo $slots_available; ?> others)</em>
            </p>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                
                <div style="display: flex; gap: 10px; align-items: center; opacity: 0.6;">
                    <span style="background: #6c5ce7; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">IGL</span>
                    <input type="text" value="You (Captain)" disabled
                           style="flex: 1; padding: 12px; background: #15151c; border: 1px solid #333; color: #888; border-radius: 5px; cursor: not-allowed;">
                </div>

                <?php for($i = 1; $i <= $slots_available; $i++): ?>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span style="color: #555; font-weight: bold; width: 20px;"><?php echo $i; ?></span>
                        <input type="text" name="teammates[]" placeholder="Enter Gamer Tag" 
                               value="<?php echo isset($_POST['teammates'][$i-1]) ? htmlspecialchars($_POST['teammates'][$i-1]) : ''; ?>"
                               style="flex: 1; padding: 12px; background: #252530; border: 1px solid #444; color: white; border-radius: 5px;">
                    </div>
                <?php endfor; ?>

            </div>

            <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
                <a href="tournament_view.php?id=<?php echo $tournament_id; ?>" style="color: #aaa; text-decoration: none;">Cancel</a>
                <button type="submit" name="register_squad" class="btn" 
                        style="background: #2ed573; color: #1a1a24; padding: 12px 25px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                    Verify & Register
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>