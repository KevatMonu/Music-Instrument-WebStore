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

$message = "";
$messageType = "";

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_categories.php");
    exit();
}

$category_id = $_GET['id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name'] ?? '');
    $category_description = trim($_POST['category_description'] ?? '');
    
    // Validate fields
    if (empty($category_name) || empty($category_description)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Get current image path
        $query = "SELECT category_image FROM categories WHERE category_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_image = $result->fetch_assoc()['category_image'];
        $stmt->close();
        
        // Initialize variables for update
        $category_image = $current_image;
        $category_image_type = NULL;
        $image_updated = false;
        
        // Check if new image is uploaded
        if (!empty($_FILES['category_image']['name']) && $_FILES['category_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['category_image']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($filetype, $allowed)) {
                // Create uploads directory if it doesn't exist
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                // Generate a unique filename
                $new_filename = uniqid() . '_' . $filename;
                $target_file = $target_dir . $new_filename;
                
                // Upload the file
                if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_file)) {
                    // Delete old image if exists and it's not the default
                    if (!empty($current_image) && file_exists($current_image) && strpos($current_image, 'default') === false) {
                        unlink($current_image);
                    }
                    
                    $category_image = $target_file;
                    $category_image_type = $_FILES['category_image']['type'];
                    $image_updated = true;
                } else {
                    $message = "Failed to upload image.";
                    $messageType = "error";
                }
            } else {
                $message = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
                $messageType = "error";
            }
        }

        if (empty($message)) { // Proceed only if no validation errors
            if ($image_updated) {
                $stmt = $conn->prepare("UPDATE categories SET category_name = ?, category_description = ?, category_image = ?, category_image_type = ? WHERE category_id = ?");
                $stmt->bind_param("ssssi", $category_name, $category_description, $category_image, $category_image_type, $category_id);
            } else {
                $stmt = $conn->prepare("UPDATE categories SET category_name = ?, category_description = ? WHERE category_id = ?");
                $stmt->bind_param("ssi", $category_name, $category_description, $category_id);
            }

            if ($stmt->execute()) {
                $message = "Category updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}

// Fetch category data
$stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_categories.php");
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    <link rel="stylesheet" href="css/add_categories.css">
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_categories.php">Manage Categories</a></li>
            <li><a href="manage_products.php">Manage Products</a></li>
            <li><a href="orders.php">View Orders</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Edit Category</h1>

        <?php if(!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category_name">Category Name:</label>
                    <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category['category_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_description">Category Description:</label>
                    <textarea id="category_description" name="category_description" required><?php echo htmlspecialchars($category['category_description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category_image">Category Image:</label>
                    <?php if(!empty($category['category_image']) && file_exists($category['category_image'])): ?>
                        <div class="current-image">
                            <p>Current Image:</p>
                            <img src="<?php echo $category['category_image']; ?>" alt="Current Image" style="max-width: 200px; max-height: 200px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="category_image" name="category_image" accept="image/*">
                    <small>Leave empty to keep current image</small>
                </div>

                <button type="submit" class="submit-btn">Update Category</button>
                <a href="manage_categories.php" class="cancel-btn">Cancel</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>