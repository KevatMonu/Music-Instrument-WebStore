<?php
session_start();
$message = '';
$messageType = '';

// Form handling
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_content = trim($_POST['message'] ?? '');
    
    // Validate form data
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message_content)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        // In a real application, you would send an email or save to database here
        // For now, we'll just simulate a successful submission
        $message = "Thank you for your message! We'll get back to you shortly.";
        $messageType = "success";
        
        // Reset form fields
        $name = $email = $subject = $message_content = '';
    } else {
        $message = "Please correct the following errors:<br>" . implode("<br>", $errors);
        $messageType = "error";
    }
}

include 'pages/header.php';
?>

<div class="contact-container">
    <div class="contact-hero">
        <h1>Contact Us</h1>
        <p class="contact-tagline">We'd love to hear from you</p>
    </div>
    
    <div class="contact-content">
        <div class="contact-info">
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>Visit Our Store</h3>
                <p>123 Music Avenue</p>
                <p>Harmony City, HC 12345</p>
                <p>United States</p>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h3>Call Us</h3>
                <p>Sales: (555) 123-4567</p>
                <p>Support: (555) 987-6543</p>
                <p>Fax: (555) 246-8910</p>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Email Us</h3>
                <p>info@musicstore.com</p>
                <p>sales@musicstore.com</p>
                <p>support@musicstore.com</p>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Opening Hours</h3>
                <p>Monday - Friday: 9am - 8pm</p>
                <p>Saturday: 10am - 6pm</p>
                <p>Sunday: 12pm - 5pm</p>
            </div>
            
            <div class="contact-social">
                <h3>Connect With Us</h3>
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        
        <div class="contact-form-container">
            <h2>Send Us a Message</h2>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="contact-form">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Your Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" rows="5" required><?php echo htmlspecialchars($message_content ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="contact-button">Send Message</button>
            </form>
        </div>
    </div>
    
    <div class="contact-map">
        <h2>Find Us</h2>
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3782.0030997589285!2d73.7507627745495!3d18.578303871515914!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bc2ba33f8d3d9e7%3A0xa5f95e0d5a1e2d0b!2sSus%2C%20Pune%2C%20Maharashtra%20411211%2C%20India!5e0!3m2!1sen!2sin!4v1709801000000!5m2!1sen!2sin" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

    </div>
    
    <div class="contact-faq">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-container">
            <div class="faq-item">
                <h3>Do you offer instrument rentals?</h3>
                <p>Yes, we offer rentals for a wide range of instruments. Please call our store or visit us in person for more information on availability and pricing.</p>
            </div>
            
            <div class="faq-item">
                <h3>What payment methods do you accept?</h3>
                <p>We accept all major credit cards, PayPal, Apple Pay, and cash payments in store.</p>
            </div>
            
            <div class="faq-item">
                <h3>Do you offer instrument repairs?</h3>
                <p>Yes, our skilled technicians can repair most instruments. Bring your instrument to our store for an assessment and quote.</p>
            </div>
            
            <div class="faq-item">
                <h3>Do you offer music lessons?</h3>
                <p>Yes, we have qualified instructors for piano, guitar, drums, and voice. Contact us for scheduling and pricing details.</p>
            </div>
        </div>
    </div>
</div>


<?php include 'pages/footer.php'; ?>