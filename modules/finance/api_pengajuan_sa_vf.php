<?php
require_once '../../config/config.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser();
if (!in_array($user['role'], ['administrator','manager','finance'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Akses ditolak']);
    exit;
}

$conn = getDBConnection();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

function json_body() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function table_has_column($conn, $table, $column) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM $table LIKE ?");
        $stmt->bind_param('s', $column);
        $stmt->execute();
        $res = $stmt->get_result();
        return ($res && $res->num_rows > 0);
    } catch (Exception $e) { return false; }
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_body();
    $tanggal = isset($body['tanggal']) ? $body['tanggal'] : date('Y-m-d');
    $rs_type = isset($body['rs_type']) ? $body['rs_type'] : 'sa';
    $items = isset($body['items']) && is_array($body['items']) ? $body['items'] : [];

    if (empty($items)) { echo json_encode(['ok' => false, 'error' => 'Payload kosong']); exit; }

    $conn->begin_transaction();
    try {
        $insHeader = $conn->prepare("INSERT INTO pengajuan_stock (tanggal, rs_type, outlet_id, requester_id, jenis, warehouse_id, total_qty, total_saldo, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
        $insItem   = $conn->prepare("INSERT INTO pengajuan_stock_items (pengajuan_id, produk_id, qty, harga, nominal) VALUES (?,?,?,?,?)");

        $created_by = isset($user['user_id']) ? intval($user['user_id']) : 0;
        $saved = 0;

        foreach ($items as $it) {
            if (empty($it['produk']) || !is_array($it['produk'])) continue;

            $outlet_id    = intval($it['outlet_id']);
            $requester_id = intval($it['requester_id']);
            $jenis        = isset($it['jenis']) ? $it['jenis'] : '';
            $warehouse_id = intval($it['warehouse_id']);

            $total_qty = 0; $total_saldo = 0;
            foreach ($it['produk'] as $p) {
                $q = intval($p['qty']);
                $h = floatval($p['harga']);
                $total_qty  += $q;
                $total_saldo+= ($q * $h);
            }

            // tanggal(s), rs_type(s), outlet_id(i), requester_id(i), jenis(s), warehouse_id(i), total_qty(i), total_saldo(d), created_by(i)
            $insHeader->bind_param('ssiiisidi', $tanggal, $rs_type, $outlet_id, $requester_id, $jenis, $warehouse_id, $total_qty, $total_saldo, $created_by);
            if (!$insHeader->execute()) throw new Exception('Gagal insert header: '.$conn->error);
            $pengajuan_id = $insHeader->insert_id;

            foreach ($it['produk'] as $p) {
                $produk_id = intval($p['produk_id']);
                $qty       = intval($p['qty']);
                $harga     = floatval($p['harga']);
                $nominal   = $qty * $harga;
                if ($qty <= 0 || $produk_id <= 0) continue;
                $insItem->bind_param('iiidd', $pengajuan_id, $produk_id, $qty, $harga, $nominal);
                if (!$insItem->execute()) throw new Exception('Gagal insert item: '.$conn->error);
            }
            $saved++;
        }

        $conn->commit();
        echo json_encode(['ok' => true, 'saved_outlets' => $saved]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        error_log('API Save Error: '.$e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'Terjadi kesalahan internal pada server.']);
    }
    exit;
}

if ($action === 'search_outlet') {
    $rsType = isset($_GET['rs_type']) ? $_GET['rs_type'] : 'sa';
    $q      = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit  = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 30;

    $where = [];
    $hasJenis = table_has_column($conn, 'outlet', 'jenis_rs');
    $hasType  = table_has_column($conn, 'outlet', 'type_outlet');
    $hasKab   = table_has_column($conn, 'outlet', 'kabupaten');
    $hasCity  = table_has_column($conn, 'outlet', 'city');

    $typeConds = [];
    if ($hasJenis) $typeConds[] = $rsType === 'sa' ? "jenis_rs LIKE '%RS Eksekusi SA%'" : "jenis_rs LIKE '%RS Eksekusi Voucher%'";
    if ($hasType)  $typeConds[] = $rsType === 'sa' ? "type_outlet LIKE '%RS Eksekusi SA%'" : "type_outlet LIKE '%RS Eksekusi Voucher%'";
    if (count($typeConds)) $where[] = '(' . implode(' OR ', $typeConds) . ')';

    if ($q !== '') {
        $esc = $conn->real_escape_string($q);
        $where[] = "(nama_outlet LIKE '%$esc%' OR nomor_rs LIKE '%$esc%' OR id_digipos LIKE '%$esc%')";
    }
    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $wil = $hasKab ? 'kabupaten' : ($hasCity ? 'city' : "''");
    $sql = "SELECT outlet_id, nama_outlet, nomor_rs, id_digipos, $wil AS wilayah FROM outlet $whereSql ORDER BY nama_outlet LIMIT $limit";
    $rows = [];
    $res = $conn->query($sql);
    if ($res) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }
    else { echo json_encode(['ok' => false, 'error' => $conn->error, 'sql' => $sql]); exit; }
    echo json_encode(['ok' => true, 'items' => $rows]);
    exit;
}

