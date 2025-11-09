<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Stock_Report_' . date('Y-m-d_His') . '.xls"');
header('Cache-Control: max-age=0');

// Get stock data based on role
if (in_array($user['role'], ['administrator', 'manager'])) {
    // For Administrator & Manager: Get all cabang for pivot table
    $cabang_data = [];
    $cabang_result = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");
    while ($row = $cabang_result->fetch_assoc()) {
        $cabang_data[] = $row;
    }
    
    // Get all products with stock per cabang
    $stock_pivot_data = [];
    $all_products = $conn->query("SELECT produk_id, nama_produk, kategori, harga FROM produk WHERE status = 'active' ORDER BY nama_produk");
    
    while ($product = $all_products->fetch_assoc()) {
        $product_id = $product['produk_id'];
        $stock_pivot_data[$product_id] = [
            'nama_produk' => $product['nama_produk'],
            'kategori' => $product['kategori'],
            'harga' => $product['harga'],
            'cabang_stocks' => []
        ];
        
        // Get stock for each cabang
        foreach ($cabang_data as $cabang) {
            $cabang_id = $cabang['cabang_id'];
            
            $stock_query = "SELECT 
                COALESCE(SUM(CASE WHEN tipe_transaksi = 'masuk' THEN jumlah ELSE -jumlah END), 0) as total_stock
                FROM inventory 
                WHERE produk_id = ? 
                AND cabang_id = ? 
                AND status_approval = 'approved'";
            $stmt = $conn->prepare($stock_query);
            $stmt->bind_param("ii", $product_id, $cabang_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stock_pivot_data[$product_id]['cabang_stocks'][$cabang_id] = $row['total_stock'];
            } else {
                $stock_pivot_data[$product_id]['cabang_stocks'][$cabang_id] = 0;
            }
        }
    }
    
    // Output Excel for Administrator/Manager
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
    echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
    echo '.header-row { background-color: #2196F3; color: white; }';
    echo '.total-row { background-color: #f0f0f0; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<h2>Laporan Stock Per Cabang</h2>';
    echo '<p>Tanggal Export: ' . date('d F Y H:i:s') . '</p>';
    echo '<p>User: ' . htmlspecialchars($user['full_name']) . '</p>';
    echo '<br>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th rowspan="2">No</th>';
    echo '<th rowspan="2">Produk</th>';
    echo '<th rowspan="2">Kategori</th>';
    echo '<th colspan="' . count($cabang_data) . '" class="header-row">Stock Per Cabang</th>';
    echo '<th rowspan="2">Total Stock</th>';
    echo '<th rowspan="2">Harga</th>';
    echo '<th rowspan="2">Total Nilai</th>';
    echo '</tr>';
    echo '<tr>';
    foreach ($cabang_data as $cabang) {
        echo '<th class="header-row">' . htmlspecialchars($cabang['nama_cabang']) . '</th>';
    }
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    $grand_total_stock = 0;
    $grand_total_nilai = 0;
    
    foreach ($stock_pivot_data as $product_id => $data) {
        $total_stock = array_sum($data['cabang_stocks']);
        $total_nilai = $total_stock * $data['harga'];
        $grand_total_stock += $total_stock;
        $grand_total_nilai += $total_nilai;
        
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($data['nama_produk']) . '</td>';
        echo '<td>' . htmlspecialchars($data['kategori']) . '</td>';
        
        foreach ($cabang_data as $cabang) {
            $stock = $data['cabang_stocks'][$cabang['cabang_id']] ?? 0;
            echo '<td style="text-align: center;">' . number_format($stock) . '</td>';
        }
        
        echo '<td style="text-align: center;"><strong>' . number_format($total_stock) . '</strong></td>';
        echo '<td style="text-align: right;">Rp ' . number_format($data['harga'], 0, ',', '.') . '</td>';
        echo '<td style="text-align: right;"><strong>Rp ' . number_format($total_nilai, 0, ',', '.') . '</strong></td>';
        echo '</tr>';
    }
    
    // Grand Total Row
    echo '<tr class="total-row">';
    echo '<td colspan="' . (3 + count($cabang_data)) . '" style="text-align: right;">GRAND TOTAL:</td>';
    echo '<td style="text-align: center;"><strong>' . number_format($grand_total_stock) . '</strong></td>';
    echo '<td></td>';
    echo '<td style="text-align: right;"><strong>Rp ' . number_format($grand_total_nilai, 0, ',', '.') . '</strong></td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
} else {
    // For other roles: Simple table
    $user_cabang_id = $user['cabang_id'];
    
    $stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
    $stmt->bind_param("i", $user_cabang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_cabang_name = $result->num_rows > 0 ? $result->fetch_assoc()['nama_cabang'] : 'Unknown';
    
    $stock_simple_data = [];
    $all_products = $conn->query("SELECT produk_id, nama_produk, kategori, harga FROM produk WHERE status = 'active' ORDER BY nama_produk");
    
    while ($product = $all_products->fetch_assoc()) {
        $product_id = $product['produk_id'];
        
        $stock_query = "SELECT 
            COALESCE(SUM(CASE WHEN tipe_transaksi = 'masuk' THEN jumlah ELSE -jumlah END), 0) as total_stock
            FROM inventory 
            WHERE produk_id = ? 
            AND cabang_id = ? 
            AND status_approval = 'approved'";
        $stmt = $conn->prepare($stock_query);
        $stmt->bind_param("ii", $product_id, $user_cabang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stock = 0;
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stock = $row['total_stock'];
        }
        
        $stock_simple_data[] = [
            'nama_produk' => $product['nama_produk'],
            'kategori' => $product['kategori'],
            'harga' => $product['harga'],
            'stock' => $stock,
            'nilai' => $stock * $product['harga']
        ];
    }
    
    // Output Excel for other roles
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
    echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
    echo '.total-row { background-color: #f0f0f0; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<h2>Laporan Stock - ' . htmlspecialchars($user_cabang_name) . '</h2>';
    echo '<p>Tanggal Export: ' . date('d F Y H:i:s') . '</p>';
    echo '<p>User: ' . htmlspecialchars($user['full_name']) . '</p>';
    echo '<br>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>Cabang</th>';
    echo '<th>Produk</th>';
    echo '<th>Kategori</th>';
    echo '<th>Qty</th>';
    echo '<th>Harga</th>';
    echo '<th>Nilai Stock</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    $total_qty = 0;
    $total_nilai = 0;
    
    foreach ($stock_simple_data as $item) {
        $total_qty += $item['stock'];
        $total_nilai += $item['nilai'];
        
        $status = '';
        if ($item['stock'] == 0) $status = 'Out of Stock';
        elseif ($item['stock'] < 10) $status = 'Low Stock';
        elseif ($item['stock'] < 50) $status = 'Medium Stock';
        else $status = 'Good Stock';
        
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($user_cabang_name) . '</td>';
        echo '<td>' . htmlspecialchars($item['nama_produk']) . '</td>';
        echo '<td>' . htmlspecialchars($item['kategori']) . '</td>';
        echo '<td style="text-align: center;"><strong>' . number_format($item['stock']) . '</strong></td>';
        echo '<td style="text-align: right;">Rp ' . number_format($item['harga'], 0, ',', '.') . '</td>';
        echo '<td style="text-align: right;"><strong>Rp ' . number_format($item['nilai'], 0, ',', '.') . '</strong></td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    
    // Total Row
    echo '<tr class="total-row">';
    echo '<td colspan="4" style="text-align: right;">TOTAL:</td>';
    echo '<td style="text-align: center;"><strong>' . number_format($total_qty) . '</strong></td>';
    echo '<td></td>';
    echo '<td style="text-align: right;"><strong>Rp ' . number_format($total_nilai, 0, ',', '.') . '</strong></td>';
    echo '<td></td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
}

echo '<br><br>';
echo '<p style="font-size: 12px; color: #666;">Generated by Sinar Telkom Dashboard System</p>';
echo '</body>';
echo '</html>';

$conn->close();
?>
