<?php
// get_image.php
// Database Connection
$conn = new mysqli("localhost", "root", "", "musicstore_database");

// Check Connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get image ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Prepare & Execute Statement
    $stmt = $conn->prepare("SELECT product_image, image_type FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($image, $type);
    $stmt->fetch();
    
    // Output image
    if ($image && $type) {
        header("Content-Type: $type");
        echo $image;
    } else {
        // Return a 404 status for missing images
        header("HTTP/1.0 404 Not Found");
    }
    
    $stmt->close();
} else {
    // Return a 400 Bad Request for invalid ID
    header("HTTP/1.0 400 Bad Request");
}

$conn->close();
?>