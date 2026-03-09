<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: index.html");
    exit();
}

// Include database configuration
require_once 'config.php';

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User';
$user_email = isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '';
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container dashboard-container">
        <a href="logout.php" class="btn-login logout-btn">Logout</a>
        
        <div class="login-box">
            <div class="welcome-message">
                <h2>Welcome, <?php echo $user_name; ?>!</h2>
                <p>Email: <?php echo $user_email; ?></p>
                <p>You are successfully logged in to the Expense Tracker.</p>
            </div>
            
            <div style="margin-top: 30px;">
                <h3>Your Dashboard</h3>
                <p>This is your dashboard where you can manage your expenses.</p>
                <!-- Add your dashboard content here -->
            </div>
        </div>
    </div>
</body>
</html>
