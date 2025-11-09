<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

echo "<h2>Debug Laporan Penjualan</h2>";
echo "<hr>";

// Test 1: Check user info
echo "<h3>1. User Info:</h3>";
echo "<pre>";
print_r($user);
echo "</pre>";

// Test 2: Check if tables exist
echo "<h3>2. Check Tables:</h3>";
$tables = ['penjualan', 'reseller', 'cabang', 'detail_penjualan', 'users'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    echo "$table: " . ($result && $result->num_rows > 0 ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
}

// Test 3: Check penjualan columns
echo "<h3>3. Penjualan Table Columns:</h3>";
$result = $conn->query("SHOW COLUMNS FROM penjualan");
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
}

// Test 4: Check if cabang_id exists in penjualan
echo "<h3>4. Check cabang_id in penjualan:</h3>";
$check_column = $conn->query("SHOW COLUMNS FROM penjualan LIKE 'cabang_id'");
$has_cabang = ($check_column && $check_column->num_rows > 0);
echo "Has cabang_id: " . ($has_cabang ? "✅ YES" : "❌ NO") . "<br>";

// Test 5: Count penjualan records
echo "<h3>5. Penjualan Records:</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM penjualan");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total records: " . $row['total'] . "<br>";
}

// Test 6: Sample penjualan data
echo "<h3>6. Sample Penjualan Data (First 5):</h3>";
$query = "SELECT p.*, r.nama_reseller 
          FROM penjualan p 
          LEFT JOIN reseller r ON p.reseller_id = r.reseller_id 
          LIMIT 5";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Invoice</th><th>Tanggal</th><th>Reseller</th><th>Total</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['penjualan_id'] . "</td>";
        echo "<td>" . $row['no_invoice'] . "</td>";
        echo "<td>" . $row['tanggal_penjualan'] . "</td>";
        echo "<td>" . ($row['nama_reseller'] ?? 'N/A') . "</td>";
        echo "<td>Rp " . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "<td>" . $row['status_pembayaran'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No data found or query error: " . $conn->error;
}

// Test 7: Test the actual query used in laporan
echo "<h3>7. Test Laporan Query:</h3>";
$filter_start_date = date('Y-m-01');
$filter_end_date = date('Y-m-d');

$where_conditions = ["p.tanggal_penjualan BETWEEN ? AND ?"];
$params = [$filter_start_date, $filter_end_date];
$types = "ss";
$where_clause = implode(" AND ", $where_conditions);

if ($has_cabang) {
    $sales_query = "SELECT 
        p.penjualan_id,
        p.no_invoice,
        p.tanggal_penjualan,
        r.nama_reseller,
        COALESCE(c.nama_cabang, '-') as nama_cabang,
        COUNT(dp.detail_id) as total_items,
        p.subtotal,
        p.total,
        p.status_pembayaran,
        p.metode_pembayaran
    FROM penjualan p
    LEFT JOIN reseller r ON p.reseller_id = r.reseller_id
    LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
    LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
    WHERE {$where_clause}
    GROUP BY p.penjualan_id 
    ORDER BY p.tanggal_penjualan DESC 
    LIMIT 5";
} else {
    $sales_query = "SELECT 
        p.penjualan_id,
        p.no_invoice,
        p.tanggal_penjualan,
        r.nama_reseller,
        COALESCE(c.nama_cabang, '-') as nama_cabang,
        COUNT(dp.detail_id) as total_items,
        p.subtotal,
        p.total,
        p.status_pembayaran,
        p.metode_pembayaran
    FROM penjualan p
    LEFT JOIN reseller r ON p.reseller_id = r.reseller_id
    LEFT JOIN users u ON p.user_id = u.user_id
    LEFT JOIN cabang c ON u.cabang_id = c.cabang_id
    LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
    WHERE {$where_clause}
    GROUP BY p.penjualan_id 
    ORDER BY p.tanggal_penjualan DESC 
    LIMIT 5";
}

echo "<strong>Query:</strong><br>";
echo "<pre>" . str_replace($where_clause, "p.tanggal_penjualan BETWEEN '$filter_start_date' AND '$filter_end_date'", $sales_query) . "</pre>";

$stmt = $conn->prepare($sales_query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<strong>Result:</strong><br>";
    echo "Rows found: " . $result->num_rows . "<br>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Invoice</th><th>Tanggal</th><th>Reseller</th><th>Cabang</th><th>Items</th><th>Total</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['no_invoice'] . "</td>";
            echo "<td>" . $row['tanggal_penjualan'] . "</td>";
            echo "<td>" . $row['nama_reseller'] . "</td>";
            echo "<td>" . $row['nama_cabang'] . "</td>";
            echo "<td>" . $row['total_items'] . "</td>";
            echo "<td>Rp " . number_format($row['total'], 0, ',', '.') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "❌ Query preparation failed: " . $conn->error;
}

$conn->close();
?>
