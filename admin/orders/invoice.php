<?php
session_start();

// Database Connection
$conn = new mysqli("localhost", "root", "", "musicstore_database");

// Check Connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    // User is not signed in, redirect to login page
    $_SESSION['redirect_after_login'] = 'checkout.php'; // Set redirect after login
    header("Location: sign-in.php");
    exit();
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$error = '';
$invoice_data = null;
$order_items = [];
$order_details = null;
$shipping_details = null;
$payment_details = null;
$user_details = null;

// Check if order exists and belongs to the current user
if ($order_id > 0) {
    // Check if invoice exists for this order
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE order_ref = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $invoice_result = $stmt->get_result();
    
    if ($invoice_result->num_rows > 0) {
        // Invoice exists, get data
        $invoice_data = $invoice_result->fetch_assoc();
    } else {
        // Invoice doesn't exist, create a new one
        // First, get order details to confirm it belongs to the user
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_ref = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $order_result = $stmt->get_result();
        
        if ($order_result->num_rows > 0) {
            $order_details = $order_result->fetch_assoc();
            
            // Generate invoice number (format: INV-YEAR-MONTH-ORDERID)
            $current_date = new DateTime();
            $invoice_number = 'INV-' . $current_date->format('Y-m') . '-' . $order_id;
            
            try {
                // Create invoice without relying on foreign key constraints
                $stmt = $conn->prepare("INSERT INTO invoices (order_ref, invoice_number, invoice_date, invoice_amount, user_ref) VALUES (?, ?, NOW(), ?, ?)");
                $stmt->bind_param("isdi", $order_id, $invoice_number, $order_details['total_cost'], $user_id);
                $stmt->execute();
                
                // Get the newly created invoice
                $invoice_id = $conn->insert_id;
                
                $stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_id = ?");
                $stmt->bind_param("i", $invoice_id);
                $stmt->execute();
                $invoice_result = $stmt->get_result();
                $invoice_data = $invoice_result->fetch_assoc();
            } catch (Exception $e) {
                $error = "Error creating invoice: " . $e->getMessage();
            }
        } else {
            $error = "Order not found or does not belong to you.";
        }
    }
    
    // If we have invoice data, fetch all the related information
    if ($invoice_data) {
        // Fetch order details if not already fetched
        if (!$order_details) {
            $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order_result = $stmt->get_result();
            $order_details = $order_result->fetch_assoc();
        }
        
        // Fetch order items
        $stmt = $conn->prepare("SELECT oi.*, p.product_name, p.product_description 
                                FROM order_items oi 
                                JOIN products p ON oi.product_ref = p.product_id 
                                WHERE oi.order_ref = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
        
        // Fetch shipping details
        $stmt = $conn->prepare("SELECT * FROM order_shipping WHERE order_ref = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $shipping_result = $stmt->get_result();
        $shipping_details = $shipping_result->fetch_assoc();
        
        // Fetch payment details
        $stmt = $conn->prepare("SELECT * FROM payments WHERE order_ref = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $payment_result = $stmt->get_result();
        $payment_details = $payment_result->fetch_assoc();
        
        // Fetch user details
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        $user_details = $user_result->fetch_assoc();
    }
} else {
    $error = "Invalid order ID.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice | Music Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Invoice-specific styles */
        .invoice-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .invoice-logo {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .invoice-title {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .invoice-title h1 {
            font-size: 28px;
            color: #333;
            margin: 0;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .invoice-details-group {
            flex-basis: 48%;
        }
        
        .invoice-details-group h3 {
            margin-top: 0;
            color: #555;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .details-row {
            margin-bottom: 5px;
        }
        
        .details-label {
            font-weight: bold;
            color: #666;
        }
        
        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .invoice-items th {
            background-color: #f5f5f5;
            border-bottom: 2px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        .invoice-items td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .item-description {
            max-width: 300px;
        }
        
        .invoice-summary {
            float: right;
            width: 300px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 1.2em;
            border-top: 2px solid #ddd;
            border-bottom: none;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .invoice-footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
        
        .invoice-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        @media print {
            .no-print, .no-print * {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .invoice-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .main-content {
                padding: 0;
            }
            /* Hide site header and footer when printing */
            header, footer, .site-header, .site-footer {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Wrap header in no-print div -->
    <div class="no-print">
        <?php include 'pages/header.php'; ?>
    </div>
    
    <main class="main-content">
        <?php if ($error): ?>
            <div class="alert error-alert"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($invoice_data): ?>
            <div class="invoice-container" id="invoice-content">
                <div class="invoice-header">
                    <div class="invoice-logo">
                        <i class="fas fa-music"></i> K&P Music
                    </div>
                    <div class="invoice-info">
                        <div>Invoice #: <?php echo htmlspecialchars($invoice_data['invoice_number'] ?? ''); ?></div>
                        <div>Date: <?php echo date('d M, Y', strtotime($invoice_data['invoice_date'] ?? 'now')); ?></div>
                    </div>
                </div>
                
                <div class="invoice-title">
                    <h1>INVOICE</h1>
                </div>
                
                <div class="invoice-details">
                    <div class="invoice-details-group">
                        <h3>Bill To:</h3>
                        <div class="details-row">
                            <span class="details-label">Name:</span> 
                            <?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Email:</span> 
                            <?php echo htmlspecialchars($user_details['email_address'] ?? ''); ?>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Phone:</span> 
                            <?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="invoice-details-group">
                        <h3>Shipping Address:</h3>
                        <div class="details-row">
                            <?php echo htmlspecialchars($shipping_details['shipping_address'] ?? ''); ?>,
                            <?php echo htmlspecialchars($shipping_details['shipping_city'] ?? ''); ?>,
                            <?php echo htmlspecialchars($shipping_details['shipping_state'] ?? ''); ?> - 
                            <?php echo htmlspecialchars($shipping_details['shipping_pincode'] ?? ''); ?>
                        </div>
                    </div>
                </div>
                
                <table class="invoice-items">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name'] ?? ''); ?></td>
                            <td class="item-description">
                                <?php 
                                // Truncate description if too long
                                $desc = $item['product_description'] ?? '';
                                echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 97) . '...' : $desc); 
                                ?>
                            </td>
                            <td>₹<?php echo number_format(($item['item_price'] ?? 0), 2); ?></td>
                            <td><?php echo ($item['item_quantity'] ?? 0); ?></td>
                            <td>₹<?php echo number_format(($item['item_price'] ?? 0) * ($item['item_quantity'] ?? 0), 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="invoice-summary">
                    <div class="summary-row">
                        <div>Subtotal:</div>
                        <div>₹<?php echo number_format(($order_details['total_cost'] ?? 0), 2); ?></div>
                    </div>
                    
                    <?php if (isset($order_details['discount_amount']) && $order_details['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <div>Discount:</div>
                        <div>-₹<?php echo number_format($order_details['discount_amount'], 2); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <div>Shipping:</div>
                        <div>Free</div>
                    </div>
                    
                    <div class="summary-row total">
                        <div>Total:</div>
                        <div>₹<?php echo number_format(($invoice_data['invoice_amount'] ?? 0), 2); ?></div>
                    </div>
                </div>
                
                <div class="invoice-footer">
                    <p>Thank you for your purchase!</p>
                    <p>For any inquiries, please contact us at support@musicstore.com</p>
                </div>
            </div>
            
            <div class="invoice-actions no-print">
                <button id="print-invoice" class="btn secondary-btn">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
                <button id="download-invoice" class="btn primary-btn">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Wrap footer in no-print div -->
    <div class="no-print">
        <?php include 'pages/footer.php'; ?>
    </div>
    
    <!-- Include jsPDF library for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Print Invoice
            const printButton = document.getElementById('print-invoice');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    window.print();
                });
            }
            
            // Download Invoice as PDF
            const downloadButton = document.getElementById('download-invoice');
            if (downloadButton) {
                downloadButton.addEventListener('click', function() {
                    const { jsPDF } = window.jspdf;
                    
                    // Create new PDF document
                    const doc = new jsPDF('p', 'pt', 'a4');
                    
                    // Get the invoice content
                    const invoiceContent = document.getElementById('invoice-content');
                    
                    // Use html2canvas to convert the HTML to a canvas
                    html2canvas(invoiceContent, {
                        scale: 2, // Increase resolution
                        useCORS: true,
                        logging: false
                    }).then(canvas => {
                        // Get the image data from the canvas
                        const imgData = canvas.toDataURL('image/png');
                        
                        // Page dimensions
                        const pageWidth = doc.internal.pageSize.getWidth();
                        const pageHeight = doc.internal.pageSize.getHeight();
                        
                        // Calculate the image width and height to fit the page
                        const imgWidth = pageWidth - 40; // margin of 20pt on each side
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;
                        
                        // Add the image to the PDF
                        doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
                        
                        // If the image is taller than the page, add multiple pages
                        let heightLeft = imgHeight;
                        let position = 20; // Start position
                        
                        while (heightLeft > pageHeight - 40) { // 20pt margin top and bottom
                            // Add a new page
                            doc.addPage();
                            position = -(pageHeight - 40 - position);
                            
                            // Add the image again but with different positioning to show next part
                            doc.addImage(imgData, 'PNG', 20, position, imgWidth, imgHeight);
                            
                            heightLeft -= (pageHeight - 40);
                        }
                        
                        // Save the PDF
                        doc.save('invoice-<?php echo htmlspecialchars($invoice_data['invoice_number'] ?? 'download'); ?>.pdf');
                    });
                });
            }
        });
    </script>
</body>
</html>