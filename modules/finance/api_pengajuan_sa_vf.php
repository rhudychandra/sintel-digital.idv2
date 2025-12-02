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

// Resolve warehouse deduction target produk_id by category→segel mapping
function resolve_segel_produk_id(mysqli $conn, int $produk_id): int {
    $stmt = $conn->prepare("SELECT kategori FROM produk WHERE produk_id = ?");
    $stmt->bind_param('i', $produk_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $kat = '';
    if ($res && ($row = $res->fetch_assoc())) { $kat = strtolower(trim($row['kategori'] ?? '')); }
    $stmt->close();

    // Map kategori keywords → exact product names
    $targetName = null;
    if ($kat !== '') {
        $isVoucher = strpos($kat, 'voucher') !== false;
        $isPerdana = strpos($kat, 'perdana') !== false;
        $hasLite   = strpos($kat, 'lite') !== false;
        $hasByU    = strpos($kat, 'byu') !== false;
        $hasRed    = strpos($kat, 'red') !== false || strpos($kat, 's261') !== false;

        if ($isVoucher && $hasLite) {
            $targetName = 'Voucher Fisik Segel Lite';
        } elseif ($isVoucher && $hasByU) {
            $targetName = 'Voucher Fisik Segel ByU';
        } elseif ($isPerdana && $hasByU) {
            $targetName = 'Perdana Segel ByU 0K';
        } elseif ($isPerdana && ($hasLite || $hasRed)) {
            $targetName = 'Perdana Segel Red 0K S261';
        }
    }

    if ($targetName) {
        $stmt2 = $conn->prepare("SELECT produk_id FROM produk WHERE status='active' AND LOWER(nama_produk) = LOWER(?) ORDER BY produk_id LIMIT 1");
        $stmt2->bind_param('s', $targetName);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($res2 && ($row2 = $res2->fetch_assoc())) {
            $stmt2->close();
            return (int)$row2['produk_id'];
        }
        $stmt2->close();
    }
    return $produk_id; // fallback to original
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_body();
    $tanggal = isset($body['tanggal']) ? $body['tanggal'] : date('Y-m-d');
    $rs_type = isset($body['rs_type']) ? $body['rs_type'] : 'sa';
    $items = isset($body['items']) && is_array($body['items']) ? $body['items'] : [];

    if (empty($items)) { echo json_encode(['ok' => false, 'error' => 'Payload kosong']); exit; }

    $conn->begin_transaction();
    $debugStock = isset($_GET['debug_stock']) && ($_GET['debug_stock'] === '1' || $_GET['debug_stock'] === 'true');
    $debugDeducts = [];
    try {
        $insHeader = $conn->prepare("INSERT INTO pengajuan_stock (tanggal, rs_type, outlet_id, requester_id, jenis, warehouse_id, total_qty, total_saldo, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
        $insItem   = $conn->prepare("INSERT INTO pengajuan_stock_items (pengajuan_id, produk_id, qty, harga, nominal) VALUES (?,?,?,?,?)");
        // Prepared statement to fetch HPP Saldo per produk
        $stmtHarga = $conn->prepare("SELECT hpp_saldo FROM produk WHERE produk_id = ?");

        // Inventory columns detection (optional status_approval)
        $hasApproval = table_has_column($conn, 'inventory', 'status_approval');
        // Build inventory insert prepared statements (with/without approval)
        if ($hasApproval) {
            $insInv = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id, cabang_id, status_approval) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        } else {
            $insInv = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id, cabang_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
        }
        // Prepared statement to compute stok sebelum per produk & cabang
        $sumInv = $conn->prepare("SELECT COALESCE(SUM(CASE WHEN tipe_transaksi='masuk' THEN jumlah ELSE -jumlah END),0) AS total FROM inventory WHERE produk_id=? AND cabang_id=?");
        // For debug enrichment
        $stmtProdName = $conn->prepare("SELECT nama_produk FROM produk WHERE produk_id = ?");
        $stmtCabName = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
        $stmtResCab = $conn->prepare("SELECT cabang_id, nama_reseller FROM reseller WHERE reseller_id = ?");
        $stmtOutletInfo = $conn->prepare("SELECT nama_outlet, nomor_rs FROM outlet WHERE outlet_id = ?");

        $created_by = isset($user['user_id']) ? intval($user['user_id']) : 0;
        $saved = 0;
        $priceCache = [];

        foreach ($items as $it) {
            if (empty($it['produk']) || !is_array($it['produk'])) continue;

            $outlet_id    = intval($it['outlet_id']);
            $requester_id = intval($it['requester_id']);
            $jenis        = isset($it['jenis']) ? $it['jenis'] : '';
            $warehouse_id = intval($it['warehouse_id']);

            $total_qty = 0; $total_saldo = 0;
            $computed = [];
            foreach ($it['produk'] as $p) {
                $q = intval($p['qty']);
                $pid = intval($p['produk_id']);
                if ($q <= 0 || $pid <= 0) { continue; }
                // Get HPP Saldo from cache or DB
                if (array_key_exists($pid, $priceCache)) {
                    $h = $priceCache[$pid];
                } else {
                    $stmtHarga->bind_param('i', $pid);
                    $stmtHarga->execute();
                    $resH = $stmtHarga->get_result();
                    $rowH = $resH ? $resH->fetch_assoc() : null;
                    $h = $rowH ? floatval($rowH['hpp_saldo']) : 0.0;
                    $priceCache[$pid] = $h;
                }
                $total_qty  += $q;
                $total_saldo+= ($q * $h);
                $computed[] = ['produk_id' => $pid, 'qty' => $q, 'harga' => $h];
            }

            // tanggal(s), rs_type(s), outlet_id(i), requester_id(i), jenis(s), warehouse_id(i), total_qty(i), total_saldo(d), created_by(i)
            $insHeader->bind_param('ssiiisidi', $tanggal, $rs_type, $outlet_id, $requester_id, $jenis, $warehouse_id, $total_qty, $total_saldo, $created_by);
            if (!$insHeader->execute()) throw new Exception('Gagal insert header: '.$conn->error);
            $pengajuan_id = $insHeader->insert_id;

            // Resolve info for destination cabang (from requester)
            $dest_cabang_id = null; $dest_cabang_name = null; $requester_name = null;
            if ($requester_id > 0) {
                try {
                    $stmtResCab->bind_param('i', $requester_id);
                    $stmtResCab->execute();
                    $rres = $stmtResCab->get_result();
                    if ($rres && ($rr = $rres->fetch_assoc())) {
                        $dest_cabang_id = isset($rr['cabang_id']) ? (int)$rr['cabang_id'] : null;
                        $requester_name = $rr['nama_reseller'] ?? '';
                        if ($dest_cabang_id) {
                            $stmtCabName->bind_param('i', $dest_cabang_id);
                            $stmtCabName->execute();
                            $rc = $stmtCabName->get_result();
                            if ($rc && ($cr = $rc->fetch_assoc())) { $dest_cabang_name = $cr['nama_cabang']; }
                        }
                    }
                } catch (Exception $e) { /* ignore */ }
            }
            $warehouse_name = null;
            if ($warehouse_id > 0) {
                try {
                    $stmtCabName->bind_param('i', $warehouse_id);
                    $stmtCabName->execute();
                    $rcw = $stmtCabName->get_result();
                    if ($rcw && ($cw = $rcw->fetch_assoc())) { $warehouse_name = $cw['nama_cabang']; }
                } catch (Exception $e) { /* ignore */ }
            }
            $outlet_name = null; $no_rs = null;
            if ($outlet_id > 0) {
                try {
                    $stmtOutletInfo->bind_param('i', $outlet_id);
                    $stmtOutletInfo->execute();
                    $roi = $stmtOutletInfo->get_result();
                    if ($roi && ($oi = $roi->fetch_assoc())) { $outlet_name = $oi['nama_outlet']; $no_rs = $oi['nomor_rs']; }
                } catch (Exception $e) { /* ignore */ }
            }

            foreach ($computed as $pcalc) {
                $produk_id = $pcalc['produk_id'];
                $qty       = $pcalc['qty'];
                $harga     = $pcalc['harga']; // HPP Saldo
                $nominal   = $qty * $harga;
                $insItem->bind_param('iiidd', $pengajuan_id, $produk_id, $qty, $harga, $nominal);
                if (!$insItem->execute()) throw new Exception('Gagal insert item: '.$conn->error);

                // Insert inventory keluar log to deduct warehouse stock
                // Apply category→segel mapping for deduction target
                $target_produk_id = resolve_segel_produk_id($conn, $produk_id);
                $sumInv->bind_param('ii', $target_produk_id, $warehouse_id);
                $sumInv->execute();
                $sumRes = $sumInv->get_result();
                $stokSebelum = 0;
                if ($sumRes && ($sr = $sumRes->fetch_assoc())) { $stokSebelum = (int)$sr['total']; }
                // Pending approval flow: stok_sesudah equals stok_sebelum until approved
                $stokSesudah = $stokSebelum;

                $ref = 'PSAVF-' . $pengajuan_id;
                $tujuan_text = $dest_cabang_name ? (' | Tujuan: ' . $dest_cabang_name) : '';
                $rs_text = $outlet_name ? (' | RS: ' . $outlet_name . ($no_rs ? (' (' . $no_rs . ')') : '')) : '';
                $ket = 'Pengajuan SA/VF: pengurangan stok gudang' . $tujuan_text . $rs_text . ' | Ref: ' . $ref;
                $tipe = 'keluar';

                if ($hasApproval) {
                    $status = 'pending';
                    $insInv->bind_param('issiiisssis', $target_produk_id, $tanggal, $tipe, $qty, $stokSebelum, $stokSesudah, $ref, $ket, $created_by, $warehouse_id, $status);
                } else {
                    $insInv->bind_param('issiiisssi', $target_produk_id, $tanggal, $tipe, $qty, $stokSebelum, $stokSesudah, $ref, $ket, $created_by, $warehouse_id);
                }
                if (!$insInv->execute()) throw new Exception('Gagal insert inventory keluar: '.$conn->error);

                // Insert inventory masuk (pending) to destination cabang with ORIGINAL product (not segel)
                if (!empty($dest_cabang_id)) {
                    $sumInv->bind_param('ii', $produk_id, $dest_cabang_id);
                    $sumInv->execute();
                    $sumRes2 = $sumInv->get_result();
                    $stokSebelumDest = 0;
                    if ($sumRes2 && ($sd = $sumRes2->fetch_assoc())) { $stokSebelumDest = (int)$sd['total']; }
                    $stokSesudahDest = $stokSebelumDest; // pending
                    $tipe2 = 'masuk';
                    $ket2 = 'Stock Masuk - Pengajuan SA/VF dari ' . ($warehouse_name ?: 'Warehouse') . $rs_text . ' | Ref: ' . $ref;
                    if ($hasApproval) {
                        $status2 = 'pending';
                        $insInv->bind_param('issiiisssis', $produk_id, $tanggal, $tipe2, $qty, $stokSebelumDest, $stokSesudahDest, $ref, $ket2, $created_by, $dest_cabang_id, $status2);
                    } else {
                        $insInv->bind_param('issiiisssi', $produk_id, $tanggal, $tipe2, $qty, $stokSebelumDest, $stokSesudahDest, $ref, $ket2, $created_by, $dest_cabang_id);
                    }
                    if (!$insInv->execute()) throw new Exception('Gagal insert inventory masuk (tujuan): '.$conn->error);
                }

                if ($debugStock) {
                    // Fetch readable names
                    $origName = null; $targetName = null; $cabName = null;
                    try {
                        $stmtProdName->bind_param('i', $produk_id);
                        $stmtProdName->execute();
                        $rpn = $stmtProdName->get_result();
                        if ($rpn && ($rn = $rpn->fetch_assoc())) { $origName = $rn['nama_produk']; }
                    } catch (Exception $e) {}
                    try {
                        $stmtProdName->bind_param('i', $target_produk_id);
                        $stmtProdName->execute();
                        $rpn2 = $stmtProdName->get_result();
                        if ($rpn2 && ($rn2 = $rpn2->fetch_assoc())) { $targetName = $rn2['nama_produk']; }
                    } catch (Exception $e) {}
                    try {
                        $stmtCabName->bind_param('i', $warehouse_id);
                        $stmtCabName->execute();
                        $rcn = $stmtCabName->get_result();
                        if ($rcn && ($cn = $rcn->fetch_assoc())) { $cabName = $cn['nama_cabang']; }
                    } catch (Exception $e) {}
                    $debugDeducts[] = [
                        'direction' => 'keluar',
                        'original_produk_id' => $produk_id,
                        'original_produk' => $origName,
                        'target_produk_id' => $target_produk_id,
                        'target_produk' => $targetName,
                        'qty' => $qty,
                        'warehouse_id' => $warehouse_id,
                        'warehouse' => $cabName,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'dest_cabang_id' => $dest_cabang_id,
                        'dest_cabang' => $dest_cabang_name
                    ];
                    if (!empty($dest_cabang_id)) {
                        $debugDeducts[] = [
                            'direction' => 'masuk',
                            'original_produk_id' => $produk_id,
                            'original_produk' => $origName,
                            'qty' => $qty,
                            'dest_cabang_id' => $dest_cabang_id,
                            'dest_cabang' => $dest_cabang_name,
                            'stok_sebelum' => $stokSebelumDest,
                            'stok_sesudah' => $stokSesudahDest
                        ];
                    }
                }
            }
            $saved++;
        }

        $conn->commit();
        if (isset($stmtHarga)) { $stmtHarga->close(); }
        if (isset($insInv)) { $insInv->close(); }
        if (isset($sumInv)) { $sumInv->close(); }
        if (isset($stmtProdName)) { $stmtProdName->close(); }
        if (isset($stmtCabName)) { $stmtCabName->close(); }
        if (isset($stmtResCab)) { $stmtResCab->close(); }
        if (isset($stmtOutletInfo)) { $stmtOutletInfo->close(); }
        $resp = ['ok' => true, 'saved_outlets' => $saved];
        if ($debugStock) { $resp['debug_deductions'] = $debugDeducts; }
        echo json_encode($resp);
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
    CASE 
        WHEN ps.jenis IN ('NGRS','LinkAja','Finpay') THEN ps.jenis
        WHEN ps.jenis = '0' OR ps.jenis = 0 THEN 'NGRS'
        WHEN ps.jenis = '1' OR ps.jenis = 1 THEN 'LinkAja'
        WHEN ps.jenis = '2' OR ps.jenis = 2 THEN 'Finpay'
        ELSE ps.jenis
    END AS jenis,
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
