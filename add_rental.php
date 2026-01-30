 <?php
session_start();

// Database Connection
$conn = new mysqli("localhost", "root", "", "musicstore_database");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$products = [];

// Fetch rentable products
$query = "SELECT product_id, product_name, rental_cost, stock_quantity FROM products WHERE rental_cost IS NOT NULL AND rental_cost > 0 AND stock_quantity > 0";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Process rental form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $order_ref = intval($_POST['order_ref']); // Order reference number
    $rental_start = $_POST['rental_start'];
    $rental_end = $_POST['rental_end'];
    
    // Validate inputs
    if (empty($product_id) || empty($order_ref) || empty($rental_start) || empty($rental_end)) {
        $message = "<p class='error-message'>All fields are required!</p>";
    } else {
        // Check if selected dates are valid
        $today = date('Y-m-d');
        $start_date = new DateTime($rental_start);
        $end_date = new DateTime($rental_end);
        
        if ($rental_start < $today) {
            $message = "<p class='error-message'>Rental start date cannot be in the past!</p>";
        } elseif ($rental_end <= $rental_start) {
            $message = "<p class='error-message'>Rental end date must be after the start date!</p>";
        } else {
            // Check if product is available (has stock)
            $product_query = "SELECT stock_quantity FROM products WHERE product_id = ?";
            $stmt = $conn->prepare($product_query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if ($product && $product['stock_quantity'] > 0) {
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Insert rental record
                    $stmt = $conn->prepare("INSERT INTO rentals (order_ref, rental_start, rental_end, rental_status) VALUES (?, ?, ?, 'active')");
                    $stmt->bind_param("iss", $order_ref, $rental_start, $rental_end);
                    
                    if ($stmt->execute()) {
                        // Reduce product stock
                        $update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - 1 WHERE product_id = ?");
                        $update_stock->bind_param("i", $product_id);
                        $update_stock->execute();
                        
                        $conn->commit();
                        $message = "<p class='success-message'>Rental created successfully!</p>";
                    } else {
                        throw new Exception("Error creating rental: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "<p class='error-message'>" . $e->getMessage() . "</p>";
                }
            } else {
                $message = "<p class='error-message'>The selected product is not available for rent!</p>";
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
    <title>Create Rental</title>
    <link rel="stylesheet" href="css/add_product.css">
    <style>
        .date-inputs {
            display: flex;
            gap: 10px;
        }
        .date-inputs input {
            flex: 1;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Create New Rental</h2>
    <?php echo $message; ?>
    
    <?php if (empty($products)): ?>
        <p>No products are currently available for rent.</p>
    <?php else: ?>
        <form action="" method="POST">
            <select name="product_id" required>
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['product_id']; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?> - 
                        $<?php echo number_format($product['rental_cost'], 2); ?>/day
                        (<?php echo $product['stock_quantity']; ?> available)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="number" name="order_ref" placeholder="Order Reference Number" required>
            
            <div class="date-inputs">
                <input type="date" name="rental_start" min="<?php echo date('Y-m-d'); ?>" required placeholder="Rental Start Date">
                <input type="date" name="rental_end" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required placeholder="Rental End Date">
            </div>
            
            <button type="submit">Create Rental</button>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.querySelector('input[name="rental_start"]');
    const endDate = document.querySelector('input[name="rental_end"]');
    
    startDate.addEventListener('change', function() {
        // Set minimum end date to be one day after start date
        const nextDay = new Date(this.value);
        nextDay.setDate(nextDay.getDate() + 1);
        
        const formattedDate = nextDay.toISOString().split('T')[0];
        endDate.min = formattedDate;
        
        // If current end date is before new min, update it
        if (endDate.value && endDate.value < formattedDate) {
            endDate.value = formattedDate;
        }
    });
});
</script>
</body>
</html>