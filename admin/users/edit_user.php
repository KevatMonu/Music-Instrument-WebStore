<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "musicstore_database", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid user ID!'); window.location.href='manage_users.php';</script>";
    exit();
}

$user_id = $_GET['id'];

// Fetch user details
$sql = "SELECT full_name, email_address, user_role, COALESCE(phone_number, '') AS phone_number, COALESCE(user_address, '') AS user_address, user_image FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "<script>alert('User not found!'); window.location.href='manage_users.php';</script>";
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name']);
    $email_address = trim($_POST['email_address']);
    $phone_number = trim($_POST['phone_number'] ?? '');
    $user_address = trim($_POST['address'] ?? '');

    // Handle image upload
    if (!empty($_FILES["user_image"]["name"])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["user_image"]["name"]);
        move_uploaded_file($_FILES["user_image"]["tmp_name"], $target_file);
        $update_image = ", user_image = '" . basename($_FILES["user_image"]["name"]) . "'";
    } else {
        $update_image = "";
    }

    // Update user details
    $update_sql = "UPDATE users SET full_name = ?, email_address = ?, phone_number = ?, user_address = ? $update_image WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssi", $full_name, $email_address, $phone_number, $user_address, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('User updated successfully!'); window.location.href='manage_users.php';</script>";
    } else {
        echo "<script>alert('Error updating user!');</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="css/edit_user.css">
</head>
<body>

<div class="edit-container">
    <h2>Edit User</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Email Address</label>
        <input type="email" name="email_address" value="<?= htmlspecialchars($user['email_address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>phone_number Number</label>
        <input type="tel" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Address</label>
        <input type="text" name="address" value="<?= htmlspecialchars($user['user_address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Profile Image</label>
        <?php if (!empty($user["user_image"])) { ?>
            <img src="uploads/<?= $user['user_image'] ?>" class="profile-img">
        <?php } ?>
        <input type="file" name="user_image">

        <button type="submit">Update User</button>
    </form>
</div>

</body>
</html>
