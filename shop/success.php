<?php
session_start();
$conn = new mysqli("localhost", "root", "", "music_store");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get order_id from URL
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("Invalid Order ID. <a href='index.php'>Go Home</a>");
}

$order_id = intval($_GET['order_id']);

// Fetch order details
$order_query = "SELECT * FROM orders WHERE id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found. <a href='index.php'>Go Home</a>");
}

// Fetch ordered products
$item_query = "SELECT oi.*, p.name AS product_name FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$stmt = $conn->prepare($item_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #f8f8f8; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #28a745; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; }
        th { background: #f4f4f4; }
        .total { font-weight: bold; color: #333; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="container">
    <h2>Order Confirmed!</h2>
    <p>Thank you, <strong><?php echo htmlspecialchars($order['name']); ?></strong>, for your purchase.</p>
    <p>Your order ID: <strong>#<?php echo $order['id']; ?></strong></p>
    <p>Email: <?php echo htmlspecialchars($order['email']); ?></p>
    <p>Shipping Address: <?php echo htmlspecialchars($order['address']); ?></p>
    <p>Payment Method: <strong><?php echo htmlspecialchars($order['payment_method']); ?></strong></p>

    <h3>Order Details:</h3>
    <table>
        <tr>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Price</th>
        </tr>
        <?php while ($item = $items_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
            <td><?php echo $item['quantity']; ?></td>
            <td>₹<?php echo number_format($item['price'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="2" class="total">Total Price:</td>
            <td class="total">₹<?php echo number_format($order['total_price'], 2); ?></td>
        </tr>
    </table>

    <a href="products.php" class="btn">Return to Homepage</a>
</div>

</body>
</html>
