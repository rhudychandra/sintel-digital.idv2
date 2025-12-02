<?php
require_once __DIR__ . '/../../config/config.php';
$user = getCurrentUser();
// Dapatkan koneksi database menggunakan helper dari config
$mysqli = getDBConnection();

function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}

function indoMonthName($monthNumber){
  $names = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
  ];
  return $names[(int)$monthNumber] ?? '';
}

function formatIndoMonthYear(DateTime $dt){
  return indoMonthName((int)$dt->format('n')).' '.$dt->format('Y');
}

// Inputs
// Month/year inputs
$defaultMonth = date('m');
$defaultYear = date('Y');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)$defaultYear;
$month = isset($_GET['month']) ? $_GET['month'] : ($defaultYear.'-'.$defaultMonth); // format YYYY-MM
list($y, $m) = explode('-', $month);
// Override year if dropdown provided
if ($selectedYear && (int)$y !== $selectedYear) { $y = (string)$selectedYear; }
$startDate = sprintf('%04d-%02d-01', (int)$y, (int)$m);
$endDate = date('Y-m-t', strtotime($startDate));

// Warehouse cabang IDs (LLG=44, LHT=45)
$warehouseIds = [44,45];
$warehouseIn = implode(',', array_map('intval', $warehouseIds));

// Summary totals
// Piutang SA: sum pengajuan_stock.total_saldo for rs_type='sa' within month
$summary = [
  'total_piutang' => 0.0,
  'total_paid' => 0.0,
  'sisa_piutang' => 0.0,
];

// total piutang dari pengajuan SA bulan ini
$q1 = $mysqli->prepare("SELECT COALESCE(SUM(total_saldo),0) FROM pengajuan_stock WHERE rs_type='sa' AND tanggal BETWEEN ? AND ?");
$q1->bind_param('ss', $startDate, $endDate);
$q1->execute();
$q1->bind_result($totalPiutang);
$q1->fetch();
$q1->close();
$summary['total_piutang'] = (float)$totalPiutang;

// total paid dari laporan harian TAP pada bulan ini (setoran_harian)
$q2 = $mysqli->prepare("SELECT COALESCE(SUM(total_setoran),0) FROM setoran_harian WHERE tanggal BETWEEN ? AND ?");
$q2->bind_param('ss', $startDate, $endDate);
$q2->execute();
$q2->bind_result($totalPaid);
$q2->fetch();
$q2->close();
$summary['total_paid'] = (float)$totalPaid;
$summary['sisa_piutang'] = max(0, $summary['total_piutang'] - $summary['total_paid']);

// Left table: from inventory stock masuk (only warehouses) UNION pengajuan SA/VF in month
// Inventory masuk
$inventoryRows = [];
$sqlInv = "SELECT i.tanggal, p.nama_produk, i.jumlah AS qty, p.hpp_saldo AS hpp, (i.jumlah * p.hpp_saldo) AS total, p.kategori, c.nama_cabang, 'Stock Masuk' AS jenis
           FROM inventory i
           JOIN produk p ON p.produk_id = i.produk_id
           JOIN cabang c ON c.cabang_id = i.cabang_id
           WHERE i.tipe_transaksi='masuk'
             AND i.cabang_id IN ($warehouseIn)
             AND i.tanggal BETWEEN ? AND ?
             AND p.nama_produk = 'Preload 3GB 30 Hari'
             AND EXISTS (SELECT 1 FROM users u WHERE u.user_id = i.user_id AND u.role = 'finance')";
$stmtInv = $mysqli->prepare($sqlInv);
$stmtInv->bind_param('ss', $startDate, $endDate);
$stmtInv->execute();
$resInv = $stmtInv->get_result();
while ($row = $resInv->fetch_assoc()) { $inventoryRows[] = $row; }
$stmtInv->close();

// Pengajuan SA/VF
$pengajuanRows = [];
$sqlPeng = "SELECT ps.tanggal, p.nama_produk, psi.qty, psi.harga AS hpp, (psi.qty * psi.harga) AS total, p.kategori, c.nama_cabang, CONCAT('Pengajuan ', UPPER(ps.rs_type)) AS jenis
            FROM pengajuan_stock ps
            JOIN pengajuan_stock_items psi ON psi.pengajuan_id = ps.id
            JOIN produk p ON p.produk_id = psi.produk_id
            JOIN cabang c ON c.cabang_id = ps.warehouse_id
            JOIN outlet o ON o.outlet_id = ps.outlet_id
            WHERE ps.tanggal BETWEEN ? AND ?
              AND ps.rs_type = 'sa'
              AND o.jenis_rs = 'RS Eksekusi SA'";
$stmtPeng = $mysqli->prepare($sqlPeng);
$stmtPeng->bind_param('ss', $startDate, $endDate);
$stmtPeng->execute();
$resPeng = $stmtPeng->get_result();
while ($row = $resPeng->fetch_assoc()) { $pengajuanRows[] = $row; }
$stmtPeng->close();

$leftRows = array_merge($inventoryRows, $pengajuanRows);

// Right table: paid detail from laporan harian TAP (setoran_harian)
$paidRows = [];
$sqlPaid = "SELECT sh.tanggal, sh.produk AS nama_produk, sh.qty, sh.harga_satuan AS hpp,
                    (sh.qty*sh.harga_satuan) AS total, p2.kategori, sh.cabang AS nama_cabang, sh.reseller AS jenis
            FROM setoran_harian sh
            JOIN produk p2 ON p2.nama_produk = sh.produk
            WHERE sh.tanggal BETWEEN ? AND ?
              AND p2.kategori IN ('Perdana Internet Lite','Perdana Internet ByU')";
