<?php
include 'config/config.php';

try {
    $conn = getDBConnection();
    echo "Database connection successful!";
    $conn->close();
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>
