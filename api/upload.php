<?php
session_start();
$conn = new mysqli("localhost", "root", "", "music_store");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $imagePath = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = "assets/products/"; // Ensure this folder exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create folder if not exists
        }

        $imageName = basename($_FILES['image']['name']);
        $imagePath = $uploadDir . $imageName;
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        $fileExtension = pathinfo($imageName, PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            die("<script>alert('Invalid file format! Only JPG, PNG, GIF allowed.'); window.history.back();</script>");
        }

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            die("<script>alert('Failed to upload image.'); window.history.back();</script>");
        }
    } else {
        die("<script>alert('Please upload an image.'); window.history.back();</script>");
    }

    if (!empty($name) && $price > 0 && !empty($imagePath)) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $name, $description, $price, $imagePath);

        if ($stmt->execute()) {
            echo "<script>alert('Product added successfully!'); window.location.href='products.php';</script>";
        } else {
            echo "<script>alert('Error adding product! Try again.'); window.history.back();</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Please fill all fields correctly.'); window.history.back();</script>";
    }
}

$conn->close();
?>
