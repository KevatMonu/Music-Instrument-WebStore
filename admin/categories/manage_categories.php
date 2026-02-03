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

// Fetch all categories
$sql = "SELECT * FROM categories ORDER BY created_on DESC";
$result = $conn->query($sql);

$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="css/manage_cat.css">
    
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
        <h1>Manage Categories</h1>
        <a href="admin_add_categories.php"><button class="add-btn">Add Category</button></a>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Created On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row["category_id"] . "</td>";
                        echo "<td>" . $row["category_name"] . "</td>";
                        echo "<td>" . $row["category_description"] . "</td>";
                        echo "<td>";
                        
                        // Check if image path exists and file exists
                        if(!empty($row["category_image"]) && file_exists($row["category_image"])) {
                            // Direct file path approach
                            echo "<img src='" . $row["category_image"] . "' alt='" . $row["category_name"] . "' class='category-img'>";
                        } else {
                            // Check if we have legacy BLOB data
                            if(isset($row["category_image"]) && is_resource($row["category_image"])) {
                                // Handle BLOB data from legacy records
                                $image_data = $row["category_image"];
                                $mime_type = $row["category_image_type"] ?? 'image/jpeg';
                                $base64 = base64_encode(stream_get_contents($image_data));
                                echo "<img src='data:$mime_type;base64,$base64' alt='" . $row["category_name"] . "' class='category-img'>";
                            } else {
                                echo "No image";
                            }
                        }
                        
                        echo "</td>";
                        echo "<td>" . $row["created_on"] . "</td>";
                        echo "<td>
                                <a href='edit_category.php?id=" . $row["category_id"] . "'><button class='action-btn'>Edit</button></a>
                                <a href='delete_category.php?id=" . $row["category_id"] . "' onclick='return confirm(\"Are you sure you want to delete this category?\")'><button class='action-btn delete-btn'>Delete</button></a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center'>No categories found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>