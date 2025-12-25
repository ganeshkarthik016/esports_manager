<?php 
include __DIR__ . '/includes/db_connect.php'; 

// Basic Header for this page only (since main header is for players)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: #000;">

<div class="form-container" style="margin-top: 100px; border: 1px solid #333;">
    <h2 style="text-align: center; margin-bottom: 10px; color: #e056fd;">Organizer Portal</h2>
    <p style="text-align: center; color: #777; margin-bottom: 20px;">Manage your tournaments</p>
    
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $pass = $_POST['password'];

        // Check Organizer Table
        $stmt = $conn->prepare("SELECT organizer_id, organizer_name, password FROM Organizer WHERE contact_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $org = $result->fetch_assoc();
            // Direct password check (Plain text as requested)
            if ($pass == $org['password']) {
                $_SESSION['organizer_id'] = $org['organizer_id'];
                $_SESSION['organizer_name'] = $org['organizer_name'];
                header("Location: org_dashboard.php");
                exit();
            } else {
                echo "<p style='color: #ff4757; text-align: center;'>Invalid Password</p>";
            }
        } else {
            echo "<p style='color: #ff4757; text-align: center;'>Organizer email not found</p>";
        }
    }
    ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Organizer Email</label>
            <input type="email" name="email" required placeholder="contact@nodwin.com">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn" style="width: 100%; background-color: #e056fd;">Login to Dashboard</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        <a href="index.php" style="color: #fff; font-size: 0.9rem;">← Back to Main Site</a>
    </p>
</div>

</body>
</html>