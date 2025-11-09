<?php
// Debug file untuk check masalah admin panel
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Admin Panel</h1>";

// Check 1: Config file
echo "<h2>1. Check Config File</h2>";
if (file_exists('../config.php')) {
    echo "✅ config.php exists<br>";
    require_once '../config.php';
    echo "✅ config.php loaded<br>";
} else {
    echo "❌ config.php NOT FOUND<br>";
}

// Check 2: Database Connection
echo "<h2>2. Check Database Connection</h2>";
try {
    $conn = getDBConnection();
    echo "✅ Database connected<br>";
    echo "Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Check 3: Session
echo "<h2>3. Check Session</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Session exists<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
} else {
    echo "❌ No session found<br>";
}

// Check 4: User Data
echo "<h2>4. Check User Data</h2>";
try {
    $user = getCurrentUser();
    echo "✅ User data loaded<br>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Failed to get user: " . $e->getMessage() . "<br>";
}

// Check 5: Tables
echo "<h2>5. Check Tables</h2>";
$tables = ['users', 'produk', 'cabang', 'reseller', 'penjualan', 'inventory'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM $table")->fetch_assoc()['cnt'];
        echo "✅ Table '$table' exists ($count rows)<br>";
    } else {
        echo "❌ Table '$table' NOT FOUND<br>";
    }
}

// Check 6: Views
echo "<h2>6. Check Views</h2>";
$views = ['view_admin_dashboard', 'view_sales_per_cabang', 'view_stock_per_cabang', 'view_reseller_performance'];
foreach ($views as $view) {
    $result = $conn->query("SHOW TABLES LIKE '$view'");
    if ($result && $result->num_rows > 0) {
        echo "✅ View '$view' exists<br>";
        try {
            $data = $conn->query("SELECT * FROM $view LIMIT 1");
            if ($data) {
                echo "   Data: ";
                print_r($data->fetch_assoc());
                echo "<br>";
            }
        } catch (Exception $e) {
            echo "   ❌ Error querying view: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ View '$view' NOT FOUND<br>";
    }
}

// Check 7: Files
echo "<h2>7. Check Files</h2>";
$files = [
    'index.php',
    'produk.php',
    'cabang.php',
    'users.php',
    'reseller.php',
    'penjualan.php',
    'stock.php',
    'grafik.php',
    'admin-styles.css'
];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT FOUND<br>";
    }
}

// Check 8: CSS File
echo "<h2>8. Check CSS File</h2>";
if (file_exists('admin-styles.css')) {
    echo "✅ admin-styles.css exists<br>";
    echo "Size: " . filesize('admin-styles.css') . " bytes<br>";
} else {
    echo "❌ admin-styles.css NOT FOUND<br>";
}

if (file_exists('../styles.css')) {
    echo "✅ ../styles.css exists<br>";
    echo "Size: " . filesize('../styles.css') . " bytes<br>";
} else {
    echo "❌ ../styles.css NOT FOUND<br>";
}

// Check 9: Test Query
echo "<h2>9. Test Dashboard Query</h2>";
try {
    $result = $conn->query("SELECT * FROM view_admin_dashboard");
    if ($result && $result->num_rows > 0) {
        $stats = $result->fetch_assoc();
        echo "✅ Dashboard query successful<br>";
        echo "<pre>";
        print_r($stats);
        echo "</pre>";
    } else {
        echo "❌ Dashboard query returned no results<br>";
    }
} catch (Exception $e) {
    echo "❌ Dashboard query failed: " . $e->getMessage() . "<br>";
}

$conn->close();

echo "<h2>10. Recommendations</h2>";
echo "<ul>";
echo "<li>If view_admin_dashboard NOT FOUND: Import database_update_admin.sql</li>";
echo "<li>If CSS files NOT FOUND: Check file paths</li>";
echo "<li>If session NOT FOUND: Login again</li>";
echo "<li>If database connection failed: Check config.php</li>";
echo "</ul>";

echo "<hr>";
echo "<a href='index.php'>← Back to Admin Panel</a>";
?>
