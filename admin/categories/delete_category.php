<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "musicstore_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_categories.php");
    exit();
}

$category_id = $_GET['id'];

// Get the image path before deleting the category
$query = "SELECT category_image FROM categories WHERE category_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $image_path = $row['category_image'];
    
    // Delete the category
    $delete_query = "DELETE FROM categories WHERE category_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $category_id);
    
    if ($delete_stmt->execute()) {
        // Delete the image file if it exists
        if (!empty($image_path) && file_exists($image_path) && strpos($image_path, 'default') === false) {
            unlink($image_path);
        }
        
        echo "<script>alert('Category deleted successfully!'); window.location.href='manage_categories.php';</script>";
    } else {
        echo "<script>alert('Error deleting category: " . $delete_stmt->error . "'); window.location.href='manage_categories.php';</script>";
    }
    
    $delete_stmt->close();
} else {
    echo "<script>alert('Category not found!'); window.location.href='manage_categories.php';</script>";
}

$stmt->close();
$conn->close();
?>