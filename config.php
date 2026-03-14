<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expense_tracker');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // For development - show error, in production you might want to log it
    // die("Connection failed: " . $conn->connect_error);
    
    // Try to create database if it doesn't exist
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->select_db(DB_NAME);
}

// Set charset to UTF-8
$conn->set_charset("utf8");

// Helper function to sanitize input
function sanitize($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Helper function to format currency
function formatCurrency($amount, $currency = '$') {
    return $currency . number_format($amount, 2);
}

// Helper function to get month name
function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return $months[$month] ?? '';
}

// Helper function to get short month name
function getShortMonthName($month) {
    return date('M', mktime(0, 0, 0, $month, 1));
}

// after $conn = new mysqli(...);
if (!$conn->set_charset('utf8mb4')) {
    // handle error if you like
}

// CSRF Protection Functions
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

