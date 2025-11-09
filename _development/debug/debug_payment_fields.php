<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();

echo "<h1>🔍 Debug Payment Fields</h1>";
echo "<hr>";

// Check 1: Database Structure
echo "<h2>1. Database Structure Check</h2>";
$result = $conn->query("DESCRIBE penjualan");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    $highlight = '';
    if ($row['Field'] == 'metode_pembayaran' || $row['Field'] == 'status_pembayaran') {
        $highlight = 'style="background: yellow;"';
    }
    echo "<tr $highlight>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check 2: Recent Data
echo "<h2>2. Recent Penjualan Data (Last 5)</h2>";
$result = $conn->query("SELECT penjualan_id, no_invoice, tanggal_penjualan, metode_pembayaran, status_pembayaran, total FROM penjualan ORDER BY penjualan_id DESC LIMIT 5");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Invoice</th><th>Tanggal</th><th>Metode</th><th>Status</th><th>Total</th></tr>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['penjualan_id']}</td>";
        echo "<td>{$row['no_invoice']}</td>";
        echo "<td>{$row['tanggal_penjualan']}</td>";
        echo "<td><strong style='color: blue;'>{$row['metode_pembayaran']}</strong></td>";
        echo "<td><strong style='color: green;'>{$row['status_pembayaran']}</strong></td>";
        echo "<td>Rp " . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>Tidak ada data</td></tr>";
}
echo "</table>";

// Check 3: Unique Values
echo "<h2>3. Unique Values in Database</h2>";
echo "<h3>Metode Pembayaran:</h3>";
$result = $conn->query("SELECT DISTINCT metode_pembayaran, COUNT(*) as count FROM penjualan GROUP BY metode_pembayaran");
echo "<ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li><strong>{$row['metode_pembayaran']}</strong> ({$row['count']} records)</li>";
}
echo "</ul>";

echo "<h3>Status Pembayaran:</h3>";
$result = $conn->query("SELECT DISTINCT status_pembayaran, COUNT(*) as count FROM penjualan GROUP BY status_pembayaran");
echo "<ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li><strong>{$row['status_pembayaran']}</strong> ({$row['count']} records)</li>";
}
echo "</ul>";

// Check 4: Test Query (Same as in inventory.php)
echo "<h2>4. Test Query (Same as Laporan)</h2>";
$penjualan_start_date = date('Y-m-d', strtotime('-30 days'));
$penjualan_end_date = date('Y-m-d');

$penjualan_query = "
    SELECT 
        p.penjualan_id,
        p.no_invoice,
        p.tanggal_penjualan,
        r.nama_reseller,
        c.nama_cabang,
        dp.nama_produk,
        dp.jumlah,
        dp.harga_satuan,
        dp.subtotal,
        p.total as total_invoice,
        p.metode_pembayaran,
        p.status_pembayaran
    FROM penjualan p
    JOIN reseller r ON p.reseller_id = r.reseller_id
    LEFT JOIN cabang c ON r.cabang_id = c.cabang_id
    JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
    WHERE p.tanggal_penjualan BETWEEN ? AND ?
    ORDER BY p.tanggal_penjualan DESC, p.penjualan_id DESC
    LIMIT 5
";

$stmt = $conn->prepare($penjualan_query);
$stmt->bind_param("ss", $penjualan_start_date, $penjualan_end_date);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Invoice</th><th>Reseller</th><th>Produk</th><th>Metode</th><th>Status</th><th>Total</th></tr>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['no_invoice']}</td>";
        echo "<td>{$row['nama_reseller']}</td>";
        echo "<td>{$row['nama_produk']}</td>";
        echo "<td><strong style='color: blue;'>{$row['metode_pembayaran']}</strong></td>";
        echo "<td><strong style='color: green;'>{$row['status_pembayaran']}</strong></td>";
        echo "<td>Rp " . number_format($row['subtotal'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>Tidak ada data dalam 30 hari terakhir</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>✅ Kesimpulan:</h2>";
echo "<ul>";
echo "<li>Jika kolom metode_pembayaran dan status_pembayaran bertipe <strong>VARCHAR(50)</strong> → Database OK ✅</li>";
echo "<li>Jika data di tabel penjualan menampilkan metode & status → Data tersimpan OK ✅</li>";
echo "<li>Jika test query menampilkan metode & status → Query OK ✅</li>";
echo "<li>Jika semua OK tapi tidak muncul di inventory.php → Ada masalah di file inventory.php</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='inventory.php?page=input_penjualan' style='padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>← Kembali ke Inventory</a></p>";

$conn->close();
