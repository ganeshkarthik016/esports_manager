<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 

$popup_msg = '';
$popup_type = ''; // 'error' or 'success'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ign = trim($_POST['gamer_tag']);
    $name = trim($_POST['player_name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $country = trim($_POST['country']);
    $age = intval($_POST['age']);
    $contact = trim($_POST['contact']);

    // 1. Basic Validation
    if (empty($ign) || empty($email) || empty($pass) || empty($age) || empty($name)) {
        $popup_msg = "Please fill in all required fields.";
        $popup_type = "error";
    } 
    // Manual Email Check (No FILTER_VALIDATE_EMAIL)
    elseif (strpos($email, '@') === false) {
        $popup_msg = "Invalid email address!";
        $popup_type = "error";
    } 
    else {
        // 2. Uniqueness Check
        $check = $conn->prepare("SELECT player_id FROM Player WHERE email = ? OR gamer_tag = ?");
        $check->bind_param("ss", $email, $ign);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $popup_msg = "Gamer Tag or Email already taken!";
            $popup_type = "error";
        } else {
            // 3. Hash Password
            $hashed_password = $pass;

            // 4. Insert
            $stmt = $conn->prepare("INSERT INTO Player (gamer_tag, player_name, email, password_hash, country, age, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssis", $ign, $name, $email, $hashed_password, $country, $age, $contact);

            if ($stmt->execute()) {
                $popup_msg = "Registration successful! You can now login.";
                $popup_type = "success";
            } else {
                $popup_msg = "Database Error: " . $conn->error;
                $popup_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Player Registration | ORGPANEL</title>
    <style>
        body { background-color: #0f0f13; color: white; font-family: sans-serif; }
        .form-container { max-width: 500px; margin: 50px auto; background: #1a1a24; padding: 30px; border-radius: 10px; border: 1px solid #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #aaa; font-size: 0.9rem; }
        input { width: 100%; padding: 12px; background: #252530; border: 1px solid #444; color: white; border-radius: 5px; box-sizing: border-box; }
        .btn { width: 100%; background: #6c5ce7; color: white; padding: 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 20px;">Player Registration</h2>

    <form method="POST" action="">
        <div class="form-group">
            <label>Gamer Tag (IGN) *</label>
            <input type="text" name="gamer_tag">
        </div>
        
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="player_name">
        </div>

        <div class="form-group">
            <label>Email Address *</label>
            <input type="text" name="email">
        </div>

        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password">
        </div>

        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 1;">
                <label>Country</label>
                <input type="text" name="country">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Age *</label>
                <input type="number" name="age">
            </div>
        </div>

        <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact">
        </div>

        <button type="submit" class="btn">Create Account</button>
    </form>
    
    <p style="text-align: center; margin-top: 15px; font-size: 0.9rem; color: #777;">
        Already have an account? <a href="login.php" style="color: #6c5ce7; text-decoration: none;">Login</a>
    </p>
</div>

<script>
    // Check if there's a message from PHP
    var msg = "<?php echo $popup_msg; ?>";
    var type = "<?php echo $popup_type; ?>";

    if (msg !== "") {
        // Simple Browser Alert
        alert(msg);
        
        // If it was a success, redirect to login page
        if (type === "success") {
            window.location.href = "login.php";
        }
    }
</script>

</body>
</html>