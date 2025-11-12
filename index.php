<?php
require_once __DIR__ . '/config/config.php';
// Entry point - Redirect to login page using BASE_PATH
header('Location: ' . BASE_PATH . '/login');
exit();
?>
