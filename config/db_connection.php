<?php
// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "musicstore_database";

// Create Connection
$conn = new mysqli($host, $username, $password, $database);

// Check Connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
