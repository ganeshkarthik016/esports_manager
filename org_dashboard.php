<?php 
include __DIR__ . '/includes/db_connect.php'; 
 //include __DIR__ . '/includes/header.php'; // Optional header

// 1. Security Check
if (!isset($_SESSION['organizer_id'])) {
    header("Location: org_login.php");
    exit();
}

$org_id = $_SESSION['organizer_id'];
$org_name = isset($_SESSION['organizer_name']) ? $_SESSION['organizer_name'] : 'Organizer';
$msg = "";

// 2. Handle "Tournament Created" Success Message
if (isset($_GET['msg']) && $_GET['msg'] == 'created') {
    $msg = "<div style='background: #e056fd; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold;'>
                🎉 Tournament Created Successfully!
            </div>";
}

// 3. Handle Accept/Reject Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type'])) {
    $target_team = intval($_POST['team_id']);
    $target_tourney = intval($_POST['tournament_id']);
    $action = $_POST['action_type']; // 'Approved' or 'Rejected'
    
    // Update the status
    $stmt = $conn->prepare("UPDATE Participates SET registration_status = ? WHERE team_id = ? AND tournament_id = ?");
    $stmt->bind_param("sii", $action, $target_team, $target_tourney);
    
    if ($stmt->execute()) {
        $color = ($action == 'Approved') ? '#2ed573' : '#ff4757';
        $msg = "<div style='background: $color; color: #1a1a24; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold;'>
                    Team $action Successfully!
                </div>";
    }
}

// 4. FETCH PENDING REQUESTS (Strict Filter: Only YOUR tournaments)
$pending_requests = [];

$sql_pending = "SELECT P.tournament_id, P.team_id, P.registration_status, 
                       T.tournament_name, Team.team_name 
                FROM Participates P
                JOIN Tournament T ON P.tournament_id = T.tournament_id
                JOIN Team ON P.team_id = Team.team_id
                WHERE T.organizer_id = $org_id AND P.registration_status = 'Pending'";

$res_pending = $conn->query($sql_pending);
if($res_pending) {
    while($row = $res_pending->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

// 5. Fetch My Tournaments
$my_tourneys = [];
$sql_my = "SELECT * FROM Tournament WHERE organizer_id = $org_id ORDER BY start_date DESC";
$res_my = $conn->query($sql_my);
while($row = $res_my->fetch_assoc()) $my_tourneys[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard</title>
    <style>
        body { background-color: #0f0f13; color: white; font-family: sans-serif; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; background: #1a1a24; border-radius: 10px; overflow: hidden; margin-bottom: 50px; }
        th { text-align: left; padding: 15px; background: #252530; color: #aaa; border-bottom: 2px solid #333; }
        td { padding: 15px; border-bottom: 1px solid #333; vertical-align: middle; }
        
        /* Buttons */
        .btn { border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .btn-accept { background: #2ed573; color: #1a1a24; }
        .btn-reject { background: #ff4757; color: white; }
        .btn:hover { opacity: 0.8; }
        
        /* Grid */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card { background: #1a1a24; padding: 25px; border-radius: 10px; border: 1px solid #333; }
    </style>
</head>
<body>

<header style="background: #000; border-bottom: 1px solid #333; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center;">
    <div style="font-size: 1.5rem; font-weight: bold;">ORG<span style="color: #6c5ce7;">PANEL</span></div>
    <div>
        <span style="color: #aaa; margin-right: 20px;">Welcome, <?php echo htmlspecialchars($org_name); ?></span>
        <a href="logout.php" style="color: #fff; text-decoration: none; border: 1px solid #555; padding: 5px 15px; border-radius: 5px;">Logout</a>
    </div>
</header>

<div class="container">
    <?php echo $msg; ?>

    <h2 style="border-left: 5px solid #ffa502; padding-left: 15px;">⚠️ Pending Requests</h2>
    
    <?php if (!empty($pending_requests)): ?>
        <table>
            <thead>
                <tr>
                    <th>Tournament</th>
                    <th>Team Name</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pending_requests as $req): ?>
                    <tr>
                        <td style="color: #ccc;"><?php echo htmlspecialchars($req['tournament_name']); ?></td>
                        <td style="font-weight: bold; color: #fff;"><?php echo htmlspecialchars($req['team_name']); ?></td>
                        <td>
                            <span style="background: #ffa502; color: #000; padding: 3px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">
                                Pending
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                <form method="POST">
                                    <input type="hidden" name="team_id" value="<?php echo $req['team_id']; ?>">
                                    <input type="hidden" name="tournament_id" value="<?php echo $req['tournament_id']; ?>">
                                    <input type="hidden" name="action_type" value="Approved">
                                    <button type="submit" class="btn btn-accept">✔ Accept</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="team_id" value="<?php echo $req['team_id']; ?>">
                                    <input type="hidden" name="tournament_id" value="<?php echo $req['tournament_id']; ?>">
                                    <input type="hidden" name="action_type" value="Rejected">
                                    <button type="submit" class="btn btn-reject">✖ Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="background: #1a1a24; padding: 30px; border-radius: 10px; text-align: center; color: #777; margin-bottom: 50px;">
            ✅ No pending requests found.
        </div>
    <?php endif; ?>


    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="border-left: 5px solid #e056fd; padding-left: 15px; margin: 0;">🏆 My Tournaments</h2>
        <a href="create_tournament.php" class="btn" style="background: #e056fd; color: #fff; text-decoration: none; padding: 10px 20px; font-size: 1rem;">
            + Create New
        </a>
    </div>
    
    <?php if (!empty($my_tourneys)): ?>
        <div class="grid">
            <?php foreach($my_tourneys as $t): ?>
                <div class="card">
                    <h3 style="color: #e056fd; margin-top: 0;"><?php echo htmlspecialchars($t['tournament_name']); ?></h3>
                    <p style="color: #888; margin-bottom: 15px;">
                        <?php echo date('M d', strtotime($t['start_date'])); ?> - <?php echo date('M d', strtotime($t['end_date'])); ?>
                    </p>
                    <div style="border-top: 1px solid #333; padding-top: 15px; display: flex; justify-content: space-between;">
                        <span style="color: #2ed573;">$<?php echo number_format($t['prize_pool']); ?></span>
                        
                        <a href="org_manage_matches.php?id=<?php echo $t['tournament_id']; ?>" 
                           style="color: #fff; text-decoration: none; background: #333; padding: 5px 15px; border-radius: 4px; font-size: 0.9rem;">
                           Update &rarr;
                        </a>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: #1a1a24; padding: 30px; border-radius: 10px; text-align: center; color: #777;">
            You haven't created any tournaments yet. Click the button above to start!
        </div>
    <?php endif; ?>

</div>

</body>
</html>