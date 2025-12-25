<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ign = $_POST['gamer_tag'];
    $name = $_POST['player_name'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $country = $_POST['country'];
    $age = $_POST['age'];
    $contact = $_POST['contact'];

    // Basic validation check
    if (empty($ign) || empty($email) || empty($pass)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if IGN or Email already exists
        $check = $conn->prepare("SELECT player_id FROM Player WHERE email = ? OR gamer_tag = ?");
        $check->bind_param("ss", $email, $ign);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Gamer Tag or Email already taken!";
        } else {
            // Hash the password for security
            $hashed_password = $pass;

            // Insert new player
            $stmt = $conn->prepare("INSERT INTO Player (gamer_tag, player_name, email, password_hash, country, age, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssis", $ign, $name, $email, $hashed_password, $country, $age, $contact);

            if ($stmt->execute()) {
                $success = "Registration successful! <a href='login.php' style='color:#6c5ce7;'>Login here</a>";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>

<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 20px;">Player Registration</h2>
    
    <?php if($error): ?>
        <p style="color: #ff4757; text-align: center; margin-bottom: 15px;"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <?php if($success): ?>
        <p style="color: #2ed573; text-align: center; margin-bottom: 15px;"><?php echo $success; ?></p>
    <?php else: ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Gamer Tag (IGN) *</label>
            <input type="text" name="gamer_tag" required>
        </div>
        
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="player_name">
        </div>

        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" required>
        </div>

        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 1;">
                <label>Country</label>
                <input type="text" name="country">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Age</label>
                <input type="number" name="age">
            </div>
        </div>

        <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact">
        </div>

        <button type="submit" class="btn" style="width: 100%;">Register</button>
    </form>
    <p style="text-align: center; margin-top: 15px; font-size: 0.9rem;">
        Already have an account? <a href="login.php" style="color: #6c5ce7;">Login</a>
    </p>
    <?php endif; ?>
</div>

</body>
</html>