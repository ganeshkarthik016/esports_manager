<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Changed from email to gamer_tag
    $ign = $_POST['gamer_tag']; 
    $pass = $_POST['password'];

    // Updated SQL to search by gamer_tag instead of email
    $stmt = $conn->prepare("SELECT player_id, gamer_tag, password_hash FROM Player WHERE gamer_tag = ?");
    $stmt->bind_param("s", $ign);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify Password
        if ($pass === $user['password_hash']) {
            // Success: Set Session Variables
            $_SESSION['player_id'] = $user['player_id'];
            $_SESSION['gamer_tag'] = $user['gamer_tag'];
            
            // Redirect to Dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Gamer Tag not found.";
    }
}
?>

<div class="form-container" style="margin-top: 100px;">
    <h2 style="text-align: center; margin-bottom: 20px;">Player Login</h2>
    
    <?php if($error): ?>
        <p style="color: #ff4757; text-align: center; margin-bottom: 15px;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Gamer Tag (IGN)</label>
            <!-- Changed input type to text and name to gamer_tag -->
            <input type="text" name="gamer_tag" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn" style="width: 100%;">Login</button>
    </form>
    
    <p style="text-align: center; margin-top: 15px; font-size: 0.9rem;">
        New player? <a href="register.php" style="color: #6c5ce7;">Create Account</a>
    </p>
</div>

</body>
</html>