<?php
session_start();
include 'db_connection.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productId = $_GET['id'];

// Handle add to cart action
if (isset($_GET['action']) && $_GET['action'] == "add" && isset($_GET['id'])) {
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    
    // Ensure quantity is at least 1
    if ($quantity < 1) $quantity = 1;
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }

    $totalItems = array_sum($_SESSION['cart']);

    if (isset($_GET['ajax'])) {
        echo json_encode(["success" => true, "totalItems" => $totalItems]);
        exit;
    }
    
    // Redirect to prevent form resubmission
    header("Location: product_detail.php?id=$productId&added=1");
    exit;
}

// Get product details
$query = "SELECT p.*, c.category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_ref = c.category_id 
          WHERE p.product_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Product not found
    header('Location: products.php');
    exit;
}

$product = $result->fetch_assoc();

// Get related products based on category
$relatedQuery = "SELECT p.product_id, p.product_name, p.product_price, p.product_image, p.image_type 
                FROM products p 
                WHERE p.category_ref = ? AND p.product_id != ? 
                LIMIT 4";

$relatedStmt = $conn->prepare($relatedQuery);
$relatedStmt->bind_param("ii", $product['category_ref'], $productId);
$relatedStmt->execute();
$relatedResult = $relatedStmt->get_result();

// Count total items in cart
$totalItems = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// Function to handle product image display
function getProductImageSrc($product) {
    // If product_image is a file path (similar to profile.php approach)
    if (isset($product['product_image']) && !empty($product['product_image']) && strpos($product['product_image'], '/') !== false) {
        return htmlspecialchars($product['product_image']);
    }
    // If product_image is binary data stored in DB
    else if (isset($product['product_image']) && !empty($product['product_image'])) {
        return 'data:' . $product['image_type'] . ';base64,' . base64_encode($product['product_image']);
    }
    // Default placeholder
    else {
        return 'assets/img/product-placeholder.jpg';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Music Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/product_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Header -->
    <?php include 'pages/header.php'; ?>
    
    <div class="main-content">
        <div class="product-detail-container">
            <!-- Breadcrumb navigation -->
            <div class="product-breadcrumb">
                <a href="index.php">Home</a> &gt; 
                <a href="products.php">Products</a> &gt; 
                <a href="products.php?category=<?php echo $product['category_ref']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> &gt; 
                <span><?php echo htmlspecialchars($product['product_name']); ?></span>
            </div>
            
            <?php if (isset($_GET['added']) && $_GET['added'] == 1): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Product added to your cart successfully!
            </div>
            <?php endif; ?>
            
            <div class="product-detail">
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="product-main-image" id="main-image">
                        <img src="<?php echo getProductImageSrc($product); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    </div>
                    
                    <div class="product-thumbnails">
                        <!-- For now, just show the same image in thumbnails -->
                        <!-- In a real implementation, you would loop through multiple product images -->
                        <div class="product-thumbnail active">
                            <img src="<?php echo getProductImageSrc($product); ?>" alt="Thumbnail">
                        </div>
                        <!-- Add more thumbnails here if you have multiple images -->
                    </div>
                </div>
                
                <!-- Product Information -->
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    
                    <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    
                    <div class="product-price-container">
                        <span class="product-price">₹<?php echo number_format($product['product_price'], 2); ?></span>
                        <?php if (isset($product['rental_cost']) && $product['rental_cost']): ?>
                            <span class="product-rental">Rental: ₹<?php echo number_format($product['rental_cost'], 2); ?>/day</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['product_description'])); ?>
                    </div>
                    
                    <div class="product-stock <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        <?php endif; ?>
                    </div>
                    
                    <form action="product_detail.php" method="GET" id="add-to-cart-form">
                        <input type="hidden" name="id" value="<?php echo $productId; ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="product-actions">
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" id="decrease-quantity">-</button>
                                <input type="number" name="quantity" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                <button type="button" class="quantity-btn" id="increase-quantity">+</button>
                            </div>
                            
                            <button type="submit" class="cart-btn" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            
                            <button type="button" class="buy-now-btn" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                Buy Now
                            </button>
                        </div>
                    </form>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <div class="meta-label">SKU:</div>
                            <div class="meta-value">MST-<?php echo $product['product_id']; ?></div>
                        </div>
                        
                        <div class="meta-item">
                            <div class="meta-label">Category:</div>
                            <div class="meta-value"><?php echo htmlspecialchars($product['category_name']); ?></div>
                        </div>
                        
                        <?php if (isset($product['brand']) && !empty($product['brand'])): ?>
                        <div class="meta-item">
                            <div class="meta-label">Brand:</div>
                            <div class="meta-value"><?php echo htmlspecialchars($product['brand']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="product-share">
                            <div class="share-label">Share:</div>
                            <div class="share-links">
                                <a href="#" class="share-link"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="share-link"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="share-link"><i class="fab fa-pinterest"></i></a>
                                <a href="#" class="share-link"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if ($relatedResult->num_rows > 0): ?>
            <div class="related-products">
                <h2 class="section-title">Related Products</h2>
                
                <div class="related-grid">
                    <?php while ($related = $relatedResult->fetch_assoc()): ?>
                    <div class="related-card">
                        <div class="related-image">
                            <img src="<?php echo getProductImageSrc($related); ?>" alt="<?php echo htmlspecialchars($related['product_name']); ?>">
                        </div>
                        <div class="related-info">
                            <h3 class="related-name"><?php echo htmlspecialchars($related['product_name']); ?></h3>
                            <div class="related-price">$<?php echo number_format($related['product_price'], 2); ?></div>
                            <a href="product_detail.php?id=<?php echo $related['product_id']; ?>" class="related-link">View Details</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'pages/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity buttons functionality
        const quantityInput = document.getElementById('quantity');
        const decreaseBtn = document.getElementById('decrease-quantity');
        const increaseBtn = document.getElementById('increase-quantity');
        const maxQuantity = <?php echo $product['stock_quantity']; ?>;
        
        decreaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
        
        increaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue < maxQuantity) {
                quantityInput.value = currentValue + 1;
            }
        });
        
        // Validate quantity on manual input
        quantityInput.addEventListener('change', function() {
            let currentValue = parseInt(this.value);
            
            if (isNaN(currentValue) || currentValue < 1) {
                this.value = 1;
            } else if (currentValue > maxQuantity) {
                this.value = maxQuantity;
            }
        });
        
        // Buy Now button
        const buyNowBtn = document.querySelector('.buy-now-btn');
        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', function() {
                document.getElementById('add-to-cart-form').action = 'checkout.php';
                document.getElementById('add-to-cart-form').submit();
            });
        }
        
        // Ajax add to cart
        const addToCartForm = document.getElementById('add-to-cart-form');
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('ajax', true);
            
            // Convert FormData to URL params
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            fetch(`product_detail.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count in header
                        const cartCount = document.querySelector('.cart-count');
                        
                        if (cartCount) {
                            cartCount.textContent = data.totalItems;
                        }
                        
                        // Show success message
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success';
                        successAlert.innerHTML = '<i class="fas fa-check-circle"></i> Product added to your cart successfully!';
                        
                        // Insert alert at the top of product detail
                        const productDetailContainer = document.querySelector('.product-detail-container');
                        productDetailContainer.insertBefore(successAlert, productDetailContainer.firstChild);
                        
                        // Remove the alert after 3 seconds
                        setTimeout(() => {
                            successAlert.remove();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                });
        });
        
        // Image gallery functionality (if multiple images)
        const thumbnails = document.querySelectorAll('.product-thumbnail');
        const mainImage = document.getElementById('main-image').querySelector('img');
        
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                // Remove active class from all thumbnails
                thumbnails.forEach(thumb => thumb.classList.remove('active'));
                
                // Add active class to clicked thumbnail
                this.classList.add('active');
                
                // Update main image
                const thumbnailImg = this.querySelector('img');
                mainImage.src = thumbnailImg.src;
            });
        });
    });
    </script>
</body>
</html>