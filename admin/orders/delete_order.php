<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

// Check if the order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Order ID is missing!'); window.location.href='manage_orders.php';</script>";
    exit();
}

$order_id = $_GET['id'];

$conn = new mysqli("localhost", "root", "", "musicstore_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete the order
$sql = "DELETE FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    echo "<script>alert('Order deleted successfully!'); window.location.href='manage_orders.php';</script>";
} else {
    echo "<script>alert('Error deleting order: " . $stmt->error . "'); window.location.href='manage_orders.php';</script>";
}

$stmt->close();
$conn->close();
?>