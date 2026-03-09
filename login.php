<?php
// Start session
session_start();

// Include database configuration
require_once 'config.php';

// Initialize error variable
$error = '';
$success = false;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Query database for user
        $query = "SELECT id, email, password, name FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            // Bind parameter
            $stmt->bind_param("s", $email);
            
            // Execute query
            $stmt->execute();
            
            // Get result
            $result = $stmt->get_result();
            
            // Check if user exists
            if ($result->num_rows == 1) {
                // Fetch user data
                $user = $result->fetch_assoc();
                
                // Verify password using password_verify (checks hashed password)
                if (password_verify($password, $user['password'])) {
                    // Password is correct - Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['logged_in'] = true;
                    
                    // Log login activity (optional)
                    $log_query = "INSERT INTO login_history (user_id, login_time) VALUES (?, NOW())";
                    $log_stmt = $conn->prepare($log_query);
                    if ($log_stmt) {
                        $log_stmt->bind_param("i", $user['id']);
                        $log_stmt->execute();
                        $log_stmt->close();
                    }
                    
                    $success = true;
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // Password is incorrect
                    $error = 'Invalid email or password';
                }
            } else {
                // User not found
                $error = 'Invalid email or password';
            }
            
            // Close statement
            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
    
    // Close connection
    $conn->close();
    
    // If there's an error, redirect back to login page with error message
    if (!$success && !empty($error)) {
        header("Location: index.html?error=" . urlencode($error));
        exit();
    }
} else {
    // Not a POST request - redirect to login page
    header("Location: index.html");
    exit();
}
?>
