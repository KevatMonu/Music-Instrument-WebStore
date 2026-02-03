<?php

$conn = new mysqli("localhost", "root", "", "musicstore_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$total_products_query = "SELECT COUNT(*) as count FROM products";
$total_orders_query = "SELECT COUNT(*) as count FROM orders";
$total_customers_query = "SELECT COUNT(*) as count FROM users WHERE user_role = 'customer'";
$low_stock_query = "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 5";

$products_result = $conn->query($total_products_query);
$orders_result = $conn->query($total_orders_query);
$customers_result = $conn->query($total_customers_query);
$low_stock_result = $conn->query($low_stock_query);

$total_products = $products_result->fetch_assoc()['count'];
$total_orders = $orders_result->fetch_assoc()['count'];
$total_customers = $customers_result->fetch_assoc()['count'];
$low_stock = $low_stock_result->fetch_assoc()['count'];


$current_report = isset($_GET['report']) ? $_GET['report'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> K&P Music  Reports</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="css/reports.css">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <i class="fas fa-music"></i>
                <span>Music Store</span>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_categories.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_products.php" class="nav-link">
                    <i class="fas fa-guitar"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
        
        <div class="logout-btn">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    <header>
        <h1>K&P Music Management Reports</h1>
    </header>
    <div class="container">
        <div class="dashboard-summary">
            <div class="summary-card">
                <h3>Total Products</h3>
                <div class="number"><?php echo $total_products; ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo $total_orders; ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Customers</h3>
                <div class="number"><?php echo $total_customers; ?></div>
            </div>
            <div class="summary-card">
                <h3>Low Stock Items</h3>
                <div class="number"><?php echo $low_stock; ?></div>
            </div>
        </div>

        <div class="report-buttons">
            <button class="report-button <?php if ($current_report == 'sales') echo 'active'; ?>" onclick="window.location.href='?report=sales'">Sales Report</button>
            <button class="report-button <?php if ($current_report == 'category') echo 'active'; ?>" onclick="window.location.href='?report=category'">Category Performance</button>
            <button class="report-button <?php if ($current_report == 'customer') echo 'active'; ?>" onclick="window.location.href='?report=customer'">Customer History</button>
        </div>

        <div class="report-container">
            <?php if ($current_report == 'sales'): ?>
               
                <div class="report-header">
                    <h2>Sales Report</h2>
                    <button class="export-pdf-btn" id="exportPdf">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>

                <?php
                
                $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
                $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

                // Query for sales report
                $sql = "SELECT o.order_id, o.order_created, o.total_cost, o.order_type, 
                            u.full_name as customer_name, 
                            COUNT(oi.order_item_id) as number_of_items,
                            o.order_status,
                            p.payment_status
                    FROM orders o
                    LEFT JOIN users u ON o.user_ref = u.user_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_ref
                    LEFT JOIN payments p ON o.order_id = p.order_ref
                    WHERE o.order_created BETWEEN '$start_date' AND '$end_date 23:59:59'
                    GROUP BY o.order_id
                    ORDER BY o.order_created DESC";

                $result = $conn->query($sql);
                ?>

                <div class="filter-form">
                    <form method="post" action="?report=sales">
                        <label>Start Date: <input type="date" name="start_date" value="<?php echo $start_date; ?>"></label>
                        <label>End Date: <input type="date" name="end_date" value="<?php echo $end_date; ?>"></label>
                        <button type="submit">Filter</button>
                    </form>
                </div>

                <h3>Sales from <?php echo $start_date; ?> to <?php echo $end_date; ?></h3>

                <table id="salesTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_sales = 0;
                        $buy_count = 0;
                        $rent_count = 0;

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['order_id'] . "</td>";
                                echo "<td>" . $row['order_created'] . "</td>";
                                echo "<td>" . $row['customer_name'] . "</td>";
                                echo "<td>" . $row['order_type'] . "</td>";
                                echo "<td>" . $row['number_of_items'] . "</td>";
                                echo "<td>₹" . number_format($row['total_cost'], 2) . "</td>";
                                echo "<td>" . $row['order_status'] . "</td>";
                                echo "<td>" . $row['payment_status'] . "</td>";
                                echo "</tr>";

                                if ($row['order_status'] == 'completed') {
                                    $total_sales += $row['total_cost'];
                                    if ($row['order_type'] == 'buy') $buy_count++;
                                    if ($row['order_type'] == 'rent') $rent_count++;
                                }
                            }
                        } else {
                            echo "<tr><td colspan='8'>No orders found in this date range</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px;" id="salesSummary">
                    <h3>Summary</h3>
                    <p>Total Sales: ₹<?php echo number_format($total_sales, 2); ?></p>
                    <p>Purchase Orders: <?php echo $buy_count; ?></p>
                    <p>Rental Orders: <?php echo $rent_count; ?></p>
                </div>

            <?php elseif ($current_report == 'inventory'): ?>
               
                <div class="report-header">
                    <h2>Inventory Report</h2>
                    <button class="export-pdf-btn" id="exportPdf">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>

                <?php
            
                $category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

                
                $sql = "SELECT p.product_id, p.product_name, c.category_name, 
                            p.product_price, p.rental_cost, p.stock_quantity,
                            (SELECT COUNT(*) FROM order_items oi 
                            JOIN orders o ON oi.order_ref = o.order_id 
                            WHERE oi.product_ref = p.product_id 
                            AND o.order_status = 'completed') as times_sold
                    FROM products p
                    LEFT JOIN categories c ON p.category_ref = c.category_id";

                if ($category_filter > 0) {
                    $sql .= " WHERE p.category_ref = $category_filter";
                }

                $sql .= " ORDER BY stock_quantity ASC";
                $result = $conn->query($sql);

                // Get categories for filter dropdown
                $cat_sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
                $cat_result = $conn->query($cat_sql);
                ?>

                <div class="filter-form">
                    <form method="get">
                        <input type="hidden" name="report" value="inventory">
                        <label>Filter by Category:
                            <select name="category">
                                <option value="0">All Categories</option>
                                <?php
                                while ($cat = $cat_result->fetch_assoc()) {
                                    $selected = ($category_filter == $cat['category_id']) ? 'selected' : '';
                                    echo "<option value='" . $cat['category_id'] . "' $selected>" . $cat['category_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </label>
                        <button type="submit">Filter</button>
                    </form>
                </div>


            <?php elseif ($current_report == 'category'): ?>
        
                <div class="report-header">
                    <h2>Category Performance Report</h2>
                    <button class="export-pdf-btn" id="exportPdf">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>

                <?php
           
                $period = isset($_GET['period']) ? $_GET['period'] : 'month';

                switch ($period) {
                    case 'week':
                        $date_filter = "AND o.order_created >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)";
                        $period_text = "Past Week";
                        break;
                    case 'year':
                        $date_filter = "AND o.order_created >= DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)";
                        $period_text = "Past Year";
                        break;
                    case 'month':
                    default:
                        $date_filter = "AND o.order_created >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)";
                        $period_text = "Past Month";
                }

                // Query for category performance
                $sql = "SELECT c.category_id, c.category_name,
                            COUNT(DISTINCT o.order_id) as order_count,
                            SUM(oi.item_quantity) as items_sold,
                            SUM(oi.item_price * oi.item_quantity) as revenue
                        FROM categories c
                        LEFT JOIN products p ON c.category_id = p.category_ref
                        LEFT JOIN order_items oi ON p.product_id = oi.product_ref
                        LEFT JOIN orders o ON oi.order_ref = o.order_id
                        WHERE o.order_status = 'completed' $date_filter
                        GROUP BY c.category_id
                        ORDER BY revenue DESC";

                $result = $conn->query($sql);
                ?>

                <div class="filter-form">
                    <form method="get">
                        <input type="hidden" name="report" value="category">
                        <label>Time Period:
                            <select name="period">
                                <option value="week" <?php if ($period == 'week') echo 'selected'; ?>>Past Week</option>
                                <option value="month" <?php if ($period == 'month') echo 'selected'; ?>>Past Month</option>
                                <option value="year" <?php if ($period == 'year') echo 'selected'; ?>>Past Year</option>
                            </select>
                        </label>
                        <button type="submit">Filter</button>
                    </form>
                </div>

                <h3>Performance for <?php echo $period_text; ?></h3>

                <table id="categoryTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Orders</th>
                            <th>Items Sold</th>
                            <th>Revenue</th>
                            <th>% of Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_revenue = 0;
                        $category_data = [];

                        if ($result->num_rows > 0) {
                            // First pass to calculate total revenue
                            while ($row = $result->fetch_assoc()) {
                                $total_revenue += $row['revenue'];
                                $category_data[] = $row;
                            }

                            // Second pass to display with percentages
                            foreach ($category_data as $row) {
                                $percentage = ($total_revenue > 0) ? ($row['revenue'] / $total_revenue * 100) : 0;

                                echo "<tr>";
                                echo "<td>" . $row['category_name'] . "</td>";
                                echo "<td>" . $row['order_count'] . "</td>";
                                echo "<td>" . $row['items_sold'] . "</td>";
                                echo "<td>$" . number_format($row['revenue'], 2) . "</td>";
                                echo "<td>" . number_format($percentage, 2) . "%</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No data available for this period</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px;" id="categorySummary">
                    <h3>Summary</h3>
                    <p>Total Revenue: ₹<?php echo number_format($total_revenue, 2); ?></p>
                    <p>Number of Categories with Sales: <?php echo count($category_data); ?></p>
                </div>

            <?php elseif ($current_report == 'customer'): ?>
                <!-- Customer Purchase History Report -->
                <div class="report-header">
                    <h2>Customer Purchase History</h2>
                    <button class="export-pdf-btn" id="exportPdf">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>

                <?php
                // Get user ID parameter
                $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

                // Get list of customers for dropdown
                $users_sql = "SELECT user_id, full_name, email_address FROM users WHERE user_role = 'customer' ORDER BY full_name";
                $users_result = $conn->query($users_sql);

                // Get customer information
                $user_info = [];
                if ($user_id > 0) {
                    $user_sql = "SELECT * FROM users WHERE user_id = $user_id";
                    $user_result = $conn->query($user_sql);
                    if ($user_result->num_rows > 0) {
                        $user_info = $user_result->fetch_assoc();
                    }

                    // Get orders
                    $orders_sql = "SELECT o.*, p.payment_mode, p.payment_status 
                                FROM orders o 
                                LEFT JOIN payments p ON o.order_id = p.order_ref
                                WHERE o.user_ref = $user_id 
                                ORDER BY o.order_created DESC";
                    $orders_result = $conn->query($orders_sql);
                }
                ?>

                <div class="filter-form">
                    <form method="get">
                        <input type="hidden" name="report" value="customer">
                        <label>Select Customer:
                            <select name="user_id">
                                <option value="0">-- Select Customer --</option>
                                <?php
                                while ($user = $users_result->fetch_assoc()) {
                                    $selected = ($user_id == $user['user_id']) ? 'selected' : '';
                                    echo "<option value='" . $user['user_id'] . "' $selected>" . $user['full_name'] . " (" . $user['email_address'] . ")</option>";
                                }
                                ?>
                            </select>
                        </label>
                        <button type="submit">View History</button>
                    </form>
                </div>

                <?php if ($user_id > 0 && !empty($user_info)): ?>
                    <div style="margin-top: 20px;" id="customerInfo">
                        <h3>Customer Information</h3>
                        <p><strong>Name:</strong> <?php echo $user_info['full_name']; ?></p>
                        <p><strong>Email:</strong> <?php echo $user_info['email_address']; ?></p>
                        <p><strong>Phone:</strong> <?php echo $user_info['phone_number']; ?></p>
                        <p><strong>Address:</strong> <?php echo $user_info['user_address']; ?></p>
                        <p><strong>Joined:</strong> <?php echo $user_info['created_on']; ?></p>
                    </div>

                    <h3>Order History</h3>
                    <?php if ($orders_result->num_rows > 0): ?>
                        <table id="customerTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo $order['order_created']; ?></td>
                                        <td><?php echo $order['order_type']; ?></td>
                                        <td>$<?php echo number_format($order['total_cost'], 2); ?></td>
                                        <td><?php echo $order['order_status']; ?></td>
                                        <td><?php echo $order['payment_mode']; ?></td>
                                        <td><?php echo $order['payment_status']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No orders found for this customer.</p>
                    <?php endif; ?>
                <?php elseif ($user_id > 0): ?>
                    <p>Customer not found.</p>
                <?php endif; ?>

            <?php else: ?>
                <!-- Dashboard Home -->
                <h2>Welcome to K&P Music Store Reports</h2>
                <p>Please select a report from the buttons above to view detailed information.</p>
                <p>Here's a quick overview of what each report provides:</p>

                <ul>
                    <li><strong>Sales Report:</strong> View all sales data filtered by date range, including order details and payment information.</li>
                    <li><strong>Inventory Report:</strong> Check your current stock levels, identify products that need restocking, and see which items are selling well.</li>
                    <li><strong>Category Performance:</strong> Analyze which instrument categories are generating the most revenue over different time periods.</li>
                    <li><strong>Customer History:</strong> View detailed purchase histories for individual customers, including all their orders and items purchased.</li>
                </ul>

                <p>The dashboard summary above provides a quick snapshot of your store's current status.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- PDF Export functionality -->
    <script>
        // Replace the existing PDF export script with this improved version
        document.addEventListener('DOMContentLoaded', function() {
            const exportBtn = document.getElementById('exportPdf');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    // Verify that jsPDF is loaded
                    if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
                        alert("PDF library not loaded correctly. Please check your internet connection and try again.");
                        console.error("jsPDF library not loaded properly");
                        return;
                    }

                    try {
                        // Get current report
                        const currentReport = '<?php echo $current_report; ?>';

                        // Initialize jsPDF
                        const {
                            jsPDF
                        } = window.jspdf;
                        const doc = new jsPDF();

                        // Set title based on report type
                        let title = 'K&P Music Store Report';
                        let tableId = '';
                        let summaryId = '';

                        switch (currentReport) {
                            case 'sales':
                                title = 'K&P Music Store - Sales Report';
                                tableId = 'salesTable';
                                summaryId = 'salesSummary';
                                break;
                            case 'inventory':
                                title = 'K&P Music Store - Inventory Report';
                                tableId = 'inventoryTable';
                                summaryId = 'inventorySummary';
                                break;
                            case 'category':
                                title = 'K&P Music Store - Category Performance Report';
                                tableId = 'categoryTable';
                                summaryId = 'categorySummary';
                                break;
                            case 'customer':
                                title = 'K&P Music Store - Customer Purchase History';
                                tableId = 'customerTable';
                                summaryId = 'customerInfo';
                                break;
                        }

                        // Add title
                        doc.setFontSize(18);
                        doc.text(title, 14, 20);

                        // Add current date
                        doc.setFontSize(12);
                        doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 30);

                        // Set starting Y position
                        let currentY = 40;

                        // Add date range for sales report
                        if (currentReport === 'sales') {
                            const startDate = document.querySelector('input[name="start_date"]').value;
                            const endDate = document.querySelector('input[name="end_date"]').value;
                            doc.text('Date Range: ' + startDate + ' to ' + endDate, 14, currentY);
                            currentY += 10;
                        }

                        // Add period for category report
                        if (currentReport === 'category') {
                            const periodSelect = document.querySelector('select[name="period"]');
                            const periodText = periodSelect.options[periodSelect.selectedIndex].text;
                            doc.text('Period: ' + periodText, 14, currentY);
                            currentY += 10;
                        }

                        // If table exists, add it to PDF
                        const table = document.getElementById(tableId);
                        if (table) {
                            // Make sure autoTable plugin is available
                            if (typeof doc.autoTable === 'undefined') {
                                alert("AutoTable plugin not loaded correctly. Please check your internet connection and try again.");
                                console.error("autoTable plugin not loaded properly");
                                return;
                            }

                            doc.autoTable({
                                html: '#' + tableId,
                                startY: currentY,
                                theme: 'grid',
                                headStyles: {
                                    fillColor: [74, 144, 226],
                                    textColor: [255, 255, 255]
                                },
                                styles: {
                                    overflow: 'linebreak',
                                    cellPadding: 3
                                }
                            });
                        }

                        // Add summary information
                        const summary = document.getElementById(summaryId);
                        if (summary) {
                            let summaryY = doc.lastAutoTable ? doc.lastAutoTable.finalY + 10 : currentY;

                            doc.setFontSize(14);
                            doc.text('Summary', 14, summaryY);
                            summaryY += 10;

                            // Get all paragraphs from summary
                            const paragraphs = summary.querySelectorAll('p');
                            doc.setFontSize(12);

                            paragraphs.forEach(p => {
                                doc.text(p.textContent, 14, summaryY);
                                summaryY += 7;
                            });
                        }

                        // Save the PDF
                        doc.save(title + '.pdf');

                    } catch (error) {
                        console.error("Error generating PDF:", error);
                        alert("Error generating PDF. Please check console for details.");
                    }
                });
            }
        });
    </script>
</body>

</html>