<?php session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "musicstore_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
// Modified query to match your actual database structure
$stmt = $conn->prepare("SELECT full_name, email_address, created_on FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$full_name = $user['full_name'];
$email = $user['email_address']; 
// Using created_on as a substitute for last_login since there's no last_login column
$last_login = $user['created_on'] ?? 'First login';

// Get order count
$stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_ref = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order_data = $result->fetch_assoc();
$order_count = $order_data['order_count'];

// Get cart count
$stmt = $conn->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_ref = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_data = $result->fetch_assoc();
$cart_count = $cart_data['cart_count'];

// Get recent orders - FIXED: Changed column names to match the database structure
$stmt = $conn->prepare("SELECT o.order_id, o.order_created, o.total_cost AS total_amount, o.order_status AS status FROM orders o WHERE o.user_ref = ? ORDER BY o.order_created DESC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result();

// Get recommended products
$stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.product_price AS price, p.product_image AS image_url FROM products p ORDER BY RAND() LIMIT 4");
$stmt->execute();
$recommended_products = $stmt->get_result();

// Get special offers - Adjusted to use product_price instead of price
$stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.product_price AS price, 10 AS discount_percent, p.product_image AS image_url FROM products p ORDER BY RAND() LIMIT 3");
$stmt->execute();
$special_offers = $stmt->get_result();

$conn->close();

// Calculate savings if applicable
function calculateSavings($price, $discount_percent) {
    return ($price * $discount_percent) / 100;
}

// Format date
function formatDate($date) {
    return date("M d, Y", strtotime($date));
}

// Get status badge class
function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'delivered':
        case 'completed':
            return 'status-delivered';
        case 'shipped':
            return 'status-shipped';
        case 'processing':
            return 'status-processing';
        case 'pending':
        case 'cancelled':
            return 'status-pending';
        default:
            return 'status-default';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | Music Store</title>
    <link rel="stylesheet" href="https://unpkg.com/lucide-static@latest/font/lucide.css">
    <link rel="stylesheet" href="css/user_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Music Store</h2>
            </div>
            <nav>
                <ul class="nav-list">
                    <li><a href="user_dashboard.php" class="nav-link active"><i class="lucide-home"></i> <span>Dashboard</span></a></li>
                    <li><a href="products.php" class="nav-link"><i class="lucide-package"></i> <span>Browse Products</span></a></li>
                    <li><a href="cart.php" class="nav-link"><i class="lucide-shopping-cart"></i> <span>My Cart</span></a></li>
                    <li><a href="user_order_detail.php" class="nav-link"><i class="lucide-clipboard-list"></i> <span>Ordertracking</span></a></li>
                    <li><a href="ordertracking.php" class="nav-link"><i class="lucide-user"></i> <span>My Profile</span></a></li>
                    <li><a href="profile.php" class="nav-link"><i class="lucide-user"></i> <span>My Profile</span></a></li>
                    <li><a href="support.php" class="nav-link"><i class="lucide-help-circle"></i> <span>Help & Support</span></a></li>
                </ul>
            </nav>
            <div class="logout-section">
                <a href="logout.php" class="logout-btn"><i class="lucide-log-out"></i> <span>Logout</span></a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="welcome-banner">
                <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?>! 👋</h1>
                <p>Discover new instruments, accessories, and exclusive deals.</p>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <span class="user-email"><?php echo htmlspecialchars($email); ?></span>
                        <span class="last-login">Joined: <?php echo htmlspecialchars(formatDate($last_login)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <a href="user_order_detail.php" class="dashboard-card card-orders">
                    <div class="card-content">
                        <div class="card-info">
                            <h3>Total Orders</h3>
                            <div class="value"><?php echo $order_count; ?></div>
                        </div>
                        <div class="card-icon"><i class="lucide-package-check"></i></div>
                    </div>
                </a>

                
            </div>

            <!-- Recent Orders Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Orders</h2>
                    <a href="user_order_detail.php" class="view-all">View All <i class="lucide-chevron-right"></i></a>
                </div>
                
                <?php if ($recent_orders->num_rows > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td class="order-id">#<?php echo $order['order_id']; ?></td>
                            <td><?php echo formatDate($order['order_created']); ?></td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><span class="status-badge <?php echo getStatusBadgeClass($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Special Offers Section -->
           
        </main>
    </div>

    <script>
        function addToCart(productId) {
            // AJAX call to add product to cart
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                    // Update cart count without page refresh
                    const cartValue = document.querySelector('.card-cart .value');
                    if (cartValue) {
                        cartValue.textContent = parseInt(cartValue.textContent) + 1;
                    }
                } else {
                    alert(data.message || 'Error adding product to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the product to cart');
            });
        }
    </script>
</body>
</html>