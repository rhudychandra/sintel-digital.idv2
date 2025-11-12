<?php
// Seed initial inventory 'masuk' per product into a chosen cabang
// Usage examples (PowerShell):
//   & "c:\\xampp\\php\\php.exe" "c:\\xampp\\htdocs\\sinartelekomdashboardsystem\\database\\migrations\\seed_initial_inventory.php" cabang_id=1 qty=100 user_id=1
//   & "c:\\xampp\\php\\php.exe" "c:\\xampp\\htdocs\\sinartelekomdashboardsystem\\database\\migrations\\seed_initial_inventory.php" list_cabang=1

require_once __DIR__ . '/../../config/config.php';

function println($s=''){ echo $s.PHP_EOL; }

// Parse args
$args = [];
foreach ($argv as $arg) {
    if (strpos($arg, '=') !== false) {
        [$k,$v] = explode('=', $arg, 2);
        $args[strtolower(trim($k))] = trim($v);
    } else {
        $args[strtolower(trim($arg))] = true;
    }
}

$conn = getDBConnection();
$conn->set_charset('utf8mb4');

// Optional: list cabang
if (!empty($args['list_cabang'])) {
    println('Daftar Cabang (active):');
    $res = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status='active' ORDER BY nama_cabang");
    while ($row = $res->fetch_assoc()) {
        println(" - ID=".$row['cabang_id']." | ".$row['nama_cabang']);
    }
    println('Gunakan: cabang_id=<ID> qty=<jumlah> user_id=<user>');
    exit(0);
}

$cabangId = isset($args['cabang_id']) ? (int)$args['cabang_id'] : 0;
$qtyDefault = isset($args['qty']) ? (int)$args['qty'] : 100;
$userId = isset($args['user_id']) ? (int)$args['user_id'] : 1;

if ($cabangId <= 0) {
    println('ERROR: Harap berikan cabang_id. Contoh: cabang_id=1');
    println('Tips: tambahkan list_cabang=1 untuk melihat daftar cabang');
    exit(1);
}

// Check cabang exists
$stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id=? AND status='active'");
$stmt->bind_param('i', $cabangId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    println('ERROR: cabang_id tidak ditemukan atau inactive');
    exit(1);
}
$namaCabang = $res->fetch_assoc()['nama_cabang'];
println("Seeding inventory untuk cabang [$cabangId] $namaCabang ...");

// Detect status_approval column
$hasApproval = false;
$colRes = $conn->query("SHOW COLUMNS FROM inventory LIKE 'status_approval'");
if ($colRes && $colRes->num_rows > 0) { $hasApproval = true; }

$today = date('Y-m-d');
$refPrefix = 'SEED-' . date('Ymd');

// Loop active products
$prodRes = $conn->query("SELECT produk_id, nama_produk FROM produk WHERE status='active' ORDER BY produk_id");
$total = 0; $skipped = 0; $inserted = 0;
while ($prod = $prodRes->fetch_assoc()) {
    $total++;
    $produkId = (int)$prod['produk_id'];
    $jumlah = $qtyDefault;

    // Skip if already seeded for today
    $checkSql = "SELECT inventory_id FROM inventory WHERE produk_id=? AND cabang_id=? AND referensi=? LIMIT 1";
    $ref = $refPrefix;
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param('iis', $produkId, $cabangId, $ref);
    $stmt->execute();
    $exists = $stmt->get_result();
    if ($exists && $exists->num_rows > 0) { $skipped++; continue; }

    // Compute stok_sebelum for this product & cabang
    $sumSql = "SELECT COALESCE(SUM(CASE WHEN tipe_transaksi='masuk' THEN jumlah ELSE -jumlah END),0) AS total FROM inventory WHERE produk_id=? AND cabang_id=?";
    $stmt = $conn->prepare($sumSql);
    $stmt->bind_param('ii', $produkId, $cabangId);
    $stmt->execute();
    $sumRes = $stmt->get_result();
    $stokSebelum = 0;
    if ($sum = $sumRes->fetch_assoc()) { $stokSebelum = (int)$sum['total']; }
    $stokSesudah = $stokSebelum + $jumlah;

    // Build insert
    if ($hasApproval) {
        $ins = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id, cabang_id, status_approval) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $status = 'approved';
        $ket = 'Seed initial stock';
        $tipe = 'masuk';
        $ins->bind_param('issiiisssis', $produkId, $today, $tipe, $jumlah, $stokSebelum, $stokSesudah, $ref, $ket, $userId, $cabangId, $status);
    } else {
        $ins = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id, cabang_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $ket = 'Seed initial stock';
        $tipe = 'masuk';
        $ins->bind_param('issiiisssi', $produkId, $today, $tipe, $jumlah, $stokSebelum, $stokSesudah, $ref, $ket, $userId, $cabangId);
    }

    if ($ins->execute()) { $inserted++; }
}

println("Selesai. Produk diproses: $total | inserted: $inserted | skipped (sudah ada): $skipped");
println('Silakan refresh halaman Stock Terupdate Per Cabang.');
