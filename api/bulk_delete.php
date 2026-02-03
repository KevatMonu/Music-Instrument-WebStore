<?php
session_start();

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "musicstore_database");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user IDs are selected
if (isset($_POST['selected_users']) && is_array($_POST['selected_users'])) {
    $user_ids = implode(',', array_map('intval', $_POST['selected_users'])); // Securely format IDs

    // Delete users from database
    $sql = "DELETE FROM users WHERE user_id IN ($user_ids)";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Selected users deleted successfully!'); window.location.href='manage_users.php';</script>";
    } else {
        echo "<script>alert('Error deleting users: " . $conn->error . "'); window.location.href='manage_users.php';</script>";
    }
} else {
    echo "<script>alert('No users selected for deletion!'); window.location.href='manage_users.php';</script>";
}

$conn->close();
?>
