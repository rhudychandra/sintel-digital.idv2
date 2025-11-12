<?php
// Sample Database Configuration (copy to config.php and fill your credentials)
// DO NOT commit real credentials. config.php is ignored by .gitignore

define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'sinar_telkom_dashboard');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
', '/', realpath(__DIR__ . '/..')), '/');
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
    $appRoot = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
    $basePath = '';
    if ($docRoot && strpos($appRoot, $docRoot) === 0) {
        $basePath = substr($appRoot, strlen($docRoot));
    }
    $basePath = '/' . ltrim($basePath, '/');
    if ($basePath === '/') {
        $basePath = '';
    }
    define('BASE_PATH', $basePath);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email'    => $_SESSION['email'],
        'role'     => $_SESSION['role'],
        'cabang_id'=> $_SESSION['cabang_id'] ?? null,
    ];
}
?>
