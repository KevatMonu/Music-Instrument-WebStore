<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='../auth/sign-in.php';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "musicstore_database");

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name'] ?? '');
    $category_description = trim($_POST['category_description'] ?? '');
    
    // Validate fields
    if (empty($category_name) || empty($category_description)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Initialize variables
        $category_image = NULL;
        $category_image_type = NULL;
        
        // Check if image is uploaded
        if (!empty($_FILES['category_image']['name']) && $_FILES['category_image']['error'] == 0) {
            // Expanded list of allowed image extensions
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff', 'ico', 'heic'];
            $filename = $_FILES['category_image']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($filetype, $allowed)) {
                // Create uploads directory if it doesn't exist
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                // Generate a unique filename to prevent overwriting
                $new_filename = uniqid() . '_' . $filename;
                $target_file = $target_dir . $new_filename;
                
                // Upload the file
                if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_file)) {
                    $category_image = $target_file;
                    $category_image_type = $_FILES['category_image']['type'];
                } else {
                    $message = "Failed to upload image.";
                    $messageType = "error";
                }
            } else {
                $message = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF, WEBP, BMP, SVG, TIFF, ICO, HEIC";
                $messageType = "error";
            }
        }

        if (empty($message)) { // Proceed only if no validation errors
            $stmt = $conn->prepare("INSERT INTO categories (category_name, category_description, category_image, category_image_type, created_on) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $category_name, $category_description, $category_image, $category_image_type);

            if ($stmt->execute()) {
                $message = "Category added successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}

$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category</title>
    <link rel="stylesheet" href="css/add_categories.css">
</head>
<body>

<div class="dashboard-container">

        <h2>Admin Panel</h2>
        <ul>
            <li><a href="../admin/dashboard/admin_dashboard.php">Dashboard</a></li>
            <li><a href="../admin/users/manage_users.php">Manage Users</a></li>
            <li><a href="../admin/categories/manage_categories.php">Manage Categories</a></li>
            <li><a href="../admin/products/admin_add_product.php">Add Products</a></li>
            <li><a href="../admin/orders/manage_orders.php">View Orders</a></li>
            <li><a href="../admin/reports/reports.php">Reports</a></li>
        </ul>
        <div class="logout-btn">
            <a href="../auth/logout.php">Logout</a>
        </div>
    

    <div class="main-content">
        <h1>Add Category</h1>

        <?php if(!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category_name">Category Name:</label>
                    <input type="text" id="category_name" name="category_name" required>
                </div>

                <div class="form-group">
                    <label for="category_description">Category Description:</label>
                    <textarea id="category_description" name="category_description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="category_image">Category Image:</label>
                    <input type="file" id="category_image" name="category_image" accept="image/*">
                    <small>Allowed formats: JPG, JPEG, PNG, GIF, WEBP, BMP, SVG, TIFF, ICO, HEIC</small>
                </div>

                <button type="submit" class="submit-btn">Add Category</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>