<?php
require_once __DIR__ . '/../../config/config.php';
$user = getCurrentUser();
$mysqli = getDBConnection();

function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}
function format_rp($n){return 'Rp ' . number_format((float)$n, 0, ',', '.');}
function format_qty($q){return number_format((int)$q, 0, ',', '.');}

// Compute stock per cabang (qty and nominal by harga jual) and piutang outstanding per cabang
$rows = [];
$sqlStock = "
    SELECT 
        c.cabang_id,
        c.nama_cabang,
        COALESCE(SUM(
            CASE 
                WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
                WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah 
                WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah 
                WHEN i.tipe_transaksi = 'return' THEN i.jumlah 
                ELSE 0
            END
        ), 0) AS qty_total,
        COALESCE(SUM(
            CASE 
                WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
                WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah 
                WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah 
                WHEN i.tipe_transaksi = 'return' THEN i.jumlah 
                ELSE 0
            END * p.harga
        ), 0) AS nominal_stok
    FROM cabang c
    LEFT JOIN inventory i ON c.cabang_id = i.cabang_id
    LEFT JOIN produk p ON i.produk_id = p.produk_id AND p.status = 'active'
    WHERE c.status = 'active'
    GROUP BY c.cabang_id, c.nama_cabang
    ORDER BY c.nama_cabang
";

$res = $mysqli->query($sqlStock);
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

// Fetch outstanding piutang per cabang and merge
for ($i=0; $i<count($rows); $i++) {
    $cabangId = (int)$rows[$i]['cabang_id'];
    $stmt = $mysqli->prepare("SELECT COALESCE(SUM(pj.total), 0) AS total_piutang FROM penjualan pj JOIN reseller r ON pj.reseller_id = r.reseller_id WHERE r.cabang_id = ? AND pj.status_pembayaran IN ('pending','top')");
    $stmt->bind_param('i', $cabangId);
    $stmt->execute();
    $stmt->bind_result($piutang);
    $stmt->fetch();
    $stmt->close();
    $rows[$i]['piutang_penjualan'] = (float)$piutang;
    $rows[$i]['total_tap'] = (float)$rows[$i]['nominal_stok'] + (float)$rows[$i]['piutang_penjualan'];
}

?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock Piutang TAP per Cabang</title>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/admin-styles.css">
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/reports.css">
</head>
<body class="admin-page">
  <div class="admin-container">
    <aside class="admin-sidebar">
      <div class="sidebar-header">
        <h2>Reports</h2>
        <p><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></p>
        <small style="opacity:0.8;font-size:12px;">Stock & Piutang TAP</small>
      </div>
      <nav class="sidebar-nav">
        <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span><span>Index Reports</span></a>
        <a href="report_piutang_sa.php" class="nav-item"><span class="nav-icon">🧾</span><span>Report Piutang SA</span></a>
        <a href="report_piutang_voucher.php" class="nav-item"><span class="nav-icon">🎟️</span><span>Report Piutang Voucher</span></a>
        <a href="report_stock_piutang_tap.php" class="nav-item active"><span class="nav-icon">🏢</span><span>Stock Piutang TAP</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="<?php echo BASE_PATH; ?>/modules/finance/laporan_setoran_global.php" class="btn-back">← Kembali ke Laporan Global</a>
        <a href="<?php echo BASE_PATH; ?>/logout" class="btn-logout">Logout</a>
      </div>
    </aside>

    <main style="flex:1;margin-left:340px;padding:24px;">
      <h2>Stock Piutang TAP per Cabang</h2>
      <div class="card-summary">
        <?php 
          $sumQty = array_sum(array_map(function($r){ return (int)$r['qty_total']; }, $rows));
          $sumStok = array_sum(array_map(function($r){ return (float)$r['nominal_stok']; }, $rows));
          $sumPiutang = array_sum(array_map(function($r){ return (float)$r['piutang_penjualan']; }, $rows));
          $sumTotal = array_sum(array_map(function($r){ return (float)$r['total_tap']; }, $rows));
        ?>
        <div class="pill">Total Qty Stok: <?php echo format_qty($sumQty); ?></div>
        <div class="pill">Nominal Stok: <?php echo format_rp($sumStok); ?></div>
        <div class="pill">Piutang Penjualan: <?php echo format_rp($sumPiutang); ?></div>
        <div class="pill">Total TAP: <?php echo format_rp($sumTotal); ?></div>
      </div>

      <table class="table" cellspacing="0" cellpadding="6" style="margin-top:16px;">
        <thead>
          <tr>
            <th>Cabang</th>
            <th>Qty Stok</th>
            <th>Nominal Stok (Qty × Harga Jual)</th>
            <th>Piutang Penjualan</th>
            <th>Total TAP</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo h($r['nama_cabang']); ?></td>
              <td><?php echo format_qty($r['qty_total']); ?></td>
              <td><?php echo format_rp($r['nominal_stok']); ?></td>
              <td><?php echo format_rp($r['piutang_penjualan']); ?></td>
              <td><?php echo format_rp($r['total_tap']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </main>
  </div>
</body>
</html>
