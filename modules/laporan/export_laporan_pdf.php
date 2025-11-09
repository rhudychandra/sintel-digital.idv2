<?php
require_once '../../config/config.php';
requireLogin();

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
$filter_search = isset($_GET['search']) ? $_GET['search'] : '';

// Apply role-based filtering
if (!$is_admin_or_manager && $user_cabang_id) {
    $filter_cabang = $user_cabang_id;
}

// Build WHERE clause
$where_conditions = ["p.tanggal_penjualan BETWEEN ? AND ?"];
$params = [$filter_start_date, $filter_end_date];
$types = "ss";

if (!empty($filter_cabang)) {
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

// Get sales data
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
WHERE {$where_clause}";

$search_params = $params;
$search_types = $types;

if (!empty($filter_search)) {
    $sales_query .= " AND (p.no_invoice LIKE ? OR r.nama_reseller LIKE ? OR c.nama_cabang LIKE ?)";
    $search_param = "%{$filter_search}%";
    $search_params[] = $search_param;
    $search_params[] = $search_param;
    $search_params[] = $search_param;
    $search_types .= "sss";
}

$sales_query .= " GROUP BY p.penjualan_id ORDER BY p.tanggal_penjualan DESC, p.penjualan_id DESC";

$stmt = $conn->prepare($sales_query);
$stmt->bind_param($search_types, ...$search_params);
$stmt->execute();
$sales_data = $stmt->get_result();

// Set headers for PDF download (using HTML to PDF conversion)
header('Content-Type: application/pdf');
header('Content-Disposition: attachment;filename="Laporan_Penjualan_' . date('Y-m-d_His') . '.pdf"');

// For simple PDF, we'll use HTML with print-friendly CSS
// In production, you should use a library like TCPDF or mPDF
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #667eea;
            font-size: 24pt;
        }
        .info-section {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box h3 {
            margin: 0 0 5px 0;
            font-size: 18pt;
        }
        .stat-box p {
            margin: 0;
            font-size: 9pt;
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 9pt;
            border: 1px solid #5568d3;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 9pt;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .grand-total {
            background: #27ae60 !important;
            color: white;
            font-weight: bold;
            font-size: 11pt;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #7f8c8d;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PENJUALAN</h1>
        <p>Sinar Telkom Dashboard System</p>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <div><strong>Periode:</strong> <?php echo date('d/m/Y', strtotime($filter_start_date)); ?> - <?php echo date('d/m/Y', strtotime($filter_end_date)); ?></div>
            <div><strong>Tanggal Cetak:</strong> <?php echo date('d/m/Y H:i:s'); ?></div>
        </div>
        <div class="info-row">
            <div><strong>Dicetak oleh:</strong> <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo ucfirst($user['role']); ?>)</div>
            <?php if (!empty($filter_cabang)): ?>
                <?php
                $cabang_info = $conn->query("SELECT nama_cabang FROM cabang WHERE cabang_id = " . intval($filter_cabang));
                if ($cabang_info && $cabang_row = $cabang_info->fetch_assoc()):
                ?>
                <div><strong>Cabang:</strong> <?php echo htmlspecialchars($cabang_row['nama_cabang']); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-box">
            <h3>Rp <?php echo number_format($stats['total_penjualan'], 0, ',', '.'); ?></h3>
            <p>Total Penjualan</p>
        </div>
        <div class="stat-box">
            <h3><?php echo number_format($stats['total_transaksi']); ?></h3>
            <p>Total Transaksi</p>
        </div>
        <div class="stat-box">
            <h3>Rp <?php echo number_format($stats['rata_rata'], 0, ',', '.'); ?></h3>
            <p>Rata-rata</p>
        </div>
        <div class="stat-box">
            <h3><?php echo number_format($stats['total_produk_terjual']); ?></h3>
            <p>Produk Terjual</p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 100px;">No Invoice</th>
                <th>Reseller</th>
                <th style="width: 80px;">Cabang</th>
                <th class="text-center" style="width: 50px;">Items</th>
                <th class="text-right" style="width: 90px;">Subtotal</th>
                <th class="text-right" style="width: 90px;">Total</th>
                <th class="text-center" style="width: 60px;">Status</th>
                <th style="width: 70px;">Metode</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $grand_total = 0;
            
            if ($sales_data && $sales_data->num_rows > 0) {
                while ($row = $sales_data->fetch_assoc()) {
                    $grand_total += $row['total'];
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo date('d/m/Y', strtotime($row['tanggal_penjualan'])); ?></td>
                <td><?php echo htmlspecialchars($row['no_invoice']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_reseller']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_cabang']); ?></td>
                <td class="text-center"><?php echo number_format($row['total_items']); ?></td>
                <td class="text-right">Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?></td>
                <td class="text-right">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                <td class="text-center"><?php echo ucfirst($row['status_pembayaran']); ?></td>
                <td><?php echo ucfirst(str_replace('_', ' ', $row['metode_pembayaran'])); ?></td>
            </tr>
            <?php 
                }
            } else {
            ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada data</td>
            </tr>
            <?php } ?>
            
            <?php if ($sales_data && $sales_data->num_rows > 0): ?>
            <tr class="grand-total">
                <td colspan="7" class="text-right">GRAND TOTAL:</td>
                <td colspan="3" class="text-right">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Laporan ini digenerate otomatis oleh Sistem Inventory Sinar Telkom</p>
        <p>Dokumen ini sah tanpa tanda tangan dan stempel</p>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
