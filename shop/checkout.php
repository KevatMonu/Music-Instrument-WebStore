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
$error = '';
$success = '';
$cartItems = [];
$totalPrice = 0;
$discount_amount = 0; // Default discount is 0
$order_id = null; // Initialize order_id

// Fetch user information including address details
$stmt = $conn->prepare("SELECT user_id, full_name, email_address, phone_number, user_address, created_on FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Process user address if it exists
$address_parts = [
    'address' => '',
    'city' => '',
    'state' => '',
    'pincode' => ''
];

if (!empty($user_data['user_address'])) {
    // Assuming the address is stored in format: "address, city, state - pincode"
    $address_string = $user_data['user_address'];

    // Extract pincode (if it exists after a dash)
    if (strpos($address_string, ' - ') !== false) {
        $parts = explode(' - ', $address_string);
        $address_string = $parts[0];
        $address_parts['pincode'] = trim($parts[1]);
    }

    // Extract state, city, and address
    $comma_parts = explode(',', $address_string);
    $count = count($comma_parts);

    if ($count >= 3) {
        $address_parts['state'] = trim($comma_parts[$count - 1]);
        $address_parts['city'] = trim($comma_parts[$count - 2]);
        $address_parts['address'] = trim(implode(',', array_slice($comma_parts, 0, $count - 2)));
    } elseif ($count == 2) {
        $address_parts['state'] = trim($comma_parts[1]);
        $address_parts['city'] = trim($comma_parts[0]);
    } elseif ($count == 1) {
        $address_parts['address'] = trim($comma_parts[0]);
    }
}

// Fetch Cart Items
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        // Fetch product details from database
        $stmt = $conn->prepare("SELECT product_id, product_name, product_price FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $row['quantity'] = $quantity;
            $row['subtotal'] = $row['product_price'] * $quantity;
            $totalPrice += $row['subtotal'];
            $cartItems[] = $row;
        }
    }
}

