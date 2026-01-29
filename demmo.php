<?php
// Start session for cart functionality
session_start();

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header("Location: sign-in.php?redirect=checkout.php");
    exit();
}


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get user details
function getUserDetails($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to calculate cart total
function calculateCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}

// Function to get a single product by ID
function getProductById($conn, $id) {
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Get user details
$user = getUserDetails($conn, $_SESSION['user_id']);
$cart_total = calculateCartTotal();

// Initialize error and success messages
$error_message = "";
$success_message = "";

// Process order submission
if (isset($_POST['place_order'])) {
    // Validate shipping information
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $phone = trim($_POST['phone']);
    
    // Simple validation
    if (empty($full_name) || empty($email) || empty($address) || empty($city) || empty($postal_code) || empty($phone)) {
        $error_message = "Please fill in all required fields";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Generate invoice number (Simple format: Current date + random number)
            $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Create order record
            $order_sql = "INSERT INTO orders (user_ref, total_cost, order_status, order_type, invoice_number) 
                          VALUES (?, ?, 'completed', 'buy', ?)";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("ids", $_SESSION['user_id'], $cart_total, $invoice_number);
            $order_stmt->execute();
            
            // Get the new order ID
            $order_id = $conn->insert_id;
            
            // Insert order items
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $item_sql = "INSERT INTO order_items (order_ref, product_ref, item_quantity, item_price) 
                             VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                $item_stmt->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
                $item_stmt->execute();
                
                // Update stock quantity
                $product = getProductById($conn, $product_id);
                if ($product) {
                    $new_quantity = $product['stock_quantity'] - $item['quantity'];
                    $new_quantity = max(0, $new_quantity); // Ensure quantity doesn't go below 0
                    
                    $update_sql = "UPDATE products SET stock_quantity = ? WHERE product_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ii", $new_quantity, $product_id);
                    $update_stmt->execute();
                }
            }
            
            // Update user address if needed
            if (empty($user['user_address']) || isset($_POST['update_address'])) {
                $full_address = $address . ', ' . $city . ', ' . $postal_code;
                $update_user_sql = "UPDATE users SET user_address = ?, phone_number = ? WHERE user_id = ?";
                $update_user_stmt = $conn->prepare($update_user_sql);
                $update_user_stmt->bind_param("ssi", $full_address, $phone, $_SESSION['user_id']);
                $update_user_stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart after successful order
            $_SESSION['cart'] = [];
            $_SESSION['order_success'] = true;
            $_SESSION['invoice_number'] = $invoice_number;
            
            // Redirect to order confirmation page
            header("Location: order_confirmation.php?order_id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "An error occurred: " . $e->getMessage();
        }
    }
}

