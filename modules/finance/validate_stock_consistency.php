<?php
/**
 * Stock Consistency Validation Script
 * 
 * Tujuan: Validasi konsistensi antara:
 * 1. produk.stok (global stock)
 * 2. SUM(inventory per cabang) 
 * 3. inventory.stok_sesudah terakhir per produk
 * 
 * Run manual untuk check data integrity
 */

require_once '../../config/config.php';

// Cek koneksi database
$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

echo "<h2>📊 Stock Consistency Validation Report</h2>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .warning { background-color: #fff3cd; }
    .error { background-color: #f8d7da; }
    .success { background-color: #d4edda; }
</style>";

// ====================================
// 1. CHECK INVENTORY WITH NULL CABANG_ID
// ====================================
echo "<h3>1️⃣ Inventory Records with NULL cabang_id</h3>";
$query1 = "SELECT COUNT(*) as total, 
           SUM(CASE WHEN tipe_transaksi='masuk' THEN jumlah ELSE -jumlah END) as qty_affected
           FROM inventory 
           WHERE cabang_id IS NULL";
$result1 = $conn->query($query1);
$row1 = $result1->fetch_assoc();

if ($row1['total'] > 0) {
    echo "<p class='warning'>⚠️ Found {$row1['total']} inventory records with NULL cabang_id (Qty: {$row1['qty_affected']})</p>";
    echo "<p><strong>Action Required:</strong> Update these records with proper cabang_id or they won't appear in Stock TAP per Cabang!</p>";
} else {
    echo "<p class='success'>✅ All inventory records have valid cabang_id</p>";
}

// ====================================
// 2. COMPARE PRODUK.STOK vs INVENTORY SUM
// ====================================
echo "<h3>2️⃣ Global Stock Comparison</h3>";
echo "<table>";
echo "<tr><th>Produk</th><th>Kategori</th><th>produk.stok</th><th>SUM(inventory)</th><th>Difference</th><th>Status</th></tr>";

$query2 = "
    SELECT 
        p.produk_id,
        p.nama_produk,
        p.kategori,
        p.stok as produk_stok,
        COALESCE(SUM(CASE 
            WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
            WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah
            WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah
            WHEN i.tipe_transaksi = 'return' THEN i.jumlah
            ELSE 0 
        END), 0) as inventory_sum,
        p.stok - COALESCE(SUM(CASE 
            WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
            WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah
            WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah
            WHEN i.tipe_transaksi = 'return' THEN i.jumlah
            ELSE 0 
        END), 0) as diff
    FROM produk p
    LEFT JOIN inventory i ON p.produk_id = i.produk_id
    WHERE p.status = 'active'
    GROUP BY p.produk_id
    HAVING ABS(
        p.stok - COALESCE(SUM(CASE 
            WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
            WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah
            WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah
            WHEN i.tipe_transaksi = 'return' THEN i.jumlah
            ELSE 0 
        END), 0)
    ) > 0
    ORDER BY ABS(
        p.stok - COALESCE(SUM(CASE 
            WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
            WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah
            WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah
            WHEN i.tipe_transaksi = 'return' THEN i.jumlah
            ELSE 0 
        END), 0)
    ) DESC
";

$result2 = $conn->query($query2);
$has_mismatch = false;

if ($result2->num_rows > 0) {
    while ($row2 = $result2->fetch_assoc()) {
        $has_mismatch = true;
        $status_class = abs($row2['diff']) > 100 ? 'error' : 'warning';
        $status_icon = abs($row2['diff']) > 100 ? '❌' : '⚠️';
        
        echo "<tr class='{$status_class}'>";
        echo "<td>{$row2['nama_produk']}</td>";
        echo "<td>{$row2['kategori']}</td>";
        echo "<td>" . number_format($row2['produk_stok']) . "</td>";
        echo "<td>" . number_format($row2['inventory_sum']) . "</td>";
        echo "<td><strong>" . number_format($row2['diff']) . "</strong></td>";
        echo "<td>{$status_icon}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p class='warning'><strong>⚠️ Stock Mismatch Detected!</strong> Run sync to fix discrepancies.</p>";
} else {
    echo "<tr class='success'><td colspan='6'>✅ All stocks are consistent!</td></tr>";
    echo "</table>";
}

// ====================================
// 3. STOCK TAP PER CABANG SUMMARY
// ====================================
echo "<h3>3️⃣ Stock TAP per Cabang (Current Calculation)</h3>";
echo "<table>";
echo "<tr><th>Cabang</th><th>Total Stock (Qty)</th><th>Stock Nominal (Rp)</th></tr>";

$query3 = "
    SELECT 
        c.nama_cabang,
        SUM(CASE 
            WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
            WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah
            WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah
            WHEN i.tipe_transaksi = 'return' THEN i.jumlah
            ELSE 0 
        END) as total_qty,
        COALESCE(SUM(
            CASE 
                WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
                WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah
                WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah
                WHEN i.tipe_transaksi = 'return' THEN i.jumlah
                ELSE 0 
            END * p.harga
        ), 0) as stok_nominal
    FROM cabang c
    LEFT JOIN inventory i ON c.cabang_id = i.cabang_id
    LEFT JOIN produk p ON i.produk_id = p.produk_id AND p.status = 'active'
    WHERE c.status = 'active'
    GROUP BY c.cabang_id, c.nama_cabang
    HAVING total_qty > 0
    ORDER BY stok_nominal DESC
";

$result3 = $conn->query($query3);
$total_qty = 0;
$total_nominal = 0;

while ($row3 = $result3->fetch_assoc()) {
    $total_qty += $row3['total_qty'];
    $total_nominal += $row3['stok_nominal'];
    
    echo "<tr>";
    echo "<td>{$row3['nama_cabang']}</td>";
    echo "<td>" . number_format($row3['total_qty']) . "</td>";
    echo "<td>Rp " . number_format($row3['stok_nominal'], 0, ',', '.') . "</td>";
    echo "</tr>";
}

echo "<tr class='success'>";
echo "<td><strong>TOTAL</strong></td>";
echo "<td><strong>" . number_format($total_qty) . "</strong></td>";
echo "<td><strong>Rp " . number_format($total_nominal, 0, ',', '.') . "</strong></td>";
echo "</tr>";
echo "</table>";

// ====================================
// 4. RECENT INVENTORY TRANSACTIONS
// ====================================
echo "<h3>4️⃣ Recent Inventory Transactions (Last 10)</h3>";
echo "<table>";
echo "<tr><th>Date</th><th>Cabang</th><th>Produk</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>Ref</th></tr>";

$query4 = "
    SELECT 
        i.tanggal,
        COALESCE(c.nama_cabang, 'N/A') as nama_cabang,
        p.nama_produk,
        i.tipe_transaksi,
        i.jumlah,
        i.stok_sebelum,
        i.stok_sesudah,
        i.referensi
    FROM inventory i
    LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
    LEFT JOIN produk p ON i.produk_id = p.produk_id
    ORDER BY i.inventory_id DESC
    LIMIT 10
";

$result4 = $conn->query($query4);
while ($row4 = $result4->fetch_assoc()) {
    $type_color = $row4['tipe_transaksi'] == 'masuk' ? '#d4edda' : '#f8d7da';
    echo "<tr style='background-color: {$type_color}'>";
    echo "<td>{$row4['tanggal']}</td>";
    echo "<td>{$row4['nama_cabang']}</td>";
    echo "<td>{$row4['nama_produk']}</td>";
    echo "<td><strong>{$row4['tipe_transaksi']}</strong></td>";
    echo "<td>" . number_format($row4['jumlah']) . "</td>";
    echo "<td>" . number_format($row4['stok_sebelum']) . "</td>";
    echo "<td>" . number_format($row4['stok_sesudah']) . "</td>";
    echo "<td>{$row4['referensi']}</td>";
    echo "</tr>";
}
echo "</table>";

// ====================================
// 5. RECOMMENDATIONS
// ====================================
echo "<h3>5️⃣ Recommendations</h3>";
echo "<ul>";

if ($row1['total'] > 0) {
    echo "<li class='warning'>🔧 <strong>Fix NULL cabang_id:</strong> Run UPDATE query to assign proper cabang to orphaned inventory records</li>";
}

if ($has_mismatch) {
    echo "<li class='error'>🔧 <strong>Sync Stock:</strong> Update produk.stok to match inventory SUM or investigate discrepancy root cause</li>";
}

echo "<li>✅ <strong>Query is safe:</strong> Handles all transaction types (masuk, keluar, adjustment, return)</li>";
echo "<li>✅ <strong>No race condition risk:</strong> Uses SUM aggregation, not dependent on single record</li>";
echo "<li>⚡ <strong>Performance tip:</strong> Add index on inventory(cabang_id, produk_id, tipe_transaksi) if table grows large</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Report generated: " . date('Y-m-d H:i:s') . "</em></p>";

$conn->close();
?>
