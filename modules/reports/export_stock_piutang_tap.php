<?php
require_once __DIR__ . '/../../config/config.php';
$mysqli = getDBConnection();

$scope = isset($_GET['scope']) ? $_GET['scope'] : 'summary';
$cabangId = isset($_GET['cabang_id']) ? (int)$_GET['cabang_id'] : 0;

function output_csv_header($filename) {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
}

function csv_line($fh, $fields) {
  fputcsv($fh, $fields);
}

if ($scope === 'detail' && $cabangId > 0) {
  // Export per-produk detail for selected cabang
  // Get cabang name
  $stmtCab = $mysqli->prepare('SELECT nama_cabang FROM cabang WHERE cabang_id = ?');
  $stmtCab->bind_param('i', $cabangId);
  $stmtCab->execute();
  $stmtCab->bind_result($namaCabang);
  $stmtCab->fetch();
  $stmtCab->close();

  $filename = 'stock_piutang_tap_detail_cabang_' . ($namaCabang ? preg_replace('/\s+/', '_', $namaCabang) : $cabangId) . '.csv';
  output_csv_header($filename);
  $fh = fopen('php://output', 'w');

  csv_line($fh, ['Cabang', $namaCabang ?: $cabangId]);
  csv_line($fh, []);
  csv_line($fh, ['Produk', 'Kategori', 'Qty Stok', 'Nominal Stok (Qty×Harga Jual)']);

  $stmt = $mysqli->prepare(
    "SELECT p.nama_produk, p.kategori,
            COALESCE(SUM(CASE 
              WHEN i.tipe_transaksi='masuk' THEN i.jumlah
              WHEN i.tipe_transaksi='keluar' THEN -i.jumlah
              WHEN i.tipe_transaksi='adjustment' THEN i.jumlah
              WHEN i.tipe_transaksi='return' THEN i.jumlah
              ELSE 0 END),0) AS qty_total,
            COALESCE(SUM(CASE 
              WHEN i.tipe_transaksi='masuk' THEN i.jumlah * p.harga
              WHEN i.tipe_transaksi='keluar' THEN -i.jumlah * p.harga
              WHEN i.tipe_transaksi='adjustment' THEN i.jumlah * p.harga
              WHEN i.tipe_transaksi='return' THEN i.jumlah * p.harga
              ELSE 0 END),0) AS nominal_stok
     FROM produk p
     LEFT JOIN inventory i ON i.produk_id = p.produk_id AND i.cabang_id = ?
     WHERE p.status='active'
     GROUP BY p.produk_id, p.nama_produk, p.kategori
     ORDER BY p.kategori, p.nama_produk"
  );
  $stmt->bind_param('i', $cabangId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) {
    // Include all rows (even qty 0) for audit completeness
    csv_line($fh, [
      $r['nama_produk'],
      $r['kategori'],
      (int)$r['qty_total'],
      (float)$r['nominal_stok'],
    ]);
  }
  $stmt->close();
  fclose($fh);
  exit;
} else {
  // Export per-cabang summary
  output_csv_header('stock_piutang_tap_per_cabang.csv');
  $fh = fopen('php://output', 'w');

  csv_line($fh, ['Cabang', 'Qty Stok', 'Nominal Stok (Qty×Harga Jual)', 'Piutang Penjualan', 'Total TAP']);

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
    // Get piutang per cabang
    $stmt2 = $mysqli->prepare("SELECT COALESCE(SUM(pj.total), 0) AS total_piutang FROM penjualan pj JOIN reseller r ON pj.reseller_id = r.reseller_id WHERE r.cabang_id = ? AND pj.status_pembayaran IN ('pending','top')");
    $stmt2->bind_param('i', $row['cabang_id']);
    $stmt2->execute();
    $stmt2->bind_result($piutang);
    $stmt2->fetch();
    $stmt2->close();

    $totalTap = (float)$row['nominal_stok'] + (float)$piutang;
    csv_line($fh, [
      $row['nama_cabang'],
      (int)$row['qty_total'],
      (float)$row['nominal_stok'],
      (float)$piutang,
      (float)$totalTap,
    ]);
  }
  $res->close();

  fclose($fh);
  exit;
}
