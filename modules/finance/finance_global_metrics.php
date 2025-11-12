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

    // 3. VOUCHER INTERNET (Lite & ByU)
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

    // 4. PIUTANG KANTOR PUSAT
    // = Total stok global (semua produk aktif) + penjualan pending/top
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

    $metrics['piutang_kantor_pusat'] = $stok_nominal + $penjualan_outstanding;

    // 5. STOCK TAP (per cabang) - FROM INVENTORY STOCK INFORMATION
    // Get stock per cabang from inventory table (latest stock per produk per cabang)
    // Query: Get latest stok_sesudah from inventory grouped by cabang
    $stmt = $conn->prepare("
        SELECT 
            c.nama_cabang,
            c.cabang_id,
            COALESCE(SUM(latest_inv.stok_sesudah * p.harga), 0) as stok_nominal
        FROM cabang c
        LEFT JOIN (
            SELECT 
                i.produk_id,
                i.stok_sesudah,
                p2.cabang_id,
                i.created_at,
                ROW_NUMBER() OVER (PARTITION BY i.produk_id, p2.cabang_id ORDER BY i.created_at DESC) as rn
            FROM inventory i
            JOIN produk p2 ON i.produk_id = p2.produk_id
            WHERE p2.cabang_id IS NOT NULL
        ) latest_inv ON latest_inv.cabang_id = c.cabang_id AND latest_inv.rn = 1
        LEFT JOIN produk p ON p.produk_id = latest_inv.produk_id AND p.status = 'active'
        WHERE c.status = 'active'
        GROUP BY c.cabang_id, c.nama_cabang
        ORDER BY c.nama_cabang
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Also add pending/top penjualan per cabang if penjualan has cabang_id
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
