<?php
session_start();
$conn = new mysqli("localhost", "root", "", "musicstore_database");

// Check Connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Validate Order ID
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("Invalid order.");
}

$order_id = intval($_GET['order_id']);

// Fetch Order Details
$order_query = "SELECT * FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

// If order not found
if (!$order) {
    die("Order not found.");
}

// Fetch Order Items
$items_query = "SELECT oi.*, p.name AS product_name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #f8f8f8; padding: 20px; }
        .container { max-width: 700px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f4f4f4; }
        .btn { display: inline-block; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; transition: 0.3s; }
        .btn:hover { background: #0056b3; }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Order Details</h2>
    <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
    <p><strong>Total Price:</strong> ₹<?php echo number_format($order['total_price'], 2); ?></p>
    <p><strong>Date:</strong> <?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></p>

    <h3>Ordered Items</h3>
    <table>
        <tr>
            <th>Product</th>
            <th>Image</th>
            <th>Quantity</th>
            <th>Price</th>
        </tr>
        <?php while ($item = $items_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><img class="product-img" src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>"></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td>₹<?php echo number_format($item['price'], 2); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <a href="order_history.php" class="btn">Back to Orders</a>
</div>

</body>
</html>
