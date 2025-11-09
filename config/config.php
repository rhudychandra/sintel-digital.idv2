<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sinar_telkom_dashboard');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'cabang_id' => $_SESSION['cabang_id'] ?? null
    ];
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Check if user has staff-level access (staff, supervisor, or finance)
function hasStaffAccess($role = null) {
    if ($role === null) {
        $user = getCurrentUser();
        $role = $user['role'] ?? null;
    }
    return in_array($role, ['staff', 'supervisor', 'finance']);
}

// Check if user has admin-level access (admin or higher)
function hasAdminAccess($role = null) {
    if ($role === null) {
        $user = getCurrentUser();
        $role = $user['role'] ?? null;
    }
    return in_array($role, ['admin', 'manager', 'administrator']);
}

// Check if user is administrator
function isAdministrator($role = null) {
    if ($role === null) {
        $user = getCurrentUser();
        $role = $user['role'] ?? null;
    }
    return $role === 'administrator';
}
?>
