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
$stmt = $conn->prepare("SELECT full_name, email_address FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$full_name = $user['full_name'];
$email = $user['email_address'];

// Handle ticket submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ticket'])) {
    $subject = $_POST['subject'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    
    // Prepare and execute the query
    // Assuming you have a support_tickets table
    $stmt = $conn->prepare("INSERT INTO support_tickets (user_ref, ticket_subject, ticket_category, ticket_description, ticket_priority, ticket_status, created_on) VALUES (?, ?, ?, ?, ?, 'Open', NOW())");
    $stmt->bind_param("issss", $user_id, $subject, $category, $description, $priority);
    
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Ticket submitted successfully! Our team will respond shortly.</div>";
    } else {
        $message = "<div class='error-message'>Error submitting ticket. Please try again.</div>";
    }
}

// Get user's previous tickets
$stmt = $conn->prepare("SELECT ticket_id, ticket_subject, ticket_category, ticket_priority, ticket_status, created_on FROM support_tickets WHERE user_ref = ? ORDER BY created_on DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets = $stmt->get_result();

// Get FAQ data
$faq_query = "SELECT faq_id, faq_question, faq_answer FROM faqs ORDER BY faq_id ASC LIMIT 8";
$faqs = $conn->query($faq_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support & Help | Music Store</title>
    <link rel="stylesheet" href="https://unpkg.com/lucide-static@latest/font/lucide.css">
    <link rel="stylesheet" href="css/user_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles for support page */
        .support-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        .support-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        
        .form-submit {
            background-color: #ff7f50;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .form-submit:hover {
            background-color: #e56b3e;
        }
        
        .success-message {
            background-color: #e6f7ee;
            color: #0f5132;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #842029;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .faq-item {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .faq-question {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-answer {
            color: #666;
            display: none;
            padding: 10px 0;
        }
        
        .faq-question.active + .faq-answer {
            display: block;
        }
        
        .icon-toggle {
            transition: transform 0.3s ease;
        }
        
        .faq-question.active .icon-toggle {
            transform: rotate(180deg);
        }
        
        .ticket-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .ticket-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .ticket-subject {
            font-weight: 600;
            color: #333;
        }
        
        .ticket-date {
            color: #777;
            font-size: 0.85rem;
        }
        
        .ticket-details {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .ticket-category, 
        .ticket-priority {
            font-size: 0.85rem;
            color: #666;
        }
        
        .priority-high {
            color: #dc3545;
        }
        
        .priority-medium {
            color: #fd7e14;
        }
        
        .priority-low {
            color: #20c997;
        }

        .ticket-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-open {
            background-color: #e6f2ff;
            color: #0055cc;
        }
        
        .status-in-progress {
            background-color: #fff7e6;
            color: #997500;
        }
        
        .status-closed {
            background-color: #e6f7ee;
            color: #0f5132;
        }
        
        .view-ticket-btn {
            display: inline-block;
            padding: 5px 10px;
            background-color: #f8f9fa;
            color: #495057;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .view-ticket-btn:hover {
            background-color: #e9ecef;
        }
        
        .contact-info {
            margin-top: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        
        .contact-method {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .contact-method i {
            color: #ff7f50;
        }
        
        .contact-method a {
            color: #333;
            text-decoration: none;
        }
        
        .contact-method a:hover {
            color: #ff7f50;
        }
        
        @media (max-width: 992px) {
            .support-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                    <li><a href="user_dashboard.php" class="nav-link"><i class="lucide-home"></i> <span>Dashboard</span></a></li>
                    <li><a href="products.php" class="nav-link"><i class="lucide-package"></i> <span>Browse Products</span></a></li>
                    <li><a href="cart.php" class="nav-link"><i class="lucide-shopping-cart"></i> <span>My Cart</span></a></li>
                    <li><a href="user_order_detail.php" class="nav-link"><i class="lucide-clipboard-list"></i> <span>My Orders</span></a></li>
                    <li><a href="profile.php" class="nav-link"><i class="lucide-user"></i> <span>My Profile</span></a></li>
                    <li><a href="support.php" class="nav-link active"><i class="lucide-help-circle"></i> <span>Help & Support</span></a></li>
                </ul>
            </nav>
            <div class="logout-section">
                <a href="logout.php" class="logout-btn"><i class="lucide-log-out"></i> <span>Logout</span></a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="welcome-banner">
                <h1>Help & Support</h1>
                <p>Have questions or need assistance? We're here to help you with any issues you might encounter.</p>
            </div>
            
            <?php echo $message; ?>
            
            <div class="support-container">
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2 class="section-title">Submit a Support Ticket</h2>
                    </div>
                    
                    <form class="support-form" method="POST" action="">
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required placeholder="Brief description of your issue">
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="Order Issue">Order Issue</option>
                                <option value="Payment Problem">Payment Problem</option>
                                <option value="Product Question">Product Question</option>
                                <option value="Return/Refund">Return/Refund</option>
                                <option value="Website Issue">Website Issue</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" required placeholder="Please provide details about your issue..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_ticket" class="form-submit">Submit Ticket</button>
                    </form>
                </div>
                
                <div>
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2 class="section-title">Frequently Asked Questions</h2>
                        </div>
                        
                        <?php if ($faqs->num_rows > 0): ?>
                            <div class="faq-list">
                                <?php while ($faq = $faqs->fetch_assoc()): ?>
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <?php echo htmlspecialchars($faq['faq_question']); ?>
                                        <i class="lucide-chevron-down icon-toggle"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <?php echo htmlspecialchars($faq['faq_answer']); ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No FAQs available at the moment.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="contact-info">
                            <h3>Contact Us Directly</h3>
                            <div class="contact-method">
                                <i class="lucide-mail"></i>
                                <a href="mailto:support@musicstore.com">support@musicstore.com</a>
                            </div>
                            <div class="contact-method">
                                <i class="lucide-phone"></i>
                                <a href="tel:+18001234567">+1 (800) 123-4567</a>
                            </div>
                            <div class="contact-method">
                                <i class="lucide-clock"></i>
                                <span>Mon-Fri: 9am to 5pm EST</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Previous Tickets Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Your Recent Tickets</h2>
                </div>
                
                <?php if ($tickets->num_rows > 0): ?>
                    <div class="tickets-list">
                        <?php while ($ticket = $tickets->fetch_assoc()): ?>
                            <div class="ticket-card">
                                <div class="ticket-header">
                                    <div class="ticket-subject"><?php echo htmlspecialchars($ticket['ticket_subject']); ?></div>
                                    <div class="ticket-date"><?php echo date("M d, Y", strtotime($ticket['created_on'])); ?></div>
                                </div>
                                <div class="ticket-details">
                                    <div class="ticket-category">
                                        <i class="lucide-tag"></i> <?php echo htmlspecialchars($ticket['ticket_category']); ?>
                                    </div>
                                    <div class="ticket-priority priority-<?php echo strtolower($ticket['ticket_priority']); ?>">
                                        <i class="lucide-alert-circle"></i> <?php echo htmlspecialchars($ticket['ticket_priority']); ?> Priority
                                    </div>
                                </div>
                                <div class="ticket-footer">
                                    <span class="ticket-status status-<?php echo strtolower(str_replace(' ', '-', $ticket['ticket_status'])); ?>">
                                        <?php echo htmlspecialchars($ticket['ticket_status']); ?>
                                    </span>
                                    <a href="ticket_details.php?id=<?php echo $ticket['ticket_id']; ?>" class="view-ticket-btn">View Details</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You haven't submitted any support tickets yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // FAQ accordion functionality
        const faqQuestions = document.querySelectorAll('.faq-question');
        
        faqQuestions.forEach(question => {
            question.addEventListener('click', function() {
                this.classList.toggle('active');
            });
        });
    });
    </script>
</body>
</html>