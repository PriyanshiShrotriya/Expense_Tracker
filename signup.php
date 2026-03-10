<?php
// Start session
session_start();

// Include database configuration
require_once 'config.php';

// Initialize variables
$error = '';
$success = false;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email already registered';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                
                if ($insert_stmt) {
                    $insert_stmt->bind_param("sss", $name, $email, $hashed_password);
                    
                    if ($insert_stmt->execute()) {
                        $user_id = $insert_stmt->insert_id;
                        
                        // Insert default categories for the user
                        $default_categories = [
                            ['Food & Dining', '🍕', '#ff6b6b'],
                            ['Transportation', '🚗', '#4ecdc4'],
                            ['Shopping', '🛒', '#45b7d1'],
                            ['Entertainment', '🎬', '#96ceb4'],
                            ['Bills & Utilities', '💡', '#ffeaa7'],
                            ['Healthcare', '🏥', '#dda0dd'],
                            ['Education', '📚', '#98d8c8'],
                            ['Other', '📦', '#a29bfe']
                        ];
                        
                        $cat_query = "INSERT INTO categories (user_id, name, icon, color) VALUES (?, ?, ?, ?)";
                        $cat_stmt = $conn->prepare($cat_query);
                        
                        foreach ($default_categories as $cat) {
                            $cat_stmt->bind_param("isss", $user_id, $cat[0], $cat[1], $cat[2]);
                            $cat_stmt->execute();
                        }
                        $cat_stmt->close();
                        
                        $success = true;
                        $message = 'Registration successful! Please login.';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                    
                    $insert_stmt->close();
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
            
            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
    
    // Close connection
    $conn->close();
    
    // Redirect with message
    if ($success) {
        header("Location: index.html?message=" . urlencode($message));
        exit();
    } else {
        header("Location: index.html?error=" . urlencode($error) . "&mode=signup");
        exit();
    }
} else {
    // Not a POST request - redirect to signup page
    header("Location: index.html?mode=signup");
    exit();
}
?>

