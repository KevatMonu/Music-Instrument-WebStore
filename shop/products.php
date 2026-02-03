<?php
session_start();
include 'db_connection.php';

// Handle add to cart action
if (isset($_GET['action']) && $_GET['action'] == "add" && isset($_GET['id'])) {
    $productId = $_GET['id'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += 1; // Increase quantity
    } else {
        $_SESSION['cart'][$productId] = 1; // Add new item
    }

    $totalItems = array_sum($_SESSION['cart']);

    echo json_encode(["success" => true, "totalItems" => $totalItems]);
    exit;
}

// Function to remove query parameters
function removeQueryParam($paramsToRemove)
{
    $currentUrl = parse_url($_SERVER['REQUEST_URI']);
    $path = $currentUrl['path'];

    if (isset($currentUrl['query'])) {
        parse_str($currentUrl['query'], $queryParams);

        if (is_array($paramsToRemove)) {
            foreach ($paramsToRemove as $param) {
                unset($queryParams[$param]);
            }
        } else {
            unset($queryParams[$paramsToRemove]);
        }

        if (count($queryParams) > 0) {
            return $path . '?' . http_build_query($queryParams);
        }
    }

    return $path;
}

// Get all categories for filter
$categoryQuery = "SELECT category_id, category_name FROM categories ORDER BY category_name";
$categoriesResult = $conn->query($categoryQuery);
$categories = [];
if ($categoriesResult && $categoriesResult->num_rows > 0) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[$row['category_id']] = $row['category_name'];
    }
}

// Get price range
$priceRangeQuery = "SELECT MIN(product_price) as min_price, MAX(product_price) as max_price FROM products";
$priceRangeResult = $conn->query($priceRangeQuery);
$minPrice = 0;
$maxPrice = 100000;
if ($priceRangeResult && $priceRangeResult->num_rows > 0) {
    $priceRange = $priceRangeResult->fetch_assoc();
    $minPrice = floor($priceRange['min_price']);
    $maxPrice = ceil($priceRange['max_price']);
}

// Initialize filter variables
$searchQuery = '';
$selectedCategory = '';
$selectedAvailability = '';
$minPriceFilter = $minPrice;
$maxPriceFilter = $maxPrice;
$sortOption = '';

// Process filters
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchQuery = $_GET['search'];
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $selectedCategory = $_GET['category'];
}

if (isset($_GET['availability']) && !empty($_GET['availability'])) {
    $selectedAvailability = $_GET['availability'];
}

if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $minPriceFilter = $_GET['min_price'];
}

if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $maxPriceFilter = $_GET['max_price'];
}

if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    $sortOption = $_GET['sort'];
}

// Build query with all filters
$query = "SELECT p.product_id, p.product_name, p.product_description, p.product_price, 
          p.rental_cost, p.product_image, p.image_type, p.stock_quantity, c.category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_ref = c.category_id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $query .= " AND (p.product_name LIKE ? OR p.product_description LIKE ?)";
    $searchParam = "%{$searchQuery}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if (!empty($selectedCategory)) {
    $query .= " AND p.category_ref = ?";
    $params[] = $selectedCategory;
    $types .= "i";
}

if (!empty($selectedAvailability)) {
    if ($selectedAvailability === 'in_stock') {
        $query .= " AND p.stock_quantity > 0";
    } else if ($selectedAvailability === 'out_of_stock') {
        $query .= " AND p.stock_quantity = 0";
    }
}

$query .= " AND p.product_price >= ? AND p.product_price <= ?";
$params[] = $minPriceFilter;
$params[] = $maxPriceFilter;
$types .= "dd";

// Add sorting
switch ($sortOption) {
    case 'price_low_high':
        $query .= " ORDER BY p.product_price ASC";
        break;
    case 'price_high_low':
        $query .= " ORDER BY p.product_price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.product_name ASC";
        break;
    case 'newest':
        $query .= " ORDER BY p.created_on DESC";
        break;
    default:
        $query .= " ORDER BY p.product_id DESC";
        break;
}

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($types) && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Count total items in cart
$totalItems = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// Count total products found
$productCount = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Store - Products</title>
    <link rel="stylesheet" href="./css/product.css">
    <link rel="stylesheet" href="./css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.7.0/nouislider.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.7.0/nouislider.min.js"></script>

</head>

