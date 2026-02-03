<?php
session_start();
include 'db_connection.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $productId = isset($_GET['id']) ? $_GET['id'] : 0;
    
    switch ($action) {
        case 'remove':
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
            }
            break;
            
        case 'increase':
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]++;
            }
            break;
            
        case 'decrease':
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]--;
                if ($_SESSION['cart'][$productId] <= 0) {
                    unset($_SESSION['cart'][$productId]);
                }
            }
            break;
            
        case 'clear':
            $_SESSION['cart'] = [];
            break;
    }
    
    // Redirect back to cart page to prevent form resubmission
    header('Location: cart.php');
    exit;
}

// Get cart items from database
$cartItems = [];
$totalPrice = 0;

if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $query = "SELECT product_id, product_name, product_price, product_image, image_type FROM products WHERE product_id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    
    // Bind parameters dynamically
    $types = str_repeat('i', count($productIds));
    $stmt->bind_param($types, ...$productIds);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$row['product_id']];
        $subtotal = $row['product_price'] * $quantity;
        $totalPrice += $subtotal;
        
        $cartItems[] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'price' => $row['product_price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'image' => $row['product_image'],
            'image_type' => $row['image_type']
        ];
    }
}

// Count total items in cart
$totalItems = array_sum($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - TechShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/cart.css">
</head>
<body>
    <!-- Header -->
    <div id="nav">
      <div class="nav1">
        <div class="logo">
          <img src="assets/home/image/logo.png" alt="" />
        </div>
        <div class="nav-item">
          <ul id="nav-item">
            <a href="index.php"><li>Home</li> </a>
            <a href="products.php"><li>Product</li> </a>
            <a href="about.php"><li>About Us</li> </a>
            <a href="contact.php"><li>Contact Us</li></a>
            <?php if (!isset($_SESSION['user_id'])): ?>
  <a href="sign-in.php">
    <li>Sign In</li>
  </a>
<?php endif; ?>
            <a href="rent.php"><li>Rent</li></a>  
          </ul>
        </div>
      </div>
      <div class="nav2">
        <div class="nav2-icon">
          <i class="fa-regular fa-heart"></i>
          <a href="cart.php" class="cart-link">
            <i class="fa-solid fa-cart-shopping"></i>
            <?php if ($totalItems > 0): ?>
                <span class="cart-count"><?php echo $totalItems; ?></span>
            <?php endif; ?>
        </a>
          <a href="user_dashboard.php"><i class="fa-solid fa-user"></i></a>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1 class="page-title">Shopping Cart</h1>
            
            <?php if (!empty($cartItems)): ?>
                <div class="cart-container">
                    <div class="cart-header">
                        <div>Image</div>
                        <div>Product</div>
                        <div>Price</div>
                        <div>Quantity</div>
                        <div>Subtotal</div>
                        <div></div>
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div>
                                <?php
                                if (!empty($item['image'])) {
                                    // Check if the product_image is a file path (starts with 'uploads/')
                                    if (strpos($item['image'], 'uploads/') === 0) {
                                        // It's a file path, display the image using the path
                                        echo '<img src="' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '" class="cart-item-image">';
                                    } else {
                                        // It's a BLOB, use base64 encoding (for backward compatibility)
                                        echo '<img src="data:' . $item['image_type'] . ';base64,' . base64_encode($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '" class="cart-item-image">';
                                    }
                                } else {
                                    echo '<img src="assets/no-image.png" alt="No Image" class="cart-item-image">';
                                }
                                ?>
                            </div>
                            <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="cart-item-price">₹<?php echo number_format($item['price']); ?></div>
                            <div class="cart-quantity-control">
                                <a href="cart.php?action=decrease&id=<?php echo $item['id']; ?>" class="cart-quantity-btn">
                                    <i class="fas fa-minus"></i>
                                </a>
                                <span class="cart-quantity-input"><?php echo $item['quantity']; ?></span>
                                <a href="cart.php?action=increase&id=<?php echo $item['id']; ?>" class="cart-quantity-btn">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                            <div class="cart-item-subtotal">₹<?php echo number_format($item['subtotal']); ?></div>
                            <div>
                                <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" class="cart-item-remove">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-footer">
                        <div class="cart-total">
                            Total: ₹<?php echo number_format($totalPrice); ?>
                        </div>
                        <div class="cart-actions">
                            <a href="cart.php?action=clear" class="btn btn-secondary">
                                <i class="fas fa-trash"></i> Clear Cart
                            </a>
                            <a href="checkout.php" class="btn btn-primary">
                                <i class="fas fa-lock"></i> Checkout
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="continue-shopping">
                    <a href="products.php" >
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any products to your cart yet.</p>
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'pages/footer.php'; ?>
   
</body>
</html>

<?php $conn->close(); ?>