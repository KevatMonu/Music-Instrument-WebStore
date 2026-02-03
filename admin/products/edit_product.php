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

// Initialize variables
$product_id = $product_name = $product_description = $category_ref = $product_price = $rental_cost = $stock_quantity = $product_image = "";
$error_message = "";
$success_message = "";

// Get product ID from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = $_GET['id'];
    
    // Fetch product details
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $product_name = $row['product_name'];
        $product_description = $row['product_description'];
        $category_ref = $row['category_ref'];
        $product_price = $row['product_price'];
        $rental_cost = $row['rental_cost'];
        $stock_quantity = $row['stock_quantity'];
        $product_image = $row['product_image']; // Changed from 'image_url' to 'product_image'
    } else {
        echo "<script>alert('Product not found!'); window.location.href='manage_products.php';</script>";
        exit();
    }
    $stmt->close();
} else {
    echo "<script>alert('Invalid product ID!'); window.location.href='manage_products.php';</script>";
    exit();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $product_name = trim($_POST['product_name']);
    $product_description = trim($_POST['product_description']);
    $category_ref = $_POST['category_ref'];
    $product_price = floatval($_POST['product_price']);
    $rental_cost = floatval($_POST['rental_cost']);
    $stock_quantity = intval($_POST['stock_quantity']);
    
    // Validation
    if (empty($product_name)) {
        $error_message = "Product name is required.";
    } elseif (empty($category_ref)) {
        $error_message = "Category is required.";
    } elseif ($product_price < 0) {
        $error_message = "Price cannot be negative.";
    } elseif ($rental_cost < 0) {
        $error_message = "Rental cost cannot be negative.";
    } elseif ($stock_quantity < 0) {
        $error_message = "Stock quantity cannot be negative.";
    } else {
        // Handle image upload if a new image is provided
        if (isset($_FILES['product_image']) && $_FILES['product_image']['size'] > 0) {
            $target_dir = "uploads/";
            $file_extension = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check if image file is an actual image
            $check = getimagesize($_FILES["product_image"]["tmp_name"]);
            if ($check === false) {
                $error_message = "File is not an image.";
            } 
            // Check file size (limit to 5MB)
            elseif ($_FILES["product_image"]["size"] > 5000000) {
                $error_message = "File is too large. Maximum size is 5MB.";
            }
            // Allow only certain file formats
            elseif (!in_array($file_extension, ["jpg", "jpeg", "png", "gif"])) {
                $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
            // Try to upload file
            elseif (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                // If upload successful, update image URL
                $product_image = $target_file; // Changed from 'image_url' to 'product_image'
            } else {
                $error_message = "There was an error uploading your file.";
            }
        }
        
        // If no errors, proceed with update
        if (empty($error_message)) {
            // Update product in database
            $sql = "UPDATE products SET 
                    product_name = ?, 
                    product_description = ?, 
                    category_ref = ?, 
                    product_price = ?, 
                    rental_cost = ?, 
                    stock_quantity = ?, 
                    product_image = ?,  <!-- Changed from 'image_url' to 'product_image' -->
                    last_updated = NOW() 
                    WHERE product_id = ?";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiddisi", 
                $product_name, 
                $product_description, 
                $category_ref, 
                $product_price, 
                $rental_cost, 
                $stock_quantity, 
                $product_image,  // Changed from 'image_url' to 'product_image'
                $product_id
            );
            
            if ($stmt->execute()) {
                $success_message = "Product updated successfully!";
            } else {
                $error_message = "Error updating product: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all categories for the dropdown
$categories = [];
$sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="css/edit_product.css">
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
            <li><a href="manage_orders.php">View Orders</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <h1>Edit Product</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="product_description">Description:</label>
                <textarea id="product_description" name="product_description" rows="4"><?php echo htmlspecialchars($product_description); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="category_ref">Category:</label>
                <select id="category_ref" name="category_ref" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_ref == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="product_price">Price ($):</label>
                <input type="number" id="product_price" name="product_price" step="0.01" value="<?php echo $product_price; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="rental_cost">Rental Cost ($/day):</label>
                <input type="number" id="rental_cost" name="rental_cost" step="0.01" value="<?php echo $rental_cost; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo $stock_quantity; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="product_image">Product Image:</label>
                <?php if (!empty($product_image)): ?>  <!-- Changed from 'image_url' to 'product_image' -->
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars($product_image); ?>" alt="Current product image" width="100">
                        <p>Current image: <?php echo htmlspecialchars(basename($product_image)); ?></p>
                    </div>
                <?php endif; ?>
                <input type="file" id="product_image" name="product_image" accept="image/*">
                <p class="help-text">Leave empty to keep current image. Accept formats: JPG, PNG, GIF (Max: 5MB)</p>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="save-btn">Update Product</button>
                <a href="manage_products.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>