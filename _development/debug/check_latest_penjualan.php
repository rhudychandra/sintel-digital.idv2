<?php
<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

echo "<h2>🔍 Check Latest Penjualan Data</h2>";
echo "<hr>";

// User info
echo "<h3>1. User Info:</h3>";
echo "Username: " . $user['username'] . "<br>";
echo "Role: " . $user['role'] . "<br>";
echo "Cabang ID: " . ($user['cabang_id'] ?? 'NULL') . "<br>";
echo "<hr>";

// Check latest 10 penjualan
echo "<h3>2. Latest 10 Penjualan (All Data):</h3>";
$query = "SELECT 
    p.penjualan_id,
    p.no_invoice,
    p.tanggal_penjualan,
    p.cabang_id,
    c.nama_cabang,
    r.nama_reseller,
    p.total,
    p.status_pembayaran,
    p.created_at
FROM penjualan p
LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
LEFT JOIN reseller r ON p.reseller_id = r.reseller_id
ORDER BY p.penjualan_id DESC
LIMIT 10";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>ID</th><th>Invoice</th><th>Tanggal</th><th>Cabang ID</th><th>Cabang</th><th>Reseller</th><th>Total</th><th>Status</th><th>Created At</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['cabang_id'] == $user['cabang_id']) ? "background: #d4edda;" : "";
        echo "<tr style='$highlight'>";
        echo "<td>" . $row['penjualan_id'] . "</td>";
        echo "<td><strong>" . $row['no_invoice'] . "</strong></td>";
        echo "<td>" . date('d/m/Y', strtotime($row['tanggal_penjualan'])) . "</td>";
        echo "<td>" . ($row['cabang_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['nama_cabang'] ?? '-') . "</td>";
        echo "<td>" . ($row['nama_reseller'] ?? '-') . "</td>";
        echo "<td>Rp " . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "<td>" . $row['status_pembayaran'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'><strong>✅ Hijau = Data yang sesuai dengan cabang Anda</strong></p>";
} else {
    echo "<p style='color: red;'>❌ Tidak ada data penjualan</p>";
}

echo "<hr>";

// Check penjualan for current month
echo "<h3>3. Penjualan Bulan Ini (Filter Default Laporan):</h3>";
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');
echo "<p>Periode: <strong>$start_date</strong> sampai <strong>$end_date</strong></p>";

$query = "SELECT 
    p.penjualan_id,
    p.no_invoice,
    p.tanggal_penjualan,
    p.cabang_id,
    c.nama_cabang,
    p.total
FROM penjualan p
LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
WHERE p.tanggal_penjualan BETWEEN ? AND ?
ORDER BY p.penjualan_id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Ditemukan <strong>" . $result->num_rows . "</strong> transaksi</p>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #3498db; color: white;'>";
    echo "<th>ID</th><th>Invoice</th><th>Tanggal</th><th>Cabang ID</th><th>Cabang</th><th>Total</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['cabang_id'] == $user['cabang_id']) ? "background: #d4edda;" : "";
        echo "<tr style='$highlight'>";
        echo "<td>" . $row['penjualan_id'] . "</td>";
        echo "<td>" . $row['no_invoice'] . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($row['tanggal_penjualan'])) . "</td>";
        echo "<td>" . ($row['cabang_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['nama_cabang'] ?? '-') . "</td>";
        echo "<td>Rp " . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Tidak ada transaksi untuk periode ini</p>";
}

echo "<hr>";

// Check with user's cabang filter
if ($user['cabang_id'] && !in_array($user['role'], ['administrator', 'manager'])) {
    echo "<h3>4. Penjualan Bulan Ini untuk Cabang Anda (ID: " . $user['cabang_id'] . "):</h3>";
    
    $query = "SELECT 
        p.penjualan_id,
        p.no_invoice,
        p.tanggal_penjualan,
        p.cabang_id,
        c.nama_cabang,
        p.total
    FROM penjualan p
    LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
    WHERE p.tanggal_penjualan BETWEEN ? AND ?
    AND p.cabang_id = ?
    ORDER BY p.penjualan_id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $start_date, $end_date, $user['cabang_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Ditemukan <strong>" . $result->num_rows . "</strong> transaksi untuk cabang Anda</p>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #27ae60; color: white;'>";
        echo "<th>ID</th><th>Invoice</th><th>Tanggal</th><th>Cabang</th><th>Total</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['penjualan_id'] . "</td>";
            echo "<td>" . $row['no_invoice'] . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['tanggal_penjualan'])) . "</td>";
            echo "<td>" . ($row['nama_cabang'] ?? '-') . "</td>";
            echo "<td>Rp " . number_format($row['total'], 0, ',', '.') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Ini adalah data yang seharusnya muncul di laporan Anda.</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Tidak ada transaksi untuk cabang Anda di periode ini</p>";
        echo "<p style='color: orange;'><strong>⚠️ MASALAH DITEMUKAN:</strong> Data penjualan baru tidak memiliki cabang_id yang sesuai dengan cabang Anda!</p>";
    }
}

echo "<hr>";

// Solution
echo "<h3>💡 Solusi:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
echo "<p><strong>Jika data baru tidak muncul, kemungkinan penyebabnya:</strong></p>";
echo "<ol>";
echo "<li><strong>Data baru tidak punya cabang_id yang benar</strong><br>";
echo "Solusi: Pastikan saat input penjualan baru, cabang_id ter-set dengan benar</li>";
echo "<li><strong>Tanggal penjualan di luar range filter</strong><br>";
echo "Solusi: Ubah filter tanggal di laporan untuk mencakup tanggal data baru</li>";
echo "<li><strong>Browser cache</strong><br>";
echo "Solusi: Hard refresh (Ctrl + F5) atau clear cache</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><a href='inventory_laporan.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>← Kembali ke Laporan</a></p>";

$conn->close();
?>
