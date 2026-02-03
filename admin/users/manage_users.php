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

// Fetch all users
$sql = "SELECT * FROM users ORDER BY created_on DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/manage_user.css">
    <script>
        function toggleAll(source) {
            var checkboxes = document.querySelectorAll('.userCheckbox');
            checkboxes.forEach(checkbox => checkbox.checked = source.checked);
            toggleBulkDeleteBtn();
        }

        function toggleBulkDeleteBtn() {
            var checkboxes = document.querySelectorAll('.userCheckbox:checked');
            document.getElementById('bulkDeleteBtn').style.display = checkboxes.length > 0 ? 'inline-block' : 'none';
        }

        function confirmBulkDelete() {
            var checkboxes = document.querySelectorAll('.userCheckbox:checked');
            if (checkboxes.length === 0) {
                alert("No users selected for deletion!");
                return;
            }
            if (confirm("Are you sure you want to delete the selected users?")) {
                document.getElementById('bulkDeleteForm').submit();
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.userCheckbox').forEach(checkbox => {
                checkbox.addEventListener('change', toggleBulkDeleteBtn);
            });
        });
    </script>
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
        <div>
            <a href="logout.php" class="delete-btn">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Manage Users</h1>

        <form id="bulkDeleteForm" action="bulk_delete.php" method="POST">
            <button type="button" id="bulkDeleteBtn" class="bulk-btn" onclick="confirmBulkDelete()">Delete Selected</button>

            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Image</th>
                        <th>Created On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><input type='checkbox' class='userCheckbox' name='selected_users[]' value='<?= $row["user_id"] ?>'></td>
                            <td><?= $row["user_id"] ?></td>
                            <td><?= $row["full_name"] ?></td>
                            <td><?= $row["email_address"] ?></td>
                            <td><?= $row["user_role"] ?></td>
                            <td>
                                <?php if (!empty($row["user_image"])) { ?>
                                    <img src='uploads/<?= $row["user_image"] ?>' class='user-img'>
                                <?php } else { echo "No image"; } ?>
                            </td>
                            <td><?= $row["created_on"] ?></td>
                            <td>
                                <a href='edit_user.php?id=<?= $row["user_id"] ?>'><button type="button" class='action-btn'>Edit</button></a>
                                <a href='delete_user.php?id=<?= $row["user_id"] ?>' onclick='return confirm("Delete this user?")'>
                                    <button type="button" class='delete-btn'>Delete</button>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>