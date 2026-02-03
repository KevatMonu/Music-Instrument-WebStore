<?php
session_start();

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

$user_id = $_SESSION['user_id'];

// Fetch user details
$userStmt = $conn->prepare("SELECT full_name, email_address, user_role FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();

// Check if viewing order details
if (isset($_GET['view_order']) && is_numeric($_GET['view_order'])) {
    $order_id = (int)$_GET['view_order'];
    
    // Fetch order details
    $orderStmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_ref = ?");
    $orderStmt->bind_param("ii", $order_id, $user_id);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    if ($orderResult->num_rows > 0) {
        $orderDetails = $orderResult->fetch_assoc();
        
        // Fetch order items - FIXED QUERY: Use the correct column names
        $itemsStmt = $conn->prepare("SELECT oi.*, p.product_name, p.product_image 
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_ref = p.product_id 
                                    WHERE oi.order_ref = ?");
        $itemsStmt->bind_param("i", $order_id);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        $orderItems = $itemsResult->fetch_all(MYSQLI_ASSOC);
    } else {
        // Order not found or doesn't belong to user
        header("Location: user_order_detail.php?error=invalid_order");
        exit();
    }
}

// Fetch user's orders with pagination
$limit = 10; // Number of orders per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total orders for pagination
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_ref = ?");
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalOrders = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalOrders / $limit);

// Fetch orders with sorting
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'order_created';
$sortOrder = isset($_GET['order']) && $_GET['order'] == 'asc' ? 'ASC' : 'DESC';

// Only allow sorting by valid columns
$allowedSortColumns = ['order_id', 'order_created', 'total_cost', 'order_status'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'order_created';
}

// Filter by order status if requested
$statusFilter = '';
$filterParams = [];
$filterTypes = '';

if (isset($_GET['status']) && in_array($_GET['status'], ['completed', 'cancelled'])) {
    $statusFilter = " AND order_status = ?";
    $filterParams[] = $_GET['status'];
    $filterTypes .= "s";
}

// Prepare the query with filters
$query = "SELECT * FROM orders WHERE user_ref = ?" . $statusFilter . 
         " ORDER BY $sortBy $sortOrder LIMIT ? OFFSET ?";

// Add parameters for the main query
array_unshift($filterParams, $user_id);
$filterTypes = "i" . $filterTypes . "ii";
$filterParams[] = $limit;
$filterParams[] = $offset;

$stmt = $conn->prepare($query);
$stmt->bind_param($filterTypes, ...$filterParams);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Helper function for sorting links
function getSortLink($column, $currentSort, $currentOrder) {
    $newOrder = ($currentSort == $column && $currentOrder == 'desc') ? 'asc' : 'desc';
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = $newOrder;
    return '?' . http_build_query($params);
}

// Helper function for filter links
function getFilterLink($param, $value) {
    $params = $_GET;
    if (isset($params[$param]) && $params[$param] == $value) {
        unset($params[$param]); // Toggle off if already active
    } else {
        $params[$param] = $value;
    }
    // Reset pagination when changing filters
    unset($params['page']);
    return '?' . http_build_query($params);
}

// Get first letter of user's name for avatar
$userInitial = isset($userData['full_name']) ? strtoupper(substr($userData['full_name'], 0, 1)) : 'U';

// Close the connection if not viewing order details
if (!isset($_GET['view_order'])) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['view_order']) ? 'Order #' . $_GET['view_order'] : 'My Orders'; ?> - Music Store</title>
    <link rel="stylesheet" href="https://unpkg.com/lucide-static@latest/font/lucide.css">
    <link rel="stylesheet" href="css/user_order_detail.css">
    <style>
        /* Additional styles for order details */
        .order-details-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }
        .summary-item h4 {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 0.9em;
        }
        .summary-item p {
            margin: 0;
            font-size: 1.2em;
            font-weight: 600;
        }
        .order-items {
            margin-bottom: 30px;
        }
        .item-card {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            margin-right: 15px;
            object-fit: cover;
            background-color: #f5f5f5;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        .item-price, .item-quantity {
            color: #666;
            margin: 3px 0;
        }
        .item-total {
            font-weight: 600;
            color: #333;
        }
        .back-to-orders {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background: #f5f5f5;
            border-radius: 6px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .back-to-orders:hover {
            background: #e5e5e5;
        }
        .order-totals {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: right;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5px;
        }
        .total-label {
            margin-right: 20px;
            color: #666;
            width: 150px;
            text-align: right;
        }
        .total-value {
            width: 100px;
            text-align: right;
            font-weight: 500;
        }
        .grand-total {
            font-size: 1.2em;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <i class="lucide-music"></i>
                    <span>K&P Musicals</span>
                </div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="user_dashboard.php" class="nav-link">
                        <i class="lucide-home"></i>
                        <span>  Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link">
                        <i class="lucide-package"></i>
                        <span>View Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cart.php" class="nav-link">
                        <i class="lucide-shopping-cart"></i>
                        <span>View Cart</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user_order_detail.php" class="nav-link active">
                        <i class="lucide-clipboard-list"></i>
                        <span>View Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="lucide-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
            <div class="logout-btn">
                <a href="logout.php">
                    <i class="lucide-log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="menu-toggle" id="menu-toggle">
                    <i class="lucide-menu"></i>
                </div>
                <h1 class="page-title">
                    <?php echo isset($_GET['view_order']) ? 'Order Details #' . $_GET['view_order'] : 'My Orders'; ?>
                </h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo $userInitial; ?>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo isset($userData['full_name']) ? $userData['full_name'] : 'User'; ?></span>
                        <span class="user-email"><?php echo isset($userData['email_address']) ? $userData['email_address'] : ''; ?></span>
                        <span class="user-role"><?php echo isset($userData['user_role']) ? ucfirst($userData['user_role']) : ''; ?></span>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['view_order']) && isset($orderDetails)): ?>
            <!-- Order Details View -->
            <div class="order-details-container">
                <div class="order-header">
                    <a href="user_order_detail.php" class="back-to-orders">
                        <i class="lucide-arrow-left"></i> Back to Orders
                    </a>
                    <div class="order-status">
                        <span class="status-pill <?php echo strtolower($orderDetails['order_status']) == 'completed' ? 'status-paid' : 'status-pending'; ?>">
                            <?php echo ucfirst($orderDetails['order_status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-summary">
                    <div class="summary-item">
                        <h4>Order Number</h4>
                        <p>#<?php echo $orderDetails['order_id']; ?></p>
                    </div>
                    <div class="summary-item">
                        <h4>Order Date</h4>
                        <p><?php echo date('M d, Y', strtotime($orderDetails['order_created'])); ?></p>
                    </div>
                    <div class="summary-item">
                        <h4>Order Time</h4>
                        <p><?php echo date('h:i A', strtotime($orderDetails['order_created'])); ?></p>
                    </div>
                    <div class="summary-item">
                        <h4>Total Amount</h4>
                        <p>₹<?php echo number_format($orderDetails['total_cost'], 2); ?></p>
                    </div>
                </div>
                
                <div class="order-items">
                    <h3>Order Items</h3>
                    
                    <?php if (empty($orderItems)): ?>
                    <p>No items found for this order.</p>
                    <?php else: ?>
                        <?php foreach ($orderItems as $item): ?>
                        <div class="item-card">
                            <img src="<?php echo !empty($item['product_image']) ? $item['product_image'] : 'images/default-product.jpg'; ?>" alt="<?php echo $item['product_name']; ?>" class="item-image">
                            <div class="item-details">
                                <h4 class="item-name"><?php echo $item['product_name']; ?></h4>
                                <p class="item-price">Unit Price: ₹<?php echo number_format($item['item_price'], 2); ?></p>
                                <p class="item-quantity">Quantity: <?php echo $item['item_quantity']; ?></p>
                                <p class="item-total">Total: ₹<?php echo number_format($item['item_price'] * $item['item_quantity'], 2); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="order-totals">
                            <div class="total-row">
                                <div class="total-label">Subtotal:</div>
                                <div class="total-value">₹<?php echo number_format($orderDetails['total_cost'] - ($orderDetails['total_cost'] * 0.1), 2); ?></div>
                            </div>
                            <div class="total-row">
                                <div class="total-label">Tax (10%):</div>
                                <div class="total-value">₹<?php echo number_format($orderDetails['total_cost'] * 0.1, 2); ?></div>
                            </div>
                            <div class="total-row grand-total">
                                <div class="total-label">Grand Total:</div>
                                <div class="total-value">₹<?php echo number_format($orderDetails['total_cost'], 2); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Orders List View -->
            
            <!-- Filter Controls -->
            <div class="quick-actions">
                <h2 class="section-title">Filter Options</h2>
                <div class="action-buttons">
                    <a href="<?php echo getFilterLink('status', 'completed'); ?>" class="action-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'active' : ''; ?>">
                        <i class="lucide-check-circle"></i> Completed Orders
                    </a>
                    <a href="<?php echo getFilterLink('status', 'cancelled'); ?>" class="action-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'active' : ''; ?>">
                        <i class="lucide-x-circle"></i> Cancelled Orders
                    </a>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="recent-activity">
                <h2 class="section-title">Order History</h2>
                
                <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid_order'): ?>
                <div class="alert alert-danger">
                    <i class="lucide-alert-triangle"></i>
                    <span>The order you requested could not be found or doesn't belong to your account.</span>
                </div>
                <?php endif; ?>
                
                <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <p>You haven't placed any orders yet.</p>
                    <a href="products.php" class="action-btn">
                        <i class="lucide-shopping-cart"></i> Browse Products
                    </a>
                </div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="<?php echo getSortLink('order_id', $sortBy, $sortOrder); ?>">
                                    Order ID
                                    <?php if ($sortBy == 'order_id'): ?>
                                    <i class="lucide-<?php echo $sortOrder == 'asc' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortLink('order_created', $sortBy, $sortOrder); ?>">
                                    Date
                                    <?php if ($sortBy == 'order_created'): ?>
                                    <i class="lucide-<?php echo $sortOrder == 'asc' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortLink('total_cost', $sortBy, $sortOrder); ?>">
                                    Total
                                    <?php if ($sortBy == 'total_cost'): ?>
                                    <i class="lucide-<?php echo $sortOrder == 'asc' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortLink('order_status', $sortBy, $sortOrder); ?>">
                                    Status
                                    <?php if ($sortBy == 'order_status'): ?>
                                    <i class="lucide-<?php echo $sortOrder == 'asc' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo date('M d, Y - h:i A', strtotime($order['order_created'])); ?></td>
                            <td>₹<?php echo number_format($order['total_cost'], 2); ?></td>
                            <td>
                                <?php 
                                $statusClass = '';
                                switch(strtolower($order['order_status'])) {
                                    case 'completed':
                                        $statusClass = 'status-paid';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'status-pending';
                                        break;
                                    default:
                                        $statusClass = 'status-processing';
                                }
                                ?>
                                <span class="status-pill <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?view_order=<?php echo $order['order_id']; ?>" class="action-btn">
                                    <i class="lucide-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?><?php echo isset($_GET['sort']) ? '&sort='.$_GET['sort'] : ''; ?><?php echo isset($_GET['order']) ? '&order='.$_GET['order'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?>" class="action-btn">
                        <i class="lucide-chevron-left"></i> Previous
                    </a>
                    <?php endif; ?>
                    
                    <div class="page-info">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['sort']) ? '&sort='.$_GET['sort'] : ''; ?><?php echo isset($_GET['order']) ? '&order='.$_GET['order'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo isset($_GET['sort']) ? '&sort='.$_GET['sort'] : ''; ?><?php echo isset($_GET['order']) ? '&order='.$_GET['order'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?>" class="action-btn">
                        Next <i class="lucide-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        </div>
    </div>

    <script>
    // Toggle sidebar on mobile
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
    </script>
</body>
</html>

<?php
// Close the connection if viewing order details (wasn't closed earlier)
if (isset($_GET['view_order'])) {
    $conn->close();
}
?>