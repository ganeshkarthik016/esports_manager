<?php
// Database configuration
$servername = "localhost";
$username = "root";        // Default XAMPP username
$password = "";            // Default XAMPP password is empty
$dbname = "dbms_project";    // Make sure you create a DB with this name and run the SQL script!

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session on every page that uses this connection
session_start();
?>