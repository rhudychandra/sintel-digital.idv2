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

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Laporan_Penjualan_' . date('Y-m-d_His') . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
echo '<style>';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
echo 'th { background-color: #667eea; color: white; font-weight: bold; }';
echo '.number { text-align: right; }';
echo '.center { text-align: center; }';
echo '.header { background-color: #f8f9fa; font-weight: bold; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Title
echo '<h2>LAPORAN PENJUALAN</h2>';
echo '<p><strong>Periode:</strong> ' . date('d/m/Y', strtotime($filter_start_date)) . ' - ' . date('d/m/Y', strtotime($filter_end_date)) . '</p>';
echo '<p><strong>Dicetak oleh:</strong> ' . htmlspecialchars($user['full_name']) . ' (' . ucfirst($user['role']) . ')</p>';
echo '<p><strong>Tanggal Cetak:</strong> ' . date('d/m/Y H:i:s') . '</p>';
echo '<br>';

// Table
echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th class="center">No</th>';
echo '<th>Tanggal</th>';
echo '<th>No Invoice</th>';
echo '<th>Reseller</th>';
echo '<th>Cabang</th>';
echo '<th class="center">Total Items</th>';
echo '<th class="number">Subtotal</th>';
echo '<th class="number">Total</th>';
echo '<th class="center">Status</th>';
echo '<th>Metode Pembayaran</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$no = 1;
$grand_total = 0;

if ($sales_data && $sales_data->num_rows > 0) {
    while ($row = $sales_data->fetch_assoc()) {
        $grand_total += $row['total'];
        
        echo '<tr>';
        echo '<td class="center">' . $no++ . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($row['tanggal_penjualan'])) . '</td>';
        echo '<td>' . htmlspecialchars($row['no_invoice']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_reseller']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_cabang']) . '</td>';
        echo '<td class="center">' . number_format($row['total_items']) . '</td>';
        echo '<td class="number">Rp ' . number_format($row['subtotal'], 0, ',', '.') . '</td>';
        echo '<td class="number">Rp ' . number_format($row['total'], 0, ',', '.') . '</td>';
        echo '<td class="center">' . ucfirst($row['status_pembayaran']) . '</td>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $row['metode_pembayaran'])) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="10" class="center">Tidak ada data</td></tr>';
}

// Grand Total
if ($sales_data && $sales_data->num_rows > 0) {
    echo '<tr class="header">';
    echo '<td colspan="7" class="number"><strong>GRAND TOTAL:</strong></td>';
    echo '<td colspan="3" class="number"><strong>Rp ' . number_format($grand_total, 0, ',', '.') . '</strong></td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

echo '<br><br>';
echo '<p><em>Laporan ini digenerate otomatis oleh Sistem Inventory Sinar Telkom</em></p>';

echo '</body>';
echo '</html>';

$conn->close();
exit();
?>
