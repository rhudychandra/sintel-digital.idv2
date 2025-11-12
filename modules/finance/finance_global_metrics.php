<?php
/**
 * Finance Global Metrics Helper
 * Computes real-time stock & piutang data for Laporan Setoran Global
 */

require_once __DIR__ . '/../../config/config.php';

/**
 * Get all global metrics for the dashboard
 * Returns array of groups with calculated qty & nominal
 */
function getGlobalMetrics($conn) {
    $metrics = [
        'linkaja_finpay' => [],
        'perdana_vf_segel' => [],
        'perdana_internet' => [],
        'voucher_internet' => [],
        'piutang_kantor_pusat' => 0,
        'stock_tap_per_cabang' => [],
        'top_payment' => 0,
        'last_updated' => date('Y-m-d H:i:s')
    ];

    // 1. SALDO LINKAJA & FINPAY
    // Get stock nominal from produk with kategori LinkAja, Finpay
    $stmt = $conn->prepare("
        SELECT p.nama_produk, p.stok, p.harga, (p.stok * p.harga) as nominal
        FROM produk p
        WHERE p.kategori IN ('LinkAja', 'Finpay') AND p.status = 'active'
        ORDER BY p.kategori, p.nama_produk
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $metrics['linkaja_finpay'][] = [
            'nama' => $row['nama_produk'],
            'qty' => (int)$row['stok'],
            'nominal' => (float)$row['nominal']
        ];
    }
    $stmt->close();

    // 2. PERDANA & VF SEGEL
    // Kategori: Perdana Segel Red 0K, Perdana Segel ByU 0K, Voucher Segel Lite, Voucher Segel ByU
    $stmt = $conn->prepare("
        SELECT p.nama_produk, p.stok, p.harga, (p.stok * p.harga) as nominal
        FROM produk p
        WHERE p.kategori IN ('Perdana Segel Red 0K', 'Perdana Segel ByU 0K', 'Voucher Segel Lite', 'Voucher Segel ByU')
          AND p.status = 'active'
        ORDER BY p.kategori, p.nama_produk
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $metrics['perdana_vf_segel'][] = [
            'nama' => $row['nama_produk'],
            'qty' => (int)$row['stok'],
            'nominal' => (float)$row['nominal']
        ];
    }
    $stmt->close();

    // 3. PERDANA INTERNET (Lite & ByU) - NEW CATEGORY
    $stmt = $conn->prepare("
        SELECT p.nama_produk, p.stok, p.harga, (p.stok * p.harga) as nominal
        FROM produk p
        WHERE p.kategori IN ('Perdana Internet Lite', 'Perdana Internet ByU')
          AND p.status = 'active'
        ORDER BY p.kategori, p.nama_produk
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $metrics['perdana_internet'][] = [
            'nama' => $row['nama_produk'],
            'qty' => (int)$row['stok'],
            'nominal' => (float)$row['nominal']
        ];
    }
    $stmt->close();

    // 4. VOUCHER INTERNET (Lite & ByU)
    $stmt = $conn->prepare("
        SELECT p.nama_produk, p.stok, p.harga, (p.stok * p.harga) as nominal
        FROM produk p
        WHERE p.kategori IN ('Voucher Internet Lite', 'Voucher Internet ByU')
          AND p.status = 'active'
        ORDER BY p.kategori, p.nama_produk
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $metrics['voucher_internet'][] = [
            'nama' => $row['nama_produk'],
            'qty' => (int)$row['stok'],
            'nominal' => (float)$row['nominal']
        ];
    }
    $stmt->close();

    // 5. PIUTANG KANTOR PUSAT
    // TODO: Nanti akan diambil dari halaman Report Piutang (belum implement)
    // Sementara placeholder = 0 atau bisa pakai rumus sederhana
    // Rumus sementara: Total stok global + penjualan pending/top
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(stok * harga), 0) as total_stok_nominal
        FROM produk
        WHERE status = 'active'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stok_nominal = (float)$row['total_stok_nominal'];
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as total_piutang_penjualan
        FROM penjualan
        WHERE status_pembayaran IN ('pending', 'top')
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $penjualan_outstanding = (float)$row['total_piutang_penjualan'];
    $stmt->close();

    // TEMPORARY: Pakai rumus sederhana dulu, nanti replace dengan Report Piutang
    $metrics['piutang_kantor_pusat'] = $stok_nominal + $penjualan_outstanding;

    // 6. STOCK TAP (per cabang) - FROM INVENTORY (sum of all transactions per cabang)
    // Menghitung stok per cabang dengan menjumlahkan semua transaksi masuk-keluar
    // Sama seperti logic di inventory_stock.php
    // IMPORTANT: Pastikan semua inventory record punya cabang_id yang valid
    // Handle all transaction types: masuk (+), keluar (-), adjustment (+/-), return (+)
    $stmt = $conn->prepare("
        SELECT 
            c.nama_cabang,
            c.cabang_id,
            COALESCE(SUM(
                CASE 
                    WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah 
                    WHEN i.tipe_transaksi = 'keluar' THEN -i.jumlah
                    WHEN i.tipe_transaksi = 'adjustment' THEN i.jumlah
                    WHEN i.tipe_transaksi = 'return' THEN i.jumlah
                    ELSE 0
                END * p.harga
            ), 0) as stok_nominal
        FROM cabang c
        LEFT JOIN inventory i ON c.cabang_id = i.cabang_id
        LEFT JOIN produk p ON i.produk_id = p.produk_id AND p.status = 'active'
        WHERE c.status = 'active'
        GROUP BY c.cabang_id, c.nama_cabang
        ORDER BY c.nama_cabang
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Also add pending/top penjualan per cabang via reseller
        $piutang_penjualan = 0;
        $stmt2 = $conn->prepare("
            SELECT COALESCE(SUM(pj.total), 0) as total_piutang
            FROM penjualan pj
            JOIN reseller r ON pj.reseller_id = r.reseller_id
            WHERE r.cabang_id = ? AND pj.status_pembayaran IN ('pending','top')
        ");
        $stmt2->bind_param("i", $row['cabang_id']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($row2 = $res2->fetch_assoc()) {
            $piutang_penjualan = (float)$row2['total_piutang'];
        }
        $stmt2->close();
        
        $metrics['stock_tap_per_cabang'][] = [
            'cabang' => $row['nama_cabang'],
            'nominal' => (float)$row['stok_nominal'] + $piutang_penjualan
        ];
    }
    $stmt->close();

    // 6. TOP (Term Off Payment) - total nominal transaksi dengan status TOP yang belum paid
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as total_top
        FROM penjualan
        WHERE status_pembayaran = 'top'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $metrics['top_payment'] = (float)$row['total_top'];
    $stmt->close();

    return $metrics;
}
