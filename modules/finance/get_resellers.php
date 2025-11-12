<?php
require_once '../../config/config.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['cabang']) || empty($_GET['cabang'])) {
    echo json_encode([]);
    exit;
}

$cabang = $_GET['cabang'];
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT reseller_id, nama_reseller FROM reseller WHERE cabang_id = (SELECT cabang_id FROM cabang WHERE nama_cabang = ? AND status = 'active') AND status = 'active' ORDER BY nama_reseller");
$stmt->bind_param("s", $cabang);
$stmt->execute();
$result = $stmt->get_result();
$resellers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode($resellers);
?>