<body>

    <div id="nav">
        <div class="nav1">
            <div class="logo">
                <img src="assets/home/image/logo.png" alt="" />
            </div>
            <div class="nav-item">
                <ul id="nav-item">
                    <a href="index.php">
                        <li>Home</li>
                    </a>
                    <a href="products.php">
                        <li>Product</li>
                    </a>
                    <a href="about.php">
                        <li>About Us</li>
                    </a>
                    <a href="contact.php">
                        <li>Contact Us</li>
                    </a>
                     <a href="chatbot.html">
                      <li>Recommendation</li>
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="sign-in.php">
                            <li>Sign In</li>
                        </a>
                    <?php endif; ?>
                   
                </ul>
            </div>
        </div>
        <div class="nav2">
            <div class="search-container">
                <form method="GET" action="products.php" class="search-form" id="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>

                    <!-- Hidden inputs to preserve filter state when searching -->
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory); ?>">
                    <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($minPriceFilter); ?>">
                    <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($maxPriceFilter); ?>">
                    <input type="hidden" name="availability" value="<?php echo htmlspecialchars($selectedAvailability); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortOption); ?>">
                </form>
                <?php if (!empty($searchQuery)): ?>
                    <a href="products.php" class="clear-search">Clear</a>
                <?php endif; ?>
            </div>
            <div class="nav2-icon">
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
   <!--Main Content -->
   
    <div class="main-content">

        <div class="container">
            <div class="product-container">
                <!-- Filter Sidebar Toggle for Mobile -->
                <button class="filter-toggle" id="filter-toggle">
                    <i class="fas fa-filter"></i> Show Filters
                </button>

                <!-- Filter Sidebar -->
                <div class="filter-sidebar mobile-hidden" id="filter-sidebar">
                    <form id="filter-form" method="GET" action="products.php">
                        <!-- Preserve search query if any -->
                        <?php if (!empty($searchQuery)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <?php endif; ?>

                        <!-- Sort By Filter -->
                        <div class="filter-section">
                            <div class="filter-title">Sort By</div>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="sort" value="price_low_high" <?php echo $sortOption == 'price_low_high' ? 'checked' : ''; ?>>
                                    Price: Low to High
                                </label>
                            </div>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="sort" value="price_high_low" <?php echo $sortOption == 'price_high_low' ? 'checked' : ''; ?>>
                                    Price: High to Low
                                </label>
                            </div>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="sort" value="name_asc" <?php echo $sortOption == 'name_asc' ? 'checked' : ''; ?>>
                                    Name: A to Z
                                </label>
                            </div>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="sort" value="newest" <?php echo $sortOption == 'newest' ? 'checked' : ''; ?>>
                                    Newest First
                                </label>
                            </div>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="filter-section">
                            <div class="filter-title">Price Range</div>
                            <div class="price-range">
                                <div id="price-slider"></div>
                                <div class="price-inputs">
                                    <div class="price-input">
                                        <input type="number" id="min-price-input" name="min_price" value="<?php echo $minPriceFilter; ?>" min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>">
                                    </div>
                                    <div class="price-input">
                                        <input type="number" id="max-price-input" name="max_price" value="<?php echo $maxPriceFilter; ?>" min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div class="filter-section">
                            <div class="filter-title">Categories</div>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="category" value="" <?php echo empty($selectedCategory) ? 'checked' : ''; ?>>
                                    All Categories
                                </label>
                            </div>
                            <?php foreach ($categories as $id => $name): ?>
                                <div class="filter-option">
                                    <label>
                                        <input type="radio" name="category" value="<?php echo $id; ?>" <?php echo $selectedCategory == $id ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Availability Filter -->
                        <div class="filter-section">
                            <div class="filter-title">Availability</div>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="availability" value="" <?php echo empty($selectedAvailability) ? 'checked' : ''; ?>>
                                    All Items
                                </label>
                            </div>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="availability" value="in_stock" <?php echo $selectedAvailability == 'in_stock' ? 'checked' : ''; ?>>
                                    In Stock
                                </label>
                            </div>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="availability" value="out_of_stock" <?php echo $selectedAvailability == 'out_of_stock' ? 'checked' : ''; ?>>
                                    Out of Stock
                                </label>
                            </div>
                        </div>

                        <!-- Apply/Reset Buttons -->
                        <button type="submit" class="filter-apply">Apply Filters</button>
                        <a href="products.php" class="filter-clear" style="display: block; text-align: center; margin-top: 10px;">Reset All Filters</a>
                    </form>
                </div>

                <!-- Product Content Area -->

            </div>
            <div class="product-content">
                <?php if (!empty($searchQuery) || !empty($selectedCategory) || !empty($selectedAvailability) || $minPriceFilter > $minPrice || $maxPriceFilter < $maxPrice || !empty($sortOption)): ?>
                    <div class="active-filters">
                        <?php if (!empty($searchQuery)): ?>
                            <div class="filter-tag">
                                Search: <?php echo htmlspecialchars($searchQuery); ?>
                                <a href="<?php echo removeQueryParam('search'); ?>"><i class="fas fa-times"></i></a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($selectedCategory)): ?>
                            <div class="filter-tag">
                                Category: <?php echo htmlspecialchars($categories[$selectedCategory]); ?>
                                <a href="<?php echo removeQueryParam('category'); ?>"><i class="fas fa-times"></i></a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($selectedAvailability)): ?>
                            <div class="filter-tag">
                                Availability: <?php echo $selectedAvailability == 'in_stock' ? 'In Stock' : 'Out of Stock'; ?>
                                <a href="<?php echo removeQueryParam('availability'); ?>"><i class="fas fa-times"></i></a>
                            </div>
                        <?php endif; ?>

                        <?php if ($minPriceFilter > $minPrice || $maxPriceFilter < $maxPrice): ?>
                            <div class="filter-tag">
                                Price: ₹<?php echo $minPriceFilter; ?> - $<?php echo $maxPriceFilter; ?>
                                <a href="<?php echo removeQueryParam(['min_price', 'max_price']); ?>"><i class="fas fa-times"></i></a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($sortOption)): ?>
                            <div class="filter-tag">
                                Sort: <?php
                                        switch ($sortOption) {
                                            case 'price_low_high':
                                                echo 'Price: Low to High';
                                                break;
                                            case 'price_high_low':
                                                echo 'Price: High to Low';
                                                break;
                                            case 'name_asc':
                                                echo 'Name: A to Z';
                                                break;
                                            case 'newest':
                                                echo 'Newest First';
                                                break;
                                        }
                                        ?>
                                <a href="<?php echo removeQueryParam('sort'); ?>"><i class="fas fa-times"></i></a>
                            </div>
                        <?php endif; ?>

                        <a href="products.php" class="filter-clear">Clear All</a>
                    </div>
                <?php endif; ?>

                <!-- Add a result count and sorting information -->
                <div class="product-header">
                    <div class="result-count">
                        <?php echo $productCount; ?> products found
                    </div>
                </div>

                <!-- Product Grid -->
                <?php if ($productCount > 0): ?>
                    <div class="product-grid">
                        <?php while ($product = $result->fetch_assoc()): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <a href="product_detail.php?id=<?php echo $product['product_id']; ?>">
                                        <?php if ($product['product_image']): ?>
                                            <?php 
                                            // Check if the product_image is a file path (starts with 'uploads/')
                                            if (strpos($product['product_image'], 'uploads/') === 0) {
                                                // It's a file path, display the image using the path
                                                echo '<img src="' . htmlspecialchars($product['product_image']) . '" alt="' . htmlspecialchars($product['product_name']) . '">';
                                            } else {
                                                // It's a BLOB, use base64 encoding (for backward compatibility)
                                                echo '<img src="data:' . $product['image_type'] . ';base64,' . base64_encode($product['product_image']) . '" alt="' . htmlspecialchars($product['product_name']) . '">';
                                            }
                                            ?>
                                        <?php else: ?>
                                            <img src="assets/img/product-placeholder.jpg" alt="No image available">
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="product-info">
                                    <h3>
                                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="product-name">
                                            <?php echo htmlspecialchars($product['product_name']); ?>
                                        </a>
                                    </h3>
                                    <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                    <div class="product-price">
                                        <span class="price">₹<?php echo number_format($product['product_price'], 2); ?></span>
                                        <?php if ($product['rental_cost']): ?>
                                            <span class="rental-price">Rental: ₹<?php echo number_format($product['rental_cost'], 2); ?>/day</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-actions">
                                        <button class="add-to-cart-btn" data-id="<?php echo $product['product_id']; ?>" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="view-details">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search fa-3x"></i>
                        <h2>No products found</h2>
                        <p>Try adjusting your filters or search terms</p>
                        <?php if (!empty($searchQuery) || !empty($selectedCategory) || !empty($selectedAvailability) || $minPriceFilter > $minPrice || $maxPriceFilter < $maxPrice): ?>
                            <a href="products.php" class="view-all-button">View All Products</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer: Keep your existing footer -->
    <?php include 'pages/footer.php'; ?>
    <script>
        $(document).ready(function() {
            // Initialize price slider
            const priceSlider = document.getElementById('price-slider');
            const minPriceInput = document.getElementById('min-price-input');
            const maxPriceInput = document.getElementById('max-price-input');

            if (priceSlider && minPriceInput && maxPriceInput) {
                noUiSlider.create(priceSlider, {
                    start: [<?php echo $minPriceFilter; ?>, <?php echo $maxPriceFilter; ?>],
                    connect: true,
                    step: 1,
                    range: {
                        'min': <?php echo $minPrice; ?>,
                        'max': <?php echo $maxPrice; ?>
                    },
                    format: {
                        to: function(value) {
                            return Math.round(value);
                        },
                        from: function(value) {
                            return Number(value);
                        }
                    }
                });

                priceSlider.noUiSlider.on('update', function(values, handle) {
                    if (handle === 0) {
                        minPriceInput.value = values[0];
                    } else {
                        maxPriceInput.value = values[1];
                    }
                });

                minPriceInput.addEventListener('change', function() {
                    priceSlider.noUiSlider.set([this.value, null]);
                });

                maxPriceInput.addEventListener('change', function() {
                    priceSlider.noUiSlider.set([null, this.value]);
                });
            }

            // Toggle filter sidebar on mobile
            const filterToggle = document.getElementById('filter-toggle');
            const filterSidebar = document.getElementById('filter-sidebar');

            if (filterToggle && filterSidebar) {
                filterToggle.addEventListener('click', function() {
                    filterSidebar.classList.toggle('mobile-hidden');

                    if (filterSidebar.classList.contains('mobile-hidden')) {
                        filterToggle.innerHTML = '<i class="fas fa-filter"></i> Show Filters';
                    } else {
                        filterToggle.innerHTML = '<i class="fas fa-times"></i> Hide Filters';
                    }
                });
            }

            // Auto-submit form when sort option changes
            const sortOptions = document.querySelectorAll('input[name="sort"]');
            sortOptions.forEach(option => {
                option.addEventListener('change', function() {
                    document.getElementById('filter-form').submit();
                });
            });

            // Add to cart functionality - Fixed implementation from Code 2
            $(".add-to-cart-btn").click(function(e) {
                e.preventDefault();
                let productId = $(this).data("id");
                let button = $(this);

                // Disable button temporarily to prevent multiple clicks
                button.prop('disabled', true);

                // Add loading effect
                button.html('<i class="fas fa-spinner fa-spin"></i> Adding...');

                $.get("products.php?action=add&id=" + productId, function(response) {
                    try {
                        let data = JSON.parse(response);
                        if (data.success) {
                            // Update cart count
                            if ($(".cart-count").length) {
                                $(".cart-count").text(data.totalItems);
                            } else {
                                $(".cart-link").append('<span class="cart-count">' + data.totalItems + '</span>');
                            }

                            // Show success feedback
                            button.html('<i class="fas fa-check"></i> Added!');

                            // Reset button after delay
                            setTimeout(function() {
                                button.html('<i class="fas fa-shopping-cart"></i> Add to Cart');
                                button.prop('disabled', false);
                            }, 1500);
                        }
                    } catch (e) {
                        console.error("Error parsing response:", e);
                        button.html('<i class="fas fa-shopping-cart"></i> Add to Cart');
                        button.prop('disabled', false);
                    }
                }).fail(function() {
                    // Handle error
                    button.html('<i class="fas fa-exclamation-circle"></i> Error');
                    setTimeout(function() {
                        button.html('<i class="fas fa-shopping-cart"></i> Add to Cart');
                        button.prop('disabled', false);
                    }, 1500);
                });
            });

            // Auto-submit search form when typing
            let searchTimeout;
            $(".search-input").on('input', function() {
                clearTimeout(searchTimeout);
                let query = $(this).val();

                if (query.length >= 3) {
                    searchTimeout = setTimeout(function() {
                        $("#search-form").submit();
                    }, 500);
                }
            });
        });



        /*wishlist*/
        // Wishlist toggle functionality
        const wishlistBtns = document.querySelectorAll('.wishlist-btn');
        wishlistBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');

                fetch('wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=toggle&product_id=' + productId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const heartIcon = this.querySelector('i');
                            if (data.status === 'added') {
                                heartIcon.classList.add('active');
                            } else {
                                heartIcon.classList.remove('active');
                            }
                        }
                    });
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
  const menuToggle = document.querySelector('.menu-toggle');
  
  // If there's no menu toggle button in the HTML, create one
  if (!menuToggle) {
    const nav1 = document.querySelector('.nav1');
    const newMenuToggle = document.createElement('button');
    newMenuToggle.className = 'menu-toggle';
    newMenuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
    nav1.prepend(newMenuToggle);
    
    // Add event listener to the newly created button
    newMenuToggle.addEventListener('click', toggleMenu);
  } else {
    // Add event listener to existing button
    menuToggle.addEventListener('click', toggleMenu);
  }
  
  function toggleMenu() {
    const navItems = document.getElementById('nav-item');
    navItems.classList.toggle('active');
  }
});
    </script>
</body>

</html>