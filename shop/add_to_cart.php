<?php
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// Check if valid data was sent
if (isset($_POST['action'], $_POST['id']) && $_POST['action'] === 'add' && is_numeric($_POST['id'])) {
  $id = intval($_POST['id']);
  
  // Add to cart
  $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
  
  // Return total number of items in cart
  echo array_sum($_SESSION['cart']);
} else {
  // Return error
  http_response_code(400);
  echo "Invalid request";
}
?>