$stmtPaid = $mysqli->prepare($sqlPaid);
$stmtPaid->bind_param('ss', $startDate, $endDate);
$stmtPaid->execute();
$resPaid = $stmtPaid->get_result();
while ($row = $resPaid->fetch_assoc()) { $paidRows[] = $row; }
$stmtPaid->close();

// Rendering dengan layout admin
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Piutang SA</title>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/admin-styles.css">
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/reports.css">
</head>
<body class="admin-page">
  <div class="admin-container">
    <aside class="admin-sidebar">
      <div class="sidebar-header">
        <h2>Reports</h2>
        <p><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></p>
        <small style="opacity:0.8;font-size:12px;">Report & Piutang</small>
      </div>
      <nav class="sidebar-nav">
        <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span><span>Index Reports</span></a>
        <a href="report_piutang_sa.php" class="nav-item active"><span class="nav-icon">🧾</span><span>Report Piutang SA</span></a>
        <a href="report_piutang_voucher.php" class="nav-item"><span class="nav-icon">🎟️</span><span>Report Piutang Voucher</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="<?php echo BASE_PATH; ?>/dashboard" class="btn-back">← Kembali ke Dashboard</a>
        <a href="<?php echo BASE_PATH; ?>/logout" class="btn-logout">Logout</a>
      </div>
    </aside>

    <main style="flex:1;margin-left:340px;padding:24px;">
      <h2>Report Piutang Perdana SA</h2>
      <div class="main-controls">
        <form method="get">
          <?php
            // Build dropdown of last 12 months including current
            $options = [];
            $cur = new DateTime($startDate);
            for ($i=0; $i<12; $i++) {
              $optMonth = $cur->format('Y-m');
              $label = formatIndoMonthYear($cur);
              $selected = ($optMonth === $month) ? 'selected' : '';
              $options[] = "<option value='".h($optMonth)."' $selected>".h($label)."</option>";
              $cur->modify('-1 month');
            }
          ?>
          <label>Bulan:
            <select name="month">
              <?php echo implode('', $options); ?>
            </select>
          </label>
          <?php
            // Year dropdown: current year and previous 4 years
            $yearOpts = [];
            for ($yy = (int)date('Y'); $yy >= (int)date('Y')-4; $yy--) {
              $sel = ($yy === $selectedYear) ? 'selected' : '';
              $yearOpts[] = "<option value='".$yy."' $sel>".$yy."</option>";
            }
          ?>
          <label>Tahun:
            <select name="year">
              <?php echo implode('', $yearOpts); ?>
            </select>
          </label>
          <button type="submit">Terapkan</button>
        </form>
        <div class="card-summary">
          <div class="pill">Total Piutang: <?php echo number_format($summary['total_piutang'],0,',','.'); ?></div>
          <div class="pill">Paid: <?php echo number_format($summary['total_paid'],0,',','.'); ?></div>
          <div class="pill">Sisa Piutang: <?php echo number_format($summary['sisa_piutang'],0,',','.'); ?></div>
        </div>
      </div>

      <div style="display:flex; gap:24px; margin-top:16px;">
        <div style="flex:1;">
          <?php $headingDate = new DateTime($startDate); ?>
          <h3>Piutang <?php echo h(formatIndoMonthYear($headingDate)); ?></h3>
          <table class="table" cellspacing="0" cellpadding="6">
            <thead>
              <tr>
                <th>Tanggal</th><th>Saldo</th><th>Qty</th><th>HPP</th><th>Total (Saldo×HPP)</th><th>Kategori</th><th>Cabang</th><th>Jenis Piutang</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($leftRows as $r): ?>
                <?php
                  $tanggal = h($r['tanggal']);
                  $saldo = h($r['nama_produk']);
                  $qty = (int)$r['qty'];
                  $hpp = (float)$r['hpp'];
                  $total = (float)$r['total'];
                  $kategori = h($r['kategori']);
                  $cabang = h($r['nama_cabang']);
                  $jenis = h($r['jenis']);
                ?>
                <tr>
                  <td><?php echo $tanggal; ?></td>
                  <td><?php echo $saldo; ?></td>
                  <td><?php echo number_format($qty,0,',','.'); ?></td>
                  <td><?php echo number_format($hpp,2,',','.'); ?></td>
                  <td><?php echo number_format($total,2,',','.'); ?></td>
                  <td><?php echo $kategori; ?></td>
                  <td><?php echo $cabang; ?></td>
                  <td><?php echo $jenis; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="flex:1;">
          <h3>Paid Detail <?php echo h(formatIndoMonthYear($headingDate)); ?></h3>
          <table class="table" cellspacing="0" cellpadding="6">
            <thead>
              <tr>
                <th>Tanggal</th><th>Saldo</th><th>Qty</th><th>HPP</th><th>Total (Saldo×HPP)</th><th>Kategori</th><th>Cabang</th><th>Jenis Piutang</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paidRows as $r): ?>
                <?php
                  $tanggal = h($r['tanggal']);
                  $saldo = h($r['nama_produk']);
                  $qty = (int)$r['qty'];
                  $hpp = (float)$r['hpp'];
                  $total = (float)$r['total'];
                  $kategori = h($r['kategori'] ?? '');
                  $cabang = h($r['nama_cabang']);
                  $jenis = h($r['jenis']);
                ?>
                <tr>
                  <td><?php echo $tanggal; ?></td>
                  <td><?php echo $saldo; ?></td>
                  <td><?php echo number_format($qty,0,',','.'); ?></td>
                  <td><?php echo number_format($hpp,2,',','.'); ?></td>
                  <td><?php echo number_format($total,2,',','.'); ?></td>
                  <td><?php echo $kategori; ?></td>
                  <td><?php echo $cabang; ?></td>
                  <td><?php echo $jenis; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</body>
</html>
