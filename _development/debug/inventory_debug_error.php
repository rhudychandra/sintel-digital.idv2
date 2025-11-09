<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Inventory.php</h2>";

// Test 1: Check config.php
echo "<h3>Test 1: Config.php</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    require_once 'config.php';
    echo "✅ config.php loaded<br>";
} else {
    echo "❌ config.php not found<br>";
}

// Test 2: Check database connection
echo "<h3>Test 2: Database Connection</h3>";
try {
    $conn = getDBConnection();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Check user session
echo "<h3>Test 3: User Session</h3>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['username'] . "<br>";
    $user = getCurrentUser();
    echo "✅ User data loaded<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Cabang ID: " . ($user['cabang_id'] ?? 'NULL') . "<br>";
} else {
    echo "❌ User not logged in<br>";
}

// Test 4: Check queries
echo "<h3>Test 4: Test Queries</h3>";
try {
    $products = $conn->query("SELECT produk_id, nama_produk FROM produk WHERE status = 'active' LIMIT 5");
    echo "✅ Products query OK (" . $products->num_rows . " rows)<br>";
    
    $resellers = $conn->query("SELECT reseller_id, nama_reseller FROM reseller WHERE status = 'active' LIMIT 5");
    echo "✅ Resellers query OK (" . $resellers->num_rows . " rows)<br>";
    
    $cabang = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' LIMIT 5");
    echo "✅ Cabang query OK (" . $cabang->num_rows . " rows)<br>";
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
}

// Test 5: Check inventory.php syntax
echo "<h3>Test 5: Check inventory.php</h3>";
if (file_exists('inventory.php')) {
    echo "✅ inventory.php exists<br>";
    $content = file_get_contents('inventory.php');
    echo "File size: " . strlen($content) . " bytes<br>";
    
    // Check for PHP syntax errors
    $output = [];
    $return_var = 0;
    exec('php -l inventory.php 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ No syntax errors<br>";
    } else {
        echo "❌ Syntax errors found:<br>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    }
} else {
    echo "❌ inventory.php not found<br>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If all tests pass, try accessing: <a href='inventory.php'>inventory.php</a></li>";
echo "<li>If blank page, check browser console (F12) for JavaScript errors</li>";
echo "<li>If PHP error, check error message above</li>";
echo "</ol>";
?>