// Process order submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // Get form data
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $payment_method = $_POST['payment_method'];
    $order_type = "buy"; // Default to buy

    // Get payment details based on payment method
    $payment_details = [];

    switch ($payment_method) {
        case 'Credit Card':
            $payment_details = [
                'card_number' => $_POST['card_number'] ?? '',
                'card_name' => $_POST['card_name'] ?? '',
                'card_expiry' => $_POST['card_expiry'] ?? '',
                'card_cvv' => $_POST['card_cvv'] ?? ''
            ];
            break;
        case 'Debit Card':
            $payment_details = [
                'debit_card_number' => $_POST['debit_card_number'] ?? '',
                'debit_card_name' => $_POST['debit_card_name'] ?? '',
                'debit_card_expiry' => $_POST['debit_card_expiry'] ?? '',
                'debit_card_cvv' => $_POST['debit_card_cvv'] ?? ''
            ];
            break;
        case 'UPI':
            $payment_details = [
                'upi_id' => $_POST['upi_id'] ?? '',
            ];
            break;
        case 'Net Banking':
            $payment_details = [
                'bank_name' => $_POST['bank_name'] ?? '',
                'account_number' => $_POST['account_number'] ?? ''
            ];
            break;
        case 'Wallet':
            $payment_details = [
                'wallet_type' => $_POST['wallet_type'] ?? '',
                'wallet_number' => $_POST['wallet_number'] ?? ''
            ];
            break;
    }

    // Serialize payment details to store in database
    $payment_details_json = json_encode($payment_details);

    // Validate input
    if (empty($address) || empty($city) || empty($state) || empty($pincode)) {
        $error = "All shipping details are required.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // 1. Create order in orders table
            $order_date = date("Y-m-d H:i:s");
            $status = "completed"; // Default status
            $offer_ref = null; // Set to actual offer reference if using offers

            // Insert order into orders table
            $stmt = $conn->prepare("INSERT INTO orders (user_ref, total_cost, order_created, order_status, order_type, offer_ref, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("idsssid", $user_id, $totalPrice, $order_date, $status, $order_type, $offer_ref, $discount_amount);
            $stmt->execute();

            $order_id = $conn->insert_id;

            // 2. Store shipping information in order_shipping table
            $shipping_address = $address . ', ' . $city . ', ' . $state . ' - ' . $pincode;
            $stmt = $conn->prepare("INSERT INTO order_shipping (order_ref, shipping_address, shipping_city, shipping_state, shipping_pincode) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $order_id, $address, $city, $state, $pincode);
            $stmt->execute();

            // Store shipping information in a session variable
            $_SESSION['last_order_shipping'] = [
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'pincode' => $pincode
            ];

            // Add payment information to payments table
            $payment_status = "success"; // Default status
            $payment_mode = '';

            // Convert the payment method to match your enum values
            switch ($payment_method) {
                case 'Credit Card':
                    $payment_mode = 'credit_card';
                    break;
                case 'Debit Card':
                    $payment_mode = 'debit_card';
                    break;
                case 'UPI':
                    $payment_mode = 'UPI';
                    break;
                case 'Net Banking':
                    $payment_mode = 'net_banking';
                    break;
                case 'Wallet':
                    $payment_mode = 'wallet';
                    break;
                case 'Cash on Delivery':
                default:
                    $payment_mode = 'cash';
                    break;
            }

            // Insert into payments table with payment details
            $stmt = $conn->prepare("INSERT INTO payments (order_ref, payment_mode, payment_amount, payment_status, payment_details) 
                                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdss", $order_id, $payment_mode, $totalPrice, $payment_status, $payment_details_json);
            $stmt->execute();

            // 3. Add order items
            foreach ($cartItems as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['product_price'];

                // Insert into order_items table
                $stmt = $conn->prepare("INSERT INTO order_items (order_ref, product_ref, item_quantity, item_price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                $stmt->execute();
            }

            // 5. Commit transaction
            $conn->commit();

            // 6. Clear cart
            $_SESSION['cart'] = [];

            // 7. Set success message
            $success = "Order placed successfully! Your order ID is: " . $order_id;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error placing order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Music Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>

<body>

    <?php include 'pages/header.php'; ?>

    <main class="main-content checkout-page">
        <div class="page-wrapper">
            <h1 class="section-title">Checkout</h1>

            <!-- Checkout Steps -->
            <div class="checkout-progress">
                <div class="progress-step">
                    <div class="step-icon">1</div>
                    <div class="step-label">Shopping Cart</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step active">
                    <div class="step-icon">2</div>
                    <div class="step-label">Checkout Details</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step <?php echo $success ? 'active' : ''; ?>">
                    <div class="step-icon">3</div>
                    <div class="step-label">Order Complete</div>
                </div>
            </div>

            <?php if ($success): ?>
                <!-- Order Success -->
                <div class="order-success">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h2 class="success-title">Order Placed Successfully!</h2>
                    <p>Thank you for your purchase. You will receive a confirmation email shortly.</p>

                    <div class="order-details-summary">
                        <div class="detail-item">
                            <div class="detail-label">Order ID:</div>
                            <div class="detail-value"><?php echo htmlspecialchars(substr($success, strrpos($success, ':') + 2)); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Shipping Address:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($_SESSION['last_order_shipping']['address']); ?>,
                                <?php echo htmlspecialchars($_SESSION['last_order_shipping']['city']); ?>,
                                <?php echo htmlspecialchars($_SESSION['last_order_shipping']['state']); ?> -
                                <?php echo htmlspecialchars($_SESSION['last_order_shipping']['pincode']); ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Amount Paid:</div>
                            <div class="detail-value">₹<?php echo number_format($totalPrice - $discount_amount, 2); ?></div>
                        </div>
                    </div>

                    <div class="success-actions">
                        <a href="index.php" class="btn secondary-btn">
                            <i class="fas fa-home"></i> Continue Shopping
                        </a>
                        <a href="invoice.php?order_id=<?php echo htmlspecialchars(substr($success, strrpos($success, ':') + 2)); ?>" class="btn primary-btn">
                            <i class="fas fa-file-invoice"></i> View Bill
                        </a>
                    </div>
                </div>
            <?php elseif (empty($cartItems)): ?>
                <!-- Empty Cart Message -->
                <div class="empty-cart-message">
                    <i class="fas fa-shopping-cart cart-icon"></i>
                    <h2>Your Cart is Empty</h2>
                    <p>Add some items to your cart to continue checkout.</p>
                    <a href="products.php" class="btn primary-btn">
                        <i class="fas fa-shopping-basket"></i> Browse Products
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert error-alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Checkout Container -->
                <div class="checkout-container">
                    <!-- Checkout Form -->
                    <div class="checkout-form-container">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="checkout-form">
                            <!-- Shipping Information -->
                            <div class="form-block">
                                <div class="block-header">
                                    <i class="fas fa-shipping-fast"></i>
                                    <h3>Shipping Information</h3>
                                </div>

                                <div class="form-field">
                                    <label for="address">Street Address</label>
                                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address_parts['address']); ?>" required>
                                </div>

                                <div class="field-row">
                                    <div class="form-field">
                                        <label for="city">City</label>
                                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($address_parts['city']); ?>" required>
                                    </div>

                                    <div class="form-field">
                                        <label for="state">State</label>
                                        <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($address_parts['state']); ?>" required>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label for="pincode">PIN Code</label>
                                    <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($address_parts['pincode']); ?>" required>
                                </div>
                            </div>

                            <!-- Payment Information -->
                            <div class="form-block">
                                <div class="block-header">
                                    <i class="fas fa-credit-card"></i>
                                    <h3>Payment Method</h3>
                                </div>

                                <div class="payment-methods">
                                    <div class="payment-method">
                                        <input type="radio" id="credit-card" name="payment_method" value="Credit Card" checked>
                                        <label for="credit-card" class="method-label">
                                            <i class="far fa-credit-card"></i>
                                            <span>Credit Card</span>
                                        </label>
                                    </div>

                                    <div class="payment-method">
                                        <input type="radio" id="debit-card" name="payment_method" value="Debit Card">
                                        <label for="debit-card" class="method-label">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Debit Card</span>
                                        </label>
                                    </div>

                                    <div class="payment-method">
                                        <input type="radio" id="upi" name="payment_method" value="UPI">
                                        <label for="upi" class="method-label">
                                            <i class="fas fa-mobile-alt"></i>
                                            <span>UPI</span>
                                        </label>
                                    </div>

                                    <div class="payment-method">
                                        <input type="radio" id="net-banking" name="payment_method" value="Net Banking">
                                        <label for="net-banking" class="method-label">
                                            <i class="fas fa-university"></i>
                                            <span>Net Banking</span>
                                        </label>
                                    </div>

                                    <div class="payment-method">
                                        <input type="radio" id="wallet" name="payment_method" value="Wallet">
                                        <label for="wallet" class="method-label">
                                            <i class="fas fa-wallet"></i>
                                            <span>Wallet</span>
                                        </label>
                                    </div>

                                    <div class="payment-method">
                                        <input type="radio" id="cod" name="payment_method" value="Cash on Delivery">
                                        <label for="cod" class="method-label">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Cash on Delivery</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Payment Method Details (dynamically shown based on selected method) -->
                                <div id="payment-details-container">
                                    <!-- Credit Card Details (Default) -->
                                    <div id="credit-card-details" class="payment-method-details">
                                        <div class="form-field">
                                            <label for="card-number">Card Number</label>
                                            <input type="text" id="card-number" name="card_number" placeholder="XXXX XXXX XXXX XXXX">
                                        </div>

                                        <div class="form-field">
                                            <label for="card-name">Name on Card</label>
                                            <input type="text" id="card-name" name="card_name" placeholder="John Doe">
                                        </div>

                                        <div class="field-row">
                                            <div class="form-field">
                                                <label for="card-expiry">Expiration Date</label>
                                                <input type="text" id="card-expiry" name="card_expiry" placeholder="MM/YY">
                                            </div>

                                            <div class="form-field">
                                                <label for="card-cvv">CVV</label>
                                                <input type="text" id="card-cvv" name="card_cvv" placeholder="XXX">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Debit Card Details -->
                                    <div id="debit-card-details" class="payment-method-details" style="display: none;">
                                        <div class="form-field">
                                            <label for="debit-card-number">Card Number</label>
                                            <input type="text" id="debit-card-number" name="debit_card_number" placeholder="XXXX XXXX XXXX XXXX">
                                        </div>

                                        <div class="form-field">
                                            <label for="debit-card-name">Name on Card</label>
                                            <input type="text" id="debit-card-name" name="debit_card_name" placeholder="John Doe">
                                        </div>

                                        <div class="field-row">
                                            <div class="form-field">
                                                <label for="debit-card-expiry">Expiration Date</label>
                                                <input type="text" id="debit-card-expiry" name="debit_card_expiry" placeholder="MM/YY">
                                            </div>

                                            <div class="form-field">
                                                <label for="debit-card-cvv">CVV</label>
                                                <input type="text" id="debit-card-cvv" name="debit_card_cvv" placeholder="XXX">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- UPI Details -->
                                    <div id="upi-details" class="payment-method-details" style="display: none;">
                                        <div class="form-field">
                                            <label for="upi-id">UPI ID</label>
                                            <input type="text" id="upi-id" name="upi_id" placeholder="name@upi">
                                        </div>
                                    </div>

                                    <!-- Net Banking Details -->
                                    <div id="net-banking-details" class="payment-method-details" style="display: none;">
                                        <div class="form-field">
                                            <label for="bank-name">Bank Name</label>
                                            <select id="bank-name" name="bank_name">
                                                <option value="">Select Bank</option>
                                                <option value="SBI">State Bank of India</option>
                                                <option value="HDFC">HDFC Bank</option>
                                                <option value="ICICI">ICICI Bank</option>
                                                <option value="Axis">Axis Bank</option>
                                                <option value="Kotak">Kotak Mahindra Bank</option>
                                            </select>
                                        </div>

                                        <div class="form-field">
                                            <label for="account-number">Account Number</label>
                                            <input type="text" id="account-number" name="account_number" placeholder="Account Number">
                                        </div>
                                    </div>

                                    <!-- Wallet Details -->
                                    <div id="wallet-details" class="payment-method-details" style="display: none;">
                                        <div class="form-field">
                                            <label for="wallet-type">Wallet Type</label>
                                            <select id="wallet-type" name="wallet_type">
                                                <option value="">Select Wallet</option>
                                                <option value="Paytm">Paytm</option>
                                                <option value="PhonePe">PhonePe</option>
                                                <option value="Amazon Pay">Amazon Pay</option>
                                                <option value="Google Pay">Google Pay</option>
                                                <option value="MobiKwik">MobiKwik</option>
                                            </select>
                                        </div>

                                        <div class="form-field">
                                            <label for="wallet-number">Mobile Number</label>
                                            <input type="text" id="wallet-number" name="wallet_number" placeholder="Mobile Number">
                                        </div>
                                    </div>

                                    <!-- COD Details -->
                                    <div id="cod-details" class="payment-method-details" style="display: none;">
                                        <p class="payment-info-text">
                                            <i class="fas fa-info-circle"></i>
                                            You will pay at the time of delivery. Additional COD charges may apply.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="cart.php" class="btn secondary-btn">
                                    <i class="fas fa-arrow-left"></i> Back to Cart
                                </a>
                                <button type="submit" name="place_order" class="btn primary-btn">
                                    <i class="fas fa-check"></i> Place Order
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-header">
                            <h3>Order Summary</h3>
                        </div>

                        <div class="summary-products">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="summary-product-item">
                                    <div class="product-details">
                                        <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="product-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                    </div>
                                    <div class="product-price">₹<?php echo number_format($item['subtotal'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-totals">
                            <div class="summary-row">
                                <div class="row-label">Subtotal:</div>
                                <div class="row-value">₹<?php echo number_format($totalPrice, 2); ?></div>
                            </div>

                            <?php if ($discount_amount > 0): ?>
                                <div class="summary-row discount">
                                    <div class="row-label">Discount:</div>
                                    <div class="row-value">-₹<?php echo number_format($discount_amount, 2); ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="summary-row">
                                <div class="row-label">Shipping:</div>
                                <div class="row-value">Free</div>
                            </div>

                            <div class="summary-row total">
                                <div class="row-label">Total:</div>
                                <div class="row-value">₹<?php echo number_format($totalPrice - $discount_amount, 2); ?></div>
                            </div>
                        </div>

                        <div class="coupon-section">
                            <h4>Have a Coupon?</h4>
                            <div class="coupon-form">
                                <input type="text" id="coupon-code" placeholder="Enter coupon code">
                                <button id="apply-coupon" class="btn secondary-btn">Apply</button>
                            </div>
                            <div id="coupon-message"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'pages/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Payment method switching
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const paymentDetailsContainers = document.querySelectorAll('.payment-method-details');

            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    // Hide all payment details first
                    paymentDetailsContainers.forEach(container => {
                        container.style.display = 'none';
                    });

                    // Show the selected payment method details
                    const selectedMethodId = this.id;
                    const detailsContainerId = selectedMethodId + '-details';
                    document.getElementById(detailsContainerId).style.display = 'block';
                });
            });

            // Coupon application (demo functionality)
            const applyButton = document.getElementById('apply-coupon');
            if (applyButton) {
                applyButton.addEventListener('click', function() {
                    const couponCode = document.getElementById('coupon-code').value.trim();
                    const couponMessage = document.getElementById('coupon-message');

                    if (couponCode.length === 0) {
                        couponMessage.innerHTML = '<span class="error-text">Please enter a coupon code</span>';
                        return;
                    }

                    // Simulate coupon validation (in a real app, this would be an AJAX call to the server)
                    if (couponCode.toUpperCase() === 'MUSIC10') {
                        couponMessage.innerHTML = '<span class="success-text">Coupon applied: 10% discount</span>';
                    } else if (couponCode.toUpperCase() === 'WELCOME20') {
                        couponMessage.innerHTML = '<span class="success-text">Coupon applied: 20% discount</span>';
                    } else {
                        couponMessage.innerHTML = '<span class="error-text">Invalid coupon code</span>';
                    }
                });
            }
        });
    </script>
</body>

</html>