<?php
require_once "db_connection.php"; 

$categoryFilter = isset($_POST['categories']) ? implode(",", array_map('intval', $_POST['categories'])) : '';
$maxPrice = isset($_POST['maxPrice']) ? floatval($_POST['maxPrice']) : 10000;
$sortBy = isset($_POST['sortBy']) ? $_POST['sortBy'] : '';
$searchQuery = isset($_POST['searchQuery']) ? "%" . $_POST['searchQuery'] . "%" : "%";

// SQL Query with Filters
$query = "SELECT * FROM products WHERE product_price <= ? AND product_name LIKE ?";
if (!empty($categoryFilter)) {
    $query .= " AND category_ref IN ($categoryFilter)";
}

// Sorting
if ($sortBy === "price_low") {
    $query .= " ORDER BY product_price ASC";
} elseif ($sortBy === "price_high") {
    $query .= " ORDER BY product_price DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("ds", $maxPrice, $searchQuery);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='product'>
                <img src='data:image/jpeg;base64," . base64_encode($row['product_image']) . "' alt='" . htmlspecialchars($row['product_name']) . "'>
                <h3>" . htmlspecialchars($row['product_name']) . "</h3>
                <p>₹" . number_format($row['product_price'], 2) . "</p>
                <button class='add-to-cart' data-id='" . $row['product_id'] . "'>Add to Cart</button>
              </div>";
    }
} else {
    echo "<p>No products found.</p>";
}

$stmt->close();
$conn->close();
?>