// Default: list history (latest first)
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 100;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset= ($page - 1) * $limit;
$rs_type   = isset($_GET['rs_type']) ? $_GET['rs_type'] : null;
$date_from = isset($_GET['from']) ? $_GET['from'] : null;
$date_to   = isset($_GET['to']) ? $_GET['to'] : null;
$requester_id = isset($_GET['requester_id']) ? intval($_GET['requester_id']) : null;
$cabang_id    = isset($_GET['cabang_id']) ? intval($_GET['cabang_id']) : null;
$warehouse_id = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : null;
$produk_id    = isset($_GET['produk_id']) ? intval($_GET['produk_id']) : null;

$where = [];
if ($rs_type)      $where[] = "ps.rs_type='" . $conn->real_escape_string($rs_type) . "'";
if ($date_from)    $where[] = "ps.tanggal >= '" . $conn->real_escape_string($date_from) . "'";
if ($date_to)      $where[] = "ps.tanggal <= '" . $conn->real_escape_string($date_to) . "'";
if ($requester_id) $where[] = "ps.requester_id = $requester_id";
if ($cabang_id)    $where[] = "r.cabang_id = $cabang_id";
if ($warehouse_id) $where[] = "ps.warehouse_id = $warehouse_id";
if ($produk_id)    $where[] = "psi.produk_id = $produk_id";
$whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT 
    ps.tanggal,
    ps.rs_type,
    o.nama_outlet AS rs_eksekusi,
    o.nomor_rs AS no_rs,
    r.nama_reseller AS requester,
    c2.nama_cabang AS cabang,
    ps.jenis,
    c1.nama_cabang AS warehouse,
    p.nama_produk AS produk,
    psi.qty,
    psi.nominal
FROM pengajuan_stock ps
JOIN pengajuan_stock_items psi ON psi.pengajuan_id = ps.id
LEFT JOIN outlet o ON o.outlet_id = ps.outlet_id
LEFT JOIN reseller r ON r.reseller_id = ps.requester_id
LEFT JOIN cabang c1 ON c1.cabang_id = ps.warehouse_id
LEFT JOIN cabang c2 ON c2.cabang_id = r.cabang_id
LEFT JOIN produk p ON p.produk_id = psi.produk_id
$whereSql
ORDER BY ps.created_at DESC, ps.id DESC, psi.id DESC
LIMIT $limit OFFSET $offset";

$rows = [];
$q = $conn->query($sql);
if ($q) { while ($row = $q->fetch_assoc()) { $rows[] = $row; } }

$has_more = count($rows) === $limit;
echo json_encode(['ok' => true, 'items' => $rows, 'page' => $page, 'limit' => $limit, 'has_more' => $has_more]);
