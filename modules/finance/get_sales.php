<?php
require_once '../../config/config.php';

$conn = getDBConnection();

$tanggal = $_GET['tanggal'] ?? '';
$reseller_str = $_GET['reseller'] ?? '';
$resellers = explode(',', $reseller_str);
if (empty($resellers) || empty($tanggal)) {
    echo json_encode([]);
    exit;
}

try {
    $placeholders = str_repeat('?,', count($resellers) - 1) . '?';
    $stmt = $conn->prepare("SELECT
        r.nama_reseller as reseller,
        dp.nama_produk as produk,
        dp.jumlah as qty,
        dp.harga_satuan,
        (dp.jumlah * dp.harga_satuan) as total,
        pj.metode_pembayaran,
        pj.status_pembayaran as status
    FROM penjualan pj
    JOIN reseller r ON pj.reseller_id = r.reseller_id
    JOIN detail_penjualan dp ON pj.penjualan_id = dp.penjualan_id
    WHERE pj.tanggal_penjualan = ? AND r.nama_reseller IN ($placeholders)");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $types = 's' . str_repeat('s', count($resellers));
    $stmt->bind_param($types, $tanggal, ...$resellers);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
