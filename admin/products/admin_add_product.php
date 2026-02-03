<?php
session_start();
require 'db_connection.php'; // Include your database connection file

// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add debugging to see what's being submitted
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Check if keys exist before accessing them
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $category_ref = isset($_POST['category']) ? intval($_POST['category']) : 0;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;
    
    // Debugging: Log values after processing
    error_log("Processed name: '$name'");
    error_log("Processed category_ref: '$category_ref'");
    error_log("Processed price: '$price'");
    
    // Check if $_FILES['image'] exists and is uploaded properly
    $imageUploaded = isset($_FILES['image']) && 
                     isset($_FILES['image']['name']) && 
                     !empty($_FILES['image']['name']) && 
                     $_FILES['image']['error'] === UPLOAD_ERR_OK;
    
    error_log("Image uploaded check: " . ($imageUploaded ? "YES" : "NO"));
    if (isset($_FILES['image'])) {
        error_log("Image error code: " . $_FILES['image']['error']);
    }
    
    // New rental fields
    $is_rentable = isset($_POST['is_rentable']) ? 1 : 0;
    $rental_cost = isset($_POST['rental_cost']) && !empty($_POST['rental_cost']) ? 
                   floatval($_POST['rental_cost']) : NULL;
    
    // Modified validation with better error messages
    $errors = [];
    
    // Improved validation checks
    if (empty($name)) $errors[] = "Product name is required.";
    if ($price <= 0) $errors[] = "Valid price is required.";
    if ($category_ref <= 0) $errors[] = "Category selection is required.";
    
    // More detailed image upload validation
    if (!$imageUploaded) {
        if (!isset($_FILES['image']) || !isset($_FILES['image']['name']) || empty($_FILES['image']['name'])) {
            $errors[] = "Product image is required.";
        } else if (isset($_FILES['image']['error']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed with code: " . $_FILES['image']['error'];
            // Add more specific error message based on the error code
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errors[] = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "The uploaded file was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errors[] = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errors[] = "Failed to write file to disk";
                    break;
                default:
                    $errors[] = "Unknown upload error";
            }
        }
    }
    
    // If is_rentable is checked but no rental cost provided
    if ($is_rentable && (is_null($rental_cost) || $rental_cost <= 0)) {
        $errors[] = "Rental cost must be greater than zero for rentable products.";
    }
    
    if (!empty($errors)) {
        $message = "<div class='error-message'><ul>";
        foreach ($errors as $error) {
            $message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $message .= "</ul></div>";
        error_log("Validation errors: " . implode(", ", $errors));
    } else {
        error_log("Validation passed, proceeding with image processing");
        
        // Process image only if it was uploaded
        $image = $_FILES['image'];
        // Allowed Image Formats
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $imageExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

        if (!in_array($imageExtension, $allowedExtensions)) {
            $message = "<p class='error-message'>Invalid image format! Only JPG, PNG, GIF, WEBP, SVG allowed.</p>";
        } elseif ($image['size'] > 500000000) { // 5MB limit
            $message = "<p class='error-message'>File is too large! Max size: 5MB.</p>";
        } else {
            error_log("Image validation passed, proceeding with file storage");
            
            // Create uploads directory if it doesn't exist
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Generate unique filename to prevent overwriting
            $filename = uniqid() . '_' . basename($image['name']);
            $target_file = $target_dir . $filename;
            $imageType = $image['type'];
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Move uploaded file to target directory
                if (move_uploaded_file($image['tmp_name'], $target_file)) {
                    // Insert Product into Database with file path instead of blob
                    $stmt = $conn->prepare("INSERT INTO products (product_name, product_description, category_ref, product_price, rental_cost, stock_quantity, product_image, image_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssiddisd", $name, $description, $category_ref, $price, $rental_cost, $stock_quantity, $target_file, $imageType);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        $message = "<p class='success-message'>Product added successfully!</p>";
                        error_log("Product added successfully");
                        
                        // Reset form after successful submission
                        $name = $description = '';
                        $category_ref = $price = $rental_cost = $stock_quantity = 0;
                        $is_rentable = 0;
                    } else {
                        throw new Exception("Error adding product: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Failed to move uploaded file.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $message = "<p class='error-message'>" . $e->getMessage() . "</p>";
                error_log("Exception during processing: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="css/add_product.css">
</head>
<body>
<div class="container">
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
    
    <div class="content">
        <h2>Add New Product</h2>
        <?php echo $message; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name" class="required">Product Name</label>
                <input type="text" id="name" name="name" placeholder="Enter product name" required 
                       value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Product Description</label>
                <textarea id="description" name="description" placeholder="Enter product description"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="category" class="required">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php
                    // Fetch categories dynamically
                    $category_query = "SELECT category_id, category_name FROM categories";
                    $result = $conn->query($category_query);
                    
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $selected = (isset($category_ref) && $category_ref == $row['category_id']) ? 'selected' : '';
                            echo "<option value='{$row['category_id']}' {$selected}>{$row['category_name']}</option>";
                        }
                    } else {
                        echo "<option value=''>Error loading categories</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="price" class="required">Purchase Price</label>
                <input type="number" id="price" step="0.01" name="price" placeholder="Enter price" required min="0.01"
                       value="<?php echo isset($price) && $price > 0 ? htmlspecialchars($price) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" placeholder="Enter stock quantity" min="0"
                       value="<?php echo isset($stock_quantity) ? htmlspecialchars($stock_quantity) : '0'; ?>">
            </div>
            
            <div class="form-group">
                <label for="image" class="required">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*" required>
                <small>Allowed formats: JPG, PNG, GIF, WEBP, SVG. Max size: 5MB</small>
            </div>
            
            <!-- Rental Options Section -->
    
            
            <button type="submit">Add Product</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isRentableCheckbox = document.getElementById('is_rentable');
    const rentalOptions = document.getElementById('rental-options');
    const rentalCostInput = document.getElementById('rental_cost');
    const rentalCostLabel = document.getElementById('rental_cost_label');
    
    // Initial state setup
    if(isRentableCheckbox.checked) {
        rentalOptions.style.display = 'block';
        rentalCostInput.setAttribute('required', 'required');
        rentalCostLabel.classList.add('required');
    }
    
    // Change event handler
    isRentableCheckbox.addEventListener('change', function() {
        if(this.checked) {
            rentalOptions.style.display = 'block';
            rentalCostInput.setAttribute('required', 'required');
            rentalCostLabel.classList.add('required');
        } else {
            rentalOptions.style.display = 'none';
            rentalCostInput.removeAttribute('required');
            rentalCostLabel.classList.remove('required');
        }
    });
});
</script>
</body>
</html>