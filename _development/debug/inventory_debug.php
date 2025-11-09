<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

echo "<h1>Inventory Debug</h1>";

// Check if tables exist
echo "<h2>1. Checking Tables</h2>";
$tables = ['penjualan', 'reseller', 'produk', 'pelanggan', 'detail_penjualan', 'inventory'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
        
        // Count rows
        $count = $conn->query("SELECT COUNT(*) as cnt FROM $table")->fetch_assoc();
        echo "&nbsp;&nbsp;&nbsp;→ Rows: " . $count['cnt'] . "<br>";
    } else {
        echo "❌ Table '$table' NOT FOUND<br>";
    }
}

// Check reseller table structure
echo "<h2>2. Checking Reseller Table Structure</h2>";
$result = $conn->query("DESCRIBE reseller");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Cannot describe reseller table<br>";
}

// Check penjualan table structure
echo "<h2>3. Checking Penjualan Table Structure</h2>";
$result = $conn->query("DESCRIBE penjualan");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Cannot describe penjualan table<br>";
}

// Test query
echo "<h2>4. Testing Sales Query</h2>";
$start_date = date('Y-m-d');
$end_date = date('Y-m-d');

$sales_query = "
    SELECT 
        r.reseller_id,
        r.nama_reseller,
        COUNT(p.penjualan_id) as total_transaksi,
        COALESCE(SUM(p.total), 0) as total_penjualan
    FROM reseller r
    LEFT JOIN penjualan p ON r.reseller_id = p.reseller_id 
        AND p.tanggal_penjualan BETWEEN ? AND ?
        AND p.status_pembayaran = 'paid'
    WHERE r.status = 'active'
    GROUP BY r.reseller_id, r.nama_reseller
    ORDER BY total_penjualan DESC
";

echo "Query: <pre>" . $sales_query . "</pre>";
echo "Params: start_date=$start_date, end_date=$end_date<br><br>";

$stmt = $conn->prepare($sales_query);
if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        echo "✅ Query executed successfully<br>";
        echo "Rows returned: " . $result->num_rows . "<br><br>";
        
        if ($result->num_rows > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Reseller ID</th><th>Nama Reseller</th><th>Total Transaksi</th><th>Total Penjualan</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['reseller_id'] . "</td>";
                echo "<td>" . $row['nama_reseller'] . "</td>";
                echo "<td>" . $row['total_transaksi'] . "</td>";
                echo "<td>Rp " . number_format($row['total_penjualan'], 0, ',', '.') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠️ No data returned<br>";
        }
    } else {
        echo "❌ Query execution failed: " . $stmt->error . "<br>";
    }
} else {
    echo "❌ Query preparation failed: " . $conn->error . "<br>";
}

// Check if reseller_id column exists in penjualan
echo "<h2>5. Checking if reseller_id exists in penjualan table</h2>";
$result = $conn->query("SHOW COLUMNS FROM penjualan LIKE 'reseller_id'");
if ($result->num_rows > 0) {
    echo "✅ Column 'reseller_id' exists in penjualan table<br>";
} else {
    echo "❌ Column 'reseller_id' NOT FOUND in penjualan table<br>";
    echo "⚠️ This is the problem! The penjualan table doesn't have reseller_id column.<br>";
    echo "💡 Solution: You need to add reseller_id column to penjualan table or modify the query.<br>";
}

// Sample data from penjualan
echo "<h2>6. Sample Data from Penjualan</h2>";
$result = $conn->query("SELECT * FROM penjualan LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data in penjualan table<br>";
}

// Sample data from reseller
echo "<h2>7. Sample Data from Reseller</h2>";
$result = $conn->query("SELECT * FROM reseller LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data in reseller table<br>";
}

$conn->close();
?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h1 { color: #667eea; }
    h2 { color: #764ba2; margin-top: 30px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th { background: #667eea; color: white; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
