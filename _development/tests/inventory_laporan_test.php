<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Script started -->\n";

try {
    require_once 'config.php';
    echo "<!-- Debug: config.php loaded -->\n";
    
    requireLogin();
    echo "<!-- Debug: requireLogin passed -->\n";
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$user = getCurrentUser();
$conn = getDBConnection();

// Determine user access level
$is_admin_or_manager = in_array($user['role'], ['administrator', 'manager']);
$user_cabang_id = $user['cabang_id'];

// Get filter parameters
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';
$filter_reseller = isset($_GET['reseller_id']) ? $_GET['reseller_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Check if cabang_id column exists
$check_column = $conn->query("SHOW COLUMNS FROM penjualan LIKE 'cabang_id'");
$has_cabang_column = ($check_column && $check_column->num_rows > 0);

// Apply role-based filtering
if (!$is_admin_or_manager && $user_cabang_id && $has_cabang_column) {
    $filter_cabang = $user_cabang_id;
}

// Build WHERE clause
$where_conditions = ["p.tanggal_penjualan BETWEEN ? AND ?"];
$params = [$filter_start_date, $filter_end_date];
$types = "ss";

if (!empty($filter_cabang) && $has_cabang_column) {
    $where_conditions[] = "p.cabang_id = ?";
    $params[] = $filter_cabang;
    $types .= "i";
}

if (!empty($filter_reseller)) {
    $where_conditions[] = "p.reseller_id = ?";
    $params[] = $filter_reseller;
    $types .= "i";
}

if (!empty($filter_status)) {
    $where_conditions[] = "p.status_pembayaran = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get summary statistics
$stats_query = "SELECT 
    COUNT(DISTINCT p.penjualan_id) as total_transaksi,
    COALESCE(SUM(p.total), 0) as total_penjualan,
    COALESCE(AVG(p.total), 0) as rata_rata,
    COALESCE(SUM(dp.jumlah), 0) as total_produk_terjual
FROM penjualan p
LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
WHERE {$where_clause}";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Get detailed sales data
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
GROUP BY p.penjualan_id ORDER BY p.tanggal_penjualan DESC, p.penjualan_id DESC";

$stmt = $conn->prepare($sales_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$sales_data = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Laporan Penjualan</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #667eea; color: white; }
        tr:nth-child(even) { background: #f8f9fa; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; }
        .stat-card h3 { margin: 0 0 10px 0; color: #667eea; }
        .stat-card p { margin: 0; font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>📋 Test Laporan Penjualan</h1>
    <p>User: <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo $user['role']; ?>)</p>
    <p>Periode: <?php echo date('d/m/Y', strtotime($filter_start_date)); ?> - <?php echo date('d/m/Y', strtotime($filter_end_date)); ?></p>
    
    <div class="stats">
        <div class="stat-card">
            <h3>Total Penjualan</h3>
            <p>Rp <?php echo number_format($stats['total_penjualan'], 0, ',', '.'); ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Transaksi</h3>
            <p><?php echo number_format($stats['total_transaksi']); ?></p>
        </div>
        <div class="stat-card">
            <h3>Rata-rata</h3>
            <p>Rp <?php echo number_format($stats['rata_rata'], 0, ',', '.'); ?></p>
        </div>
        <div class="stat-card">
            <h3>Produk Terjual</h3>
            <p><?php echo number_format($stats['total_produk_terjual']); ?></p>
        </div>
    </div>
    
    <h2>Detail Penjualan</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Invoice</th>
                <th>Reseller</th>
                <th>Cabang</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $grand_total = 0;
            if ($sales_data && $sales_data->num_rows > 0) {
                while ($row = $sales_data->fetch_assoc()): 
                    $grand_total += $row['total'];
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo date('d/m/Y', strtotime($row['tanggal_penjualan'])); ?></td>
                <td><strong><?php echo htmlspecialchars($row['no_invoice']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['nama_reseller']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_cabang']); ?></td>
                <td><?php echo number_format($row['total_items']); ?></td>
                <td>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                <td><?php echo ucfirst($row['status_pembayaran']); ?></td>
            </tr>
            <?php 
                endwhile;
            } else { 
            ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px;">
                    Tidak ada data penjualan untuk periode ini
                </td>
            </tr>
            <?php } ?>
        </tbody>
        <?php if ($sales_data && $sales_data->num_rows > 0): ?>
        <tfoot>
            <tr style="background: #667eea; color: white; font-weight: bold;">
                <td colspan="6" style="text-align: right;">GRAND TOTAL:</td>
                <td colspan="2">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
    
    <p><a href="inventory_laporan.php">← Kembali ke Laporan Lengkap</a></p>
    
    <?php $conn->close(); ?>
</body>
</html>
