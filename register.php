<?php 
include __DIR__ . '/includes/db_connect.php'; 
include __DIR__ . '/includes/header.php'; 

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim inputs to remove accidental spaces
    $ign = trim($_POST['gamer_tag']);
    $name = trim($_POST['player_name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $country = trim($_POST['country']);
    $age = intval($_POST['age']);
    $contact = trim($_POST['contact']);

    // 1. Validation Constraints
    if (empty($ign) || empty($email) || empty($pass) || empty($age) || empty($name)) {
        $error = "Please fill in all required fields.";
    } 
    // INTERNAL CONSTRAINT: Check for valid email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "The email address entered is not valid.";
    } 
    else {
        // 2. Check if IGN or Email already exists
        $check = $conn->prepare("SELECT player_id FROM Player WHERE email = ? OR gamer_tag = ?");
        $check->bind_param("ss", $email, $ign);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Gamer Tag or Email already taken!";
        } else {
            // 3. Security: Hash the password (Don't store plain text!)
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

            // 4. Insert new player into the 'Player' table
            $stmt = $conn->prepare("INSERT INTO Player (gamer_tag, player_name, email, password_hash, country, age, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssis", $ign, $name, $email, $hashed_password, $country, $age, $contact);

            if ($stmt->execute()) {
                $success = "Registration successful! <a href='login.php' style='color:#6c5ce7;'>Login here</a>";
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Player Registration | Esports Manager</title>
    <style>
        /* Basic styling to match your dark theme */
        body { background-color: #0f0f13; color: white; font-family: sans-serif; }
        .form-container { max-width: 500px; margin: 50px auto; background: #1a1a24; padding: 30px; border-radius: 10px; border: 1px solid #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #aaa; font-size: 0.9rem; }
        input { width: 100%; padding: 12px; background: #252530; border: 1px solid #444; color: white; border-radius: 5px; box-sizing: border-box; }
        input:focus { border-color: #6c5ce7; outline: none; }
        .btn { background: #6c5ce7; color: white; padding: 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn:hover { background: #5649c1; }
    </style>
</head>
<body>

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
            <input type="text" name="gamer_tag" required placeholder="e.g. GK_Pro">
        </div>
        
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="player_name" placeholder="Ganesh Karthik">
        </div>

        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required placeholder="gk@iiitj.ac.in">
        </div>

        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" required>
        </div>

        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 1;">
                <label>Country</label>
                <input type="text" name="country" placeholder="India">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Age *</label>
                <input type="number" name="age" min="0">
            </div>
        </div>

        <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact">
        </div>

        <button type="submit" class="btn" style="width: 100%;">Create Account</button>
    </form>
    
    <p style="text-align: center; margin-top: 15px; font-size: 0.9rem; color: #777;">
        Already have an account? <a href="login.php" style="color: #6c5ce7; text-decoration: none;">Login</a>
    </p>
    <?php endif; ?>
</div>

</body>
</html>