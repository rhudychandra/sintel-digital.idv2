<?php
// Test database connection
echo "=== DATABASE CONNECTION TEST ===<br><br>";

// Database credentials
$db_host = 'localhost';
$db_user = 'u879436580_sintel_digital';
$db_pass = 'Musirawas2024';
$db_name = 'u879436580_sintel_db1';

echo "Attempting connection to: $db_host<br>";
echo "Database: $db_name<br>";
echo "User: $db_user<br><br>";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo "❌ CONNECTION FAILED<br>";
    echo "Error: " . $conn->connect_error . "<br>";
    die();
} else {
    echo "✅ CONNECTION SUCCESS<br><br>";
    
    // Test if tables exist
    echo "=== CHECKING TABLES ===<br>";
    $tables = ['users', 'produk', 'inventory', 'cabang', 'reseller', 'penjualan'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' NOT FOUND<br>";
        }
    }
    
    // Check inventory columns
    echo "<br>=== CHECKING INVENTORY COLUMNS ===<br>";
    $cols_to_check = ['inventory_id', 'status_approval', 'tipe_transaksi'];
    
    $result = $conn->query("DESCRIBE inventory");
    $existing_cols = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing_cols[] = $row['Field'];
        }
    }
    
    foreach ($cols_to_check as $col) {
        if (in_array($col, $existing_cols)) {
            echo "✅ Column '$col' exists<br>";
        } else {
            echo "❌ Column '$col' NOT FOUND<br>";
        }
    }
    
    $conn->close();
}
?>