// Include header
require("pages/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Plant Shop</title>
    <link rel="stylesheet" href="css/Product.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .checkout-section {
            margin-bottom: 30px;
        }
        .form-group label.required:after {
            content: " *";
            color: red;
        }
        .order-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            position: sticky;
            top: 20px;
        }
        .cart-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <h1 class="mb-4"><i class="fas fa-credit-card"></i> Checkout</h1>
        
        <!-- Error Message -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Checkout Form -->
        <form method="POST" action="">
            <div class="row">
                <!-- Customer Information & Shipping -->
                <div class="col-lg-8">
                    <!-- Shipping Information -->
                    <div class="checkout-section card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-shipping-fast"></i> Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name" class="required">Full Name</label>
                                        <input type="text" id="full_name" name="full_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="required">Email</label>
                                        <input type="email" id="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['email_address'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="required">Street Address</label>
                                <input type="text" id="address" name="address" class="form-control" 
                                       value="<?php 
                                           $address_parts = explode(',', $user['user_address'] ?? '');
                                           echo htmlspecialchars(trim($address_parts[0] ?? '')); 
                                       ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city" class="required">City</label>
                                        <input type="text" id="city" name="city" class="form-control" 
                                               value="<?php 
                                                   $address_parts = explode(',', $user['user_address'] ?? '');
                                                   echo htmlspecialchars(trim($address_parts[1] ?? '')); 
                                               ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="postal_code" class="required">Postal Code</label>
                                        <input type="text" id="postal_code" name="postal_code" class="form-control" 
                                               value="<?php 
                                                   $address_parts = explode(',', $user['user_address'] ?? '');
                                                   echo htmlspecialchars(trim($address_parts[2] ?? '')); 
                                               ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="required">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" required>
                            </div>
                            
                            <?php if (!empty($user['user_address'])): ?>
                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="update_address" name="update_address">
                                <label class="form-check-label" for="update_address">Update my saved address</label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="checkout-section card mt-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-money-check-alt"></i> Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_cod" value="cod" checked>
                                <label class="form-check-label" for="payment_cod">
                                    <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                                </label>
                                <div class="payment-details mt-2 ml-4" id="cod_details" style="display: none;">
                                    <small class="text-muted">Pay with cash upon delivery of your order.</small>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_card" value="card">
                                <label class="form-check-label" for="payment_card">
                                    <i class="fas fa-credit-card"></i> Credit/Debit Card
                                </label>
                                <div class="payment-details mt-2 ml-4" id="card_details" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label for="card_number" class="required">Card Number</label>
                                                <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="card_cvv" class="required">CVV</label>
                                                <input type="text" id="card_cvv" name="card_cvv" class="form-control" placeholder="123" maxlength="4">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="card_name" class="required">Cardholder Name</label>
                                                <input type="text" id="card_name" name="card_name" class="form-control" placeholder="John Doe">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="card_exp" class="required">Expiration Date</label>
                                                <input type="text" id="card_exp" name="card_exp" class="form-control" placeholder="MM/YY" maxlength="5">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_upi" value="upi">
                                <label class="form-check-label" for="payment_upi">
                                    <i class="fas fa-mobile-alt"></i> UPI
                                </label>
                                <div class="payment-details mt-2 ml-4" id="upi_details" style="display: none;">
                                    <div class="form-group">
                                        <label for="upi_id" class="required">UPI ID</label>
                                        <input type="text" id="upi_id" name="upi_id" class="form-control" placeholder="username@bankname">
                                        <small class="form-text text-muted">Enter your UPI ID (e.g., yourname@okbank, mobile@paytm)</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_qr" value="qr">
                                <label class="form-check-label" for="payment_qr">
                                    <i class="fas fa-qrcode"></i> QR Code Payment
                                </label>
                                <div class="payment-details mt-2 ml-4" id="qr_details" style="display: none;">
                                    <div class="text-center py-3">
                                        <img src="images/payment-qr.png" alt="Payment QR Code" class="img-fluid mb-2" style="max-width: 200px;">
                                        <p class="mb-0">Scan this QR code with any UPI app to make payment</p>
                                        <small class="text-muted">After payment, please enter your UPI transaction ID</small>
                                        <div class="form-group mt-2">
                                            <label for="qr_transaction_id" class="required">Transaction ID</label>
                                            <input type="text" id="qr_transaction_id" name="qr_transaction_id" class="form-control" placeholder="Enter UPI transaction ID">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_netbanking" value="netbanking">
                                <label class="form-check-label" for="payment_netbanking">
                                    <i class="fas fa-university"></i> Net Banking
                                </label>
                                <div class="payment-details mt-2 ml-4" id="netbanking_details" style="display: none;">
                                    <div class="form-group">
                                        <label for="bank_select" class="required">Select Your Bank</label>
                                        <select class="form-control" id="bank_select" name="bank_name">
                                            <option value="">-- Select Bank --</option>
                                            <option value="sbi">State Bank of India</option>
                                            <option value="hdfc">HDFC Bank</option>
                                            <option value="icici">ICICI Bank</option>
                                            <option value="axis">Axis Bank</option>
                                            <option value="pnb">Punjab National Bank</option>
                                            <option value="other">Other Banks</option>
                                        </select>
                                    </div>
                                    <small class="form-text text-muted">You will be redirected to your bank's secure payment page after placing order.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="order-summary">
                        <h5 class="mb-4">Order Summary</h5>
                        
                        <!-- Cart Items Preview -->
                        <div class="cart-items mb-4">
                            <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                <div class="cart-item d-flex align-items-center">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" class="cart-item-image mr-3" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="cart-item-image mr-3 bg-light d-flex align-items-center justify-content-center">
                                            <span class="text-muted small">No Image</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <span class="text-muted small">
                                            <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?>
                                        </span>
                                    </div>
                                    <div class="ml-3 text-right">
                                        <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Cost Breakdown -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>$0.00</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax:</span>
                            <span>$<?php echo number_format($cart_total * 0.05, 2); ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong>$<?php echo number_format($cart_total * 1.05, 2); ?></strong>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" name="place_order" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-check-circle"></i> Place Order
                        </button>
                        
                        <a href="cart.php" class="btn btn-outline-secondary btn-block mt-2">
                            <i class="fas fa-shopping-cart"></i> Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- JavaScript dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    // Show/hide payment method details
    $(document).ready(function() {
        // Show selected payment method details
        function showSelectedPaymentDetails() {
            // Hide all payment details first
            $('.payment-details').slideUp();
            
            // Show the selected payment method details
            const selectedMethod = $('input[name="payment_method"]:checked').val();
            $('#' + selectedMethod + '_details').slideDown();
        }
        
        // Initial setup
        showSelectedPaymentDetails();
        
        // Change event
        $('input[name="payment_method"]').change(function() {
            showSelectedPaymentDetails();
        });
        
        // Format credit card input
        $('#card_number').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            $(this).val(formattedValue.substring(0, 19));
        });
        
        // Format expiration date (MM/YY)
        $('#card_exp').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            $(this).val(value);
        });
    });
</script>
    
</body>
</html>