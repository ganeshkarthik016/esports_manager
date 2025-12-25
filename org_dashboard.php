<?php 
include __DIR__ . '/includes/db_connect.php'; 

// 1. Session & Security
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['organizer_id'])) {
    header("Location: org_login.php");
    exit();
}

$org_id = $_SESSION['organizer_id'];
$org_name = isset($_SESSION['organizer_name']) ? $_SESSION['organizer_name'] : 'Organizer';
$msg = "";

// 2. Handle Tournament Deletion (Cascading Clean-up)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_tournament'])) {
    $t_id = intval($_POST['tournament_id']);
    
    // Start Transaction to ensure data integrity
    $conn->begin_transaction();
    try {
        // A. Delete match records
        $conn->query("DELETE FROM Match_plays WHERE match_id IN (SELECT match_id FROM Matches WHERE tournament_id = $t_id)");
        $conn->query("DELETE FROM Matches WHERE tournament_id = $t_id");

        // B. Delete team roster members related to this tournament
        // This targets players in teams specifically participating in THIS tournament
        $conn->query("DELETE FROM Team_Members WHERE team_id IN (SELECT team_id FROM Participates WHERE tournament_id = $t_id)");

        // C. Delete the participation records and the tournament itself
        $conn->query("DELETE FROM Participates WHERE tournament_id = $t_id");
        $conn->query("DELETE FROM Tournament WHERE tournament_id = $t_id AND organizer_id = $org_id");

        $conn->commit();
        $msg = "<div style='background: #ff4757; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;'>🗑️ Tournament and all associated rosters deleted successfully.</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div style='background: #ff4757; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>Error: " . $conn->error . "</div>";
    }
}

// 3. Fetch Pending Requests
$pending_requests = [];
$sql_pending = "SELECT P.tournament_id, P.team_id, T.tournament_name, Team.team_name, G.game_name, G.min_age 
                FROM Participates P
                JOIN Tournament T ON P.tournament_id = T.tournament_id
                JOIN Team ON P.team_id = Team.team_id
                JOIN game G ON T.game_id = G.game_id
                WHERE T.organizer_id = $org_id AND P.registration_status = 'Pending'";
$res_pending = $conn->query($sql_pending);
if($res_pending) { while($row = $res_pending->fetch_assoc()) $pending_requests[] = $row; }

// 4. Fetch Organizer's Tournaments
$my_tourneys = [];
$sql_my = "SELECT T.*, G.game_name FROM Tournament T 
           JOIN game G ON T.game_id = G.game_id 
           WHERE T.organizer_id = $org_id ORDER BY start_date DESC";
$res_my = $conn->query($sql_my);
while($row = $res_my->fetch_assoc()) $my_tourneys[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard | ORGPANEL</title>
    <style>
        body { background-color: #0f0f13; color: white; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; }
        .container { max-width: 1240px; margin: 40px auto; padding: 20px; }
        header { background: #000; border-bottom: 1px solid #333; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; background: #1a1a24; border-radius: 10px; overflow: hidden; margin-bottom: 50px; }
        th { text-align: left; padding: 15px; background: #252530; color: #aaa; border-bottom: 2px solid #333; }
        td { padding: 15px; border-bottom: 1px solid #333; }
        .btn { border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; transition: 0.2s; display: inline-block; }
        .btn-eval { background: #6c5ce7; color: white; }
        .btn-manage { background: #333; color: #fff; font-size: 0.85rem; }
        .btn-disabled { background: #222; color: #555; cursor: not-allowed; font-size: 0.85rem; }
        .btn-delete { background: none; border: 1px solid #ff4757; color: #ff4757; font-size: 0.8rem; padding: 5px 10px; }
        .btn-delete:hover { background: #ff4757; color: #fff; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .card { background: #1a1a24; padding: 25px; border-radius: 10px; border: 1px solid #333; border-top: 4px solid #e056fd; position: relative; }
    </style>
</head>
<body>

<header>
    <div style="font-size: 1.5rem; font-weight: bold;">ORG<span style="color: #6c5ce7;">PANEL</span></div>
    <div>
        <span style="color: #aaa; margin-right: 20px;">Welcome, <?php echo htmlspecialchars($org_name); ?></span>
        <a href="logout.php" class="btn" style="border: 1px solid #555; color: #fff;">Logout</a>
    </div>
</header>

<div class="container">
    <?php echo $msg; ?>

    <h2 style="border-left: 5px solid #ffa502; padding-left: 15px; margin-bottom: 20px;">⚠️ Pending Registrations</h2>
    <?php if (!empty($pending_requests)): ?>
        <table>
            <thead>
                <tr>
                    <th>Tournament (Game)</th>
                    <th>Team Name</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pending_requests as $req): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($req['tournament_name']); ?></strong><br><small><?php echo htmlspecialchars($req['game_name']); ?></small></td>
                        <td style="font-weight: bold; color: #6c5ce7;"><?php echo htmlspecialchars($req['team_name']); ?></td>
                        <td style="text-align: right;">
                            <a href="evaluate_team.php?team_id=<?php echo $req['team_id']; ?>&t_id=<?php echo $req['tournament_id']; ?>" class="btn btn-eval">🔍 Evaluate</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #555; margin-bottom: 50px;">No pending registrations found.</p>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="border-left: 5px solid #e056fd; padding-left: 15px; margin: 0;">🏆 My Tournaments</h2>
        <a href="create_tournament.php" class="btn" style="background: #e056fd; color: white;">+ Create New</a>
    </div>

    <div class="grid">
        <?php foreach($my_tourneys as $t): 
            $current_date = date("Y-m-d H:i:s");
            // Check if Tournament is ongoing or future
            $is_manageable = ($t['end_date'] >= $current_date);
        ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <h3 style="margin: 0; color: #e056fd;"><?php echo htmlspecialchars($t['tournament_name']); ?></h3>
                    <form method="POST" onsubmit="return confirm('⚠️ DANGER: This will delete ALL matches and ALL team rosters for this tournament. Continue?');">
                        <input type="hidden" name="tournament_id" value="<?php echo $t['tournament_id']; ?>">
                        <button type="submit" name="delete_tournament" class="btn btn-delete">Delete</button>
                    </form>
                </div>
                
                <p style="color: #aaa; font-size: 0.85rem; margin-top: 10px;">
                    Game: <?php echo htmlspecialchars($t['game_name']); ?><br>
                    Ends: <?php echo date('M d, Y', strtotime($t['end_date'])); ?>
                </p>

                <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #2ed573; font-weight: bold;">$<?php echo number_format($t['prize_pool']); ?></span>
                    
                    <?php if($is_manageable): ?>
                        <a href="org_manage_matches.php?id=<?php echo $t['tournament_id']; ?>" class="btn btn-manage">Manage Matches &rarr;</a>
                    <?php else: ?>
                        <span class="btn btn-disabled" title="Tournament has ended">Closed</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>