<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

echo "<h1>Debug Stock Keluar Page</h1>";
echo "<hr>";

echo "<h2>1. User Info:</h2>";
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h2>2. Test Cabang List Form:</h2>";
$cabang_list_form = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");
if ($cabang_list_form) {
    echo "✅ Query success. Rows: " . $cabang_list_form->num_rows . "<br>";
    while ($c = $cabang_list_form->fetch_assoc()) {
        echo "- " . $c['nama_cabang'] . "<br>";
    }
} else {
    echo "❌ Query failed: " . $conn->error . "<br>";
}

echo "<h2>3. Test Produk List:</h2>";
$produk_list = $conn->query("SELECT produk_id, nama_produk, stok FROM produk WHERE status = 'active' ORDER BY nama_produk");
if ($produk_list) {
    echo "✅ Query success. Rows: " . $produk_list->num_rows . "<br>";
    while ($p = $produk_list->fetch_assoc()) {
        echo "- " . $p['nama_produk'] . " (Stok: " . $p['stok'] . ")<br>";
    }
} else {
    echo "❌ Query failed: " . $conn->error . "<br>";
}

echo "<h2>4. Test Stock Keluar Data:</h2>";
$filter_start_date = date('Y-m-01');
$filter_end_date = date('Y-m-d');

$where_conditions = ["i.tipe_transaksi = 'keluar'"];
$params = [];
$types = "";

$where_conditions[] = "i.tanggal BETWEEN ? AND ?";
$params[] = $filter_start_date;
$params[] = $filter_end_date;
$types .= "ss";

if (!in_array($user['role'], ['administrator', 'manager'])) {
    $where_conditions[] = "(i.cabang_id = ? OR i.cabang_id IS NULL)";
    $params[] = $user['cabang_id'];
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

$stock_keluar_query = "SELECT 
    i.inventory_id,
    i.tanggal,
    i.jumlah,
    i.referensi,
    i.keterangan,
    i.status_approval,
    p.nama_produk,
    p.kategori,
    p.harga,
    COALESCE(c.nama_cabang, 'Pusat/Global') as nama_cabang
FROM inventory i
LEFT JOIN produk p ON i.produk_id = p.produk_id
LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
LEFT JOIN users u ON i.user_id = u.user_id
WHERE " . $where_clause . "
ORDER BY i.tanggal DESC
LIMIT 10";

$stmt = $conn->prepare($stock_keluar_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

echo "✅ Query executed. Rows: " . $result->num_rows . "<br>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

$conn->close();
?>
