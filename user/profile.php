<?php
session_start();
$conn = new mysqli("localhost", "root", "", "musicstore_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$userId = $_SESSION['user_id']; // Get logged-in user's ID

// Fetch user details
$stmt = $conn->prepare("SELECT user_id, full_name, email_address, user_role, user_image, created_on FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST['full_name'];
    $email = $_POST['email_address'];

    $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, email_address = ? WHERE user_id = ?");
    $updateStmt->bind_param("ssi", $fullName, $email, $userId);
    $updateStmt->execute();

    // Refresh user data after update
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | Music Store</title>
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <i class="fas fa-music"></i>
                    <span>Music Store</span>
                </div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="user_dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-guitar"></i>
                        <span>Browse Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cart.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>My Cart</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user_order_detail.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>My Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link active">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="support.php" class="nav-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Help & Support</span>
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

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="menu-toggle" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <h1 class="page-title">My Profile</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($user['full_name'], 0, 1); ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>
            </div>

            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-image">
                        <?php if ($user['user_image']): ?>
                            <img src="<?php echo htmlspecialchars($user['user_image']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <div class="profile-role"><?php echo htmlspecialchars($user['user_role']); ?></div>
                        <div class="member-since">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Member since <?php echo date('F j, Y', strtotime($user['created_on'])); ?></span>
                        </div>
                    </div>
                </div>

                <form method="POST" id="profileForm" class="profile-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled required>
                    </div>

                    <div class="form-group">
                        <label for="email_address">Email Address</label>
                        <input type="email" id="email_address" name="email_address" value="<?php echo htmlspecialchars($user['email_address']); ?>" disabled required>
                    </div>

                    <div class="form-group">
                        <label for="user_role">Role</label>
                        <input type="text" id="user_role" value="<?php echo htmlspecialchars($user['user_role']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="created_on">Member Since</label>
                        <input type="text" id="created_on" value="<?php echo date('F j, Y', strtotime($user['created_on'])); ?>" disabled>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="editBtn" class="btn btn-edit" onclick="toggleEdit()">Edit Profile</button>
                        <button type="button" id="cancelBtn" class="btn btn-cancel" style="display: none;" onclick="toggleEdit()">Cancel</button>
                        <button type="submit" id="saveBtn" class="btn btn-save" style="display: none;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Toggle edit mode for profile form
        function toggleEdit() {
            const form = document.getElementById('profileForm');
            const nameInput = document.getElementById('full_name');
            const emailInput = document.getElementById('email_address');
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            const isEditing = nameInput.disabled;

            nameInput.disabled = !isEditing;
            emailInput.disabled = !isEditing;
            
            if (isEditing) {
                editBtn.style.display = 'none';
                saveBtn.style.display = 'block';
                cancelBtn.style.display = 'block';
            } else {
                editBtn.style.display = 'block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                
                // Reset form to original values if canceling
                nameInput.value = '<?php echo htmlspecialchars($user['full_name']); ?>';
                emailInput.value = '<?php echo htmlspecialchars($user['email_address']); ?>';
            }
        }
    </script>
</body>
</html>