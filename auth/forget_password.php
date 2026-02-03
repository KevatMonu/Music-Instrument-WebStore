<?php
// Database connection
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "musicstore_database"; // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    
    // Check if email exists in the database
    $sql = "SELECT * FROM users WHERE email_address = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update user password in database
        $update_sql = "UPDATE users SET user_password = ? WHERE email_address = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $hashed_password, $email);
        
        if ($update_stmt->execute()) {
            $message = "<div class='success'>Your password has been successfully reset.</div>";
        } else {
            $message = "<div class='error'>Error updating password: " . $conn->error . "</div>";
        }
        
        $update_stmt->close();
    } else {
        $message = "<div class='error'>Email address not found in our records.</div>";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="css/forget_password.css">

</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Reset Password</h2>
            
            <?php if (!empty($message)) echo $message; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Reset Password</button>
                </div>
            </form>
            
            <div class="links">
                <a href="sign-in.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>