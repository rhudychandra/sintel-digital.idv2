<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Get current page/menu
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Handle form submissions
$message = '';
$error = '';

// Handle Stock Keluar (Pengeluaran Stok Non-Penjualan) - WITH APPROVAL SYSTEM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'stock_keluar') {
    $tanggal = $_POST['tanggal'];
    $produk_id = $_POST['produk_id'];
    $qty = $_POST['qty'];
    $alasan = $_POST['alasan'];
    $keterangan = $_POST['keterangan'] ?? '';
    
    // Get cabang_id based on user role
    // Admin, Staff, Supervisor, Finance: use their own branch
    // Administrator, Manager: can select any branch
    if (in_array($user['role'], ['admin', 'staff', 'supervisor', 'finance'])) {
        $cabang_id = $user['cabang_id'];
    } else {
        $cabang_id = $_POST['cabang_id'] ?? null;
    }
    
    // Get current stock
    $stmt = $conn->prepare("SELECT nama_produk, stok FROM produk WHERE produk_id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Produk tidak ditemukan!";
    } else {
        $produk = $result->fetch_assoc();
        $stok_sebelum = $produk['stok'];
        
        if ($stok_sebelum < $qty) {
            $error = "Stok tidak mencukupi! Stok tersedia: " . $stok_sebelum;
        } else {
            $stok_sesudah = $stok_sebelum - $qty; // Calculated but not applied yet
            
            // Generate reference number
            $referensi = 'KELUAR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Build keterangan
            $full_keterangan = "Stock Keluar - Alasan: " . $alasan;
            if (!empty($keterangan)) {
                $full_keterangan .= " | " . $keterangan;
            }
            
            // Insert inventory record WITH status_approval = 'pending'
            // Stock will be updated when approved
            if ($cabang_id) {
                $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id, cabang_id) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, 'pending', ?, ?)");
                $stmt->bind_param("isiiissii", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id'], $cabang_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, 'pending', ?)");
                $stmt->bind_param("isiiissi", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id']);
            }
            
            if ($stmt->execute()) {
                $message = "Stock keluar berhasil diajukan! Ref: " . $referensi . " | Produk: " . $produk['nama_produk'] . " | Qty: " . $qty . " | Status: PENDING (Menunggu Approval)";
            } else {
                $error = "Gagal mengajukan stock keluar: " . $conn->error;
            }
        }
    }
}

// Handle Input Barang (Stok Masuk) - UPDATED WITH CABANG & ALASAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'input_barang') {
    if (!in_array($user['role'], ['administrator', 'manager', 'finance'])) {
        $error = "Akses ditolak: hanya Administrator, Manager, dan Finance yang dapat input barang.";
    } else {
    $tanggal = $_POST['tanggal'];
    $produk_id = $_POST['produk_id'];
    $qty = $_POST['qty'];
    $alasan_masuk = $_POST['alasan_masuk'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    
    // Get cabang_id based on user role
    // Admin, Staff, Supervisor, Finance: use their own branch
    // Administrator, Manager: can select any branch
    if (in_array($user['role'], ['admin', 'staff', 'supervisor', 'finance'])) {
        $cabang_id = $user['cabang_id'];
    } else {
        $cabang_id = $_POST['cabang_id'] ?? null;
    }
    
    // Get current stock
    $stmt = $conn->prepare("SELECT nama_produk, stok FROM produk WHERE produk_id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();
    $stok_sebelum = $produk['stok'];
    $stok_sesudah = $stok_sebelum + $qty; // Calculated but not applied yet
    
    // Build full keterangan with alasan
    $full_keterangan = "Stock Masuk - Alasan: " . $alasan_masuk;
    if (!empty($keterangan)) {
        $full_keterangan .= " | " . $keterangan;
    }
    
    // Generate reference number
    $referensi = 'MASUK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert inventory record WITH status_approval = 'pending'
    // Stock will be updated when approved
    if ($cabang_id) {
        $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id, cabang_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->bind_param("isiiissii", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id'], $cabang_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("isiiissi", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id']);
    }
    
    if ($stmt->execute()) {
        $message = "Stock masuk berhasil diajukan! Ref: " . $referensi . " | Produk: " . $produk['nama_produk'] . " | Qty: " . $qty . " | Status: PENDING (Menunggu Approval)";
    } else {
        $error = "Gagal mengajukan stock masuk: " . $conn->error;
    }
    }
}

// Handle Input Penjualan (Multiple Products) - NEW
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'input_penjualan') {
    $tanggal = $_POST['tanggal_penjualan'];
    $reseller_id = $_POST['reseller_id'];
    $produk_ids = $_POST['produk_id'] ?? [];
    $quantities = $_POST['qty'] ?? [];
    
    if (empty($produk_ids) || empty($quantities)) {
        $error = "Minimal harus ada 1 produk!";
    } else {
        $valid = true;
        $products_data = [];
        $subtotal_all = 0;
        
        foreach ($produk_ids as $index => $produk_id) {
            if (empty($produk_id) || empty($quantities[$index]) || $quantities[$index] <= 0) continue;
            
            $qty = $quantities[$index];
            $stmt = $conn->prepare("SELECT nama_produk, harga, stok FROM produk WHERE produk_id = ?");
            $stmt->bind_param("i", $produk_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "Produk tidak ditemukan!";
                $valid = false;
                break;
            }
            
            $produk = $result->fetch_assoc();
            
            if ($produk['stok'] < $qty) {
                $error = "Stok tidak mencukupi untuk " . $produk['nama_produk'] . "! Stok tersedia: " . $produk['stok'];
                $valid = false;
                break;
            }
            
            $subtotal = $produk['harga'] * $qty;
            $subtotal_all += $subtotal;
            
            $products_data[] = [
                'produk_id' => $produk_id,
                'nama_produk' => $produk['nama_produk'],
                'harga' => $produk['harga'],
                'qty' => $qty,
                'subtotal' => $subtotal,
                'stok_sebelum' => $produk['stok']
            ];
        }
        
        if (empty($products_data)) {
            $error = "Minimal harus ada 1 produk yang diisi!";
            $valid = false;
        }
        
        if ($valid && !empty($products_data)) {
            $no_invoice = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("SELECT nama_reseller FROM reseller WHERE reseller_id = ?");
            $stmt->bind_param("i", $reseller_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $reseller = $result->fetch_assoc();
            
            $stmt = $conn->prepare("SELECT pelanggan_id FROM pelanggan WHERE nama_pelanggan = ?");
            $stmt->bind_param("s", $reseller['nama_reseller']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $pelanggan = $result->fetch_assoc();
                $pelanggan_id = $pelanggan['pelanggan_id'];
            } else {
                $kode_pelanggan = 'CUST-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $stmt = $conn->prepare("INSERT INTO pelanggan (kode_pelanggan, nama_pelanggan, phone, tipe_pelanggan) VALUES (?, ?, '000000000', 'corporate')");
                $stmt->bind_param("ss", $kode_pelanggan, $reseller['nama_reseller']);
                $stmt->execute();
                $pelanggan_id = $conn->insert_id;
            }
            
            $metode_pembayaran = $_POST['metode_pembayaran'];
            $status_pembayaran = $_POST['status_pembayaran'];
            
            // Get cabang_id from reseller
            $stmt_cabang = $conn->prepare("SELECT cabang_id FROM reseller WHERE reseller_id = ?");
            $stmt_cabang->bind_param("i", $reseller_id);
            $stmt_cabang->execute();
            $result_cabang = $stmt_cabang->get_result();
            $reseller_cabang = $result_cabang->fetch_assoc();
            $cabang_id_penjualan = $reseller_cabang['cabang_id'] ?? $user['cabang_id'] ?? 1;
            
            $stmt = $conn->prepare("INSERT INTO penjualan (no_invoice, tanggal_penjualan, pelanggan_id, user_id, reseller_id, cabang_id, subtotal, total, metode_pembayaran, status_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiidddss", $no_invoice, $tanggal, $pelanggan_id, $user['user_id'], $reseller_id, $cabang_id_penjualan, $subtotal_all, $subtotal_all, $metode_pembayaran, $status_pembayaran);
            $stmt->execute();
            $penjualan_id = $conn->insert_id;
            
            foreach ($products_data as $prod) {
                $stmt = $conn->prepare("INSERT INTO detail_penjualan (penjualan_id, produk_id, nama_produk, harga_satuan, jumlah, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdid", $penjualan_id, $prod['produk_id'], $prod['nama_produk'], $prod['harga'], $prod['qty'], $prod['subtotal']);
                $stmt->execute();
                
                $stok_sesudah = $prod['stok_sebelum'] - $prod['qty'];
                $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE produk_id = ?");
                $stmt->bind_param("ii", $stok_sesudah, $prod['produk_id']);
                $stmt->execute();
                
                $referensi = $no_invoice;
                $keterangan = "Penjualan ke reseller: " . $reseller['nama_reseller'];
                $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id, cabang_id) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, 'approved', ?, ?)");
                $stmt->bind_param("isiiissii", $prod['produk_id'], $tanggal, $prod['qty'], $prod['stok_sebelum'], $stok_sesudah, $referensi, $keterangan, $user['user_id'], $cabang_id_penjualan);
                $stmt->execute();
            }
            
            $message = "Penjualan berhasil! No Invoice: " . $no_invoice . " | Total Produk: " . count($products_data) . " | Total: Rp " . number_format($subtotal_all, 0, ',', '.');
        }
    }
}

// Get products for dropdown
$products = $conn->query("SELECT produk_id, kode_produk, nama_produk, kategori, harga, stok FROM produk WHERE status = 'active' ORDER BY nama_produk");

// Get resellers for dropdown - filtered by user cabang (except administrator & manager)
if (in_array($user['role'], ['administrator', 'manager'])) {
    // Administrator & Manager: See all resellers
    $resellers = $conn->query("SELECT r.reseller_id, r.kode_reseller, r.nama_reseller, r.cabang_id, c.nama_cabang 
                               FROM reseller r 
                               LEFT JOIN cabang c ON r.cabang_id = c.cabang_id 
                               WHERE r.status = 'active' 
                               ORDER BY r.nama_reseller");
} else {
    // Other roles: See only resellers from their cabang
    $stmt = $conn->prepare("SELECT r.reseller_id, r.kode_reseller, r.nama_reseller, r.cabang_id, c.nama_cabang 
                            FROM reseller r 
                            LEFT JOIN cabang c ON r.cabang_id = c.cabang_id 
                            WHERE r.status = 'active' AND r.cabang_id = ? 
                            ORDER BY r.nama_reseller");
    $stmt->bind_param("i", $user['cabang_id']);
    $stmt->execute();
    $resellers = $stmt->get_result();
}

// Get cabang for dropdown (for administrator/staff)
$cabang_list = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");

// Get input barang history data (on input_barang page)
$input_barang_data = null;
$input_barang_start_date = isset($_GET['input_start']) ? $_GET['input_start'] : date('Y-m-d', strtotime('-7 days'));
$input_barang_end_date = isset($_GET['input_end']) ? $_GET['input_end'] : date('Y-m-d');

if ($page === 'input_barang') {
    // Build query based on user role
    if (in_array($user['role'], ['administrator', 'manager'])) {
        // Administrator & Manager: See all input barang
        $input_query = "
            SELECT 
                i.inventory_id,
                i.tanggal,
                i.jumlah,
                i.referensi,
                i.keterangan,
                i.status_approval,
                i.created_at,
                p.nama_produk,
                p.kategori,
                p.harga,
                c.nama_cabang,
                u.full_name as user_name
            FROM inventory i
            JOIN produk p ON i.produk_id = p.produk_id
            LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
            LEFT JOIN users u ON i.user_id = u.user_id
            WHERE i.tipe_transaksi = 'masuk'
            AND i.tanggal BETWEEN ? AND ?
            ORDER BY i.tanggal DESC, i.inventory_id DESC
        ";
        
        $stmt = $conn->prepare($input_query);
        $stmt->bind_param("ss", $input_barang_start_date, $input_barang_end_date);
    } else {
        // Other roles: See only input barang from their cabang
        $input_query = "
            SELECT 
                i.inventory_id,
                i.tanggal,
                i.jumlah,
                i.referensi,
                i.keterangan,
                i.status_approval,
                i.created_at,
                p.nama_produk,
                p.kategori,
                p.harga,
                c.nama_cabang,
                u.full_name as user_name
            FROM inventory i
            JOIN produk p ON i.produk_id = p.produk_id
            LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
            LEFT JOIN users u ON i.user_id = u.user_id
            WHERE i.tipe_transaksi = 'masuk'
            AND i.tanggal BETWEEN ? AND ?
            AND i.cabang_id = ?
            ORDER BY i.tanggal DESC, i.inventory_id DESC
        ";
        
        $stmt = $conn->prepare($input_query);
        $stmt->bind_param("ssi", $input_barang_start_date, $input_barang_end_date, $user['cabang_id']);
    }
    
    $stmt->execute();
    $input_barang_data = $stmt->get_result();
}

// Get penjualan data for report (on input_penjualan page)
$penjualan_data = null;
$penjualan_start_date = isset($_GET['penjualan_start']) ? $_GET['penjualan_start'] : date('Y-m-d', strtotime('-7 days'));
$penjualan_end_date = isset($_GET['penjualan_end']) ? $_GET['penjualan_end'] : date('Y-m-d');

if ($page === 'input_penjualan') {
    // Filter by cabang for non-administrator/manager roles
    if (in_array($user['role'], ['administrator', 'manager'])) {
        // Administrator & Manager: See all sales
    $penjualan_query = "
            SELECT 
                p.penjualan_id,
                p.no_invoice,
                p.tanggal_penjualan,
                r.nama_reseller,
                c.nama_cabang,
                dp.nama_produk,
                dp.jumlah,
                dp.harga_satuan,
                dp.subtotal,
                p.total as total_invoice,
                p.metode_pembayaran,
                p.status_pembayaran
            FROM penjualan p
            JOIN reseller r ON p.reseller_id = r.reseller_id
            LEFT JOIN cabang c ON r.cabang_id = c.cabang_id
            JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
            WHERE p.tanggal_penjualan BETWEEN ? AND ?
            ORDER BY p.tanggal_penjualan DESC, p.penjualan_id DESC, dp.detail_id ASC
        ";
        
        $stmt = $conn->prepare($penjualan_query);
        $stmt->bind_param("ss", $penjualan_start_date, $penjualan_end_date);
    } else {
        // Other roles: See only sales from their cabang
        $penjualan_query = "
            SELECT 
                p.penjualan_id,
                p.no_invoice,
                p.tanggal_penjualan,
                r.nama_reseller,
                c.nama_cabang,
                dp.nama_produk,
                dp.jumlah,
                dp.harga_satuan,
                dp.subtotal,
                p.total as total_invoice,
                p.metode_pembayaran,
                p.status_pembayaran
            FROM penjualan p
            JOIN reseller r ON p.reseller_id = r.reseller_id
            LEFT JOIN cabang c ON r.cabang_id = c.cabang_id
            JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
            WHERE p.tanggal_penjualan BETWEEN ? AND ?
            AND r.cabang_id = ?
            ORDER BY p.tanggal_penjualan DESC, p.penjualan_id DESC, dp.detail_id ASC
        ";
        
        $stmt = $conn->prepare($penjualan_query);
        $stmt->bind_param("ssi", $penjualan_start_date, $penjualan_end_date, $user['cabang_id']);
    }
    
    $stmt->execute();
    $penjualan_data = $stmt->get_result();
}

// Dashboard data
$sales_data = null;
$chart_labels = [];
$reseller_data = [];
$total_transaksi = 0;
$total_penjualan = 0;
$period = 'daily';
$start_date = date('Y-m-d');
$end_date = date('Y-m-d');

if ($page === 'dashboard') {
    $period = isset($_GET['period']) ? $_GET['period'] : 'daily';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    if ($period === 'daily') {
        $start_date = $end_date = date('Y-m-d');
    } elseif ($period === 'weekly') {
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
    } elseif ($period === 'monthly') {
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
    } elseif ($period === 'custom') {
        // Use the provided start_date and end_date from GET parameters
        // Already set above, no need to change
    }
    
    $isAdminDash = in_array($user['role'], ['administrator', 'manager']);
    if ($isAdminDash) {
        $sales_query = "
            SELECT 
                r.reseller_id,
                r.nama_reseller,
                COUNT(p.penjualan_id) as total_transaksi,
                COALESCE(SUM(p.total), 0) as total_penjualan
            FROM reseller r
            LEFT JOIN penjualan p ON r.reseller_id = p.reseller_id 
                AND p.tanggal_penjualan BETWEEN ? AND ?
                AND (p.status_pembayaran = 'paid' OR p.status_pembayaran = 'Paid (Lunas)')
            WHERE r.status = 'active'
            GROUP BY r.reseller_id, r.nama_reseller
            ORDER BY total_penjualan DESC
        ";
        $stmt = $conn->prepare($sales_query);
        $stmt->bind_param("ss", $start_date, $end_date);
    } else {
        $sales_query = "
            SELECT 
                r.reseller_id,
                r.nama_reseller,
                COUNT(p.penjualan_id) as total_transaksi,
                COALESCE(SUM(p.total), 0) as total_penjualan
            FROM reseller r
            LEFT JOIN penjualan p ON r.reseller_id = p.reseller_id 
                AND p.tanggal_penjualan BETWEEN ? AND ?
                AND (p.status_pembayaran = 'paid' OR p.status_pembayaran = 'Paid (Lunas)')
                AND (p.cabang_id = ? OR (p.cabang_id IS NULL AND r.cabang_id = ?))
            WHERE r.status = 'active' AND r.cabang_id = ?
            GROUP BY r.reseller_id, r.nama_reseller
            ORDER BY total_penjualan DESC
        ";
        $stmt = $conn->prepare($sales_query);
        $uidCab = (int)($user['cabang_id'] ?? 0);
        $stmt->bind_param("ssiii", $start_date, $end_date, $uidCab, $uidCab, $uidCab);
    }
    $stmt->execute();
    $sales_data = $stmt->get_result();
    
    if ($isAdminDash) {
        $chart_query = "
            SELECT 
                DATE(p.tanggal_penjualan) as tanggal,
                r.nama_reseller,
                SUM(p.total) as total
            FROM penjualan p
            JOIN reseller r ON p.reseller_id = r.reseller_id
            WHERE p.tanggal_penjualan BETWEEN ? AND ?
                AND (p.status_pembayaran = 'paid' OR p.status_pembayaran = 'Paid (Lunas)')
            GROUP BY DATE(p.tanggal_penjualan), r.nama_reseller
            ORDER BY tanggal, r.nama_reseller
        ";
        $stmt = $conn->prepare($chart_query);
        $stmt->bind_param("ss", $start_date, $end_date);
    } else {
        $chart_query = "
            SELECT 
                DATE(p.tanggal_penjualan) as tanggal,
                r.nama_reseller,
                SUM(p.total) as total
            FROM penjualan p
            JOIN reseller r ON p.reseller_id = r.reseller_id
            WHERE p.tanggal_penjualan BETWEEN ? AND ?
                AND (p.status_pembayaran = 'paid' OR p.status_pembayaran = 'Paid (Lunas)')
                AND (p.cabang_id = ? OR (p.cabang_id IS NULL AND r.cabang_id = ?))
            GROUP BY DATE(p.tanggal_penjualan), r.nama_reseller
            ORDER BY tanggal, r.nama_reseller
        ";
        $stmt = $conn->prepare($chart_query);
        $uidCab = (int)($user['cabang_id'] ?? 0);
        $stmt->bind_param("ssii", $start_date, $end_date, $uidCab, $uidCab);
    }
    $stmt->execute();
    $chart_data = $stmt->get_result();
    
    while ($row = $chart_data->fetch_assoc()) {
        $tanggal = $row['tanggal'];
        $reseller = $row['nama_reseller'];
        $total = $row['total'];
        
        if (!in_array($tanggal, $chart_labels)) {
            $chart_labels[] = $tanggal;
        }
        
        if (!isset($reseller_data[$reseller])) {
            $reseller_data[$reseller] = [];
        }
        
        $reseller_data[$reseller][$tanggal] = $total;
    }
    
    if ($sales_data) {
        $sales_data->data_seek(0);
        while ($row = $sales_data->fetch_assoc()) {
            $total_transaksi += $row['total_transaksi'];
            $total_penjualan += $row['total_penjualan'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Sinar Telkom Dashboard System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css?v=<?php echo time(); ?>">
    <?php if ($page === 'dashboard'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    <script src="../../assets/js/force-lexend.js?v=<?php echo time(); ?>"></script>
</head>
<body class="admin-page">
    <div class="admin-container">
        <aside class="admin-sidebar" style="width: 340px;">
            <div class="sidebar-header">
                <div style="display: flex; flex-direction: column; gap: 15px; align-items: stretch;">
                    <!-- Logo and Title -->
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div style="flex-shrink: 0;">
                            <img src="../../assets/images/logo_icon.png" alt="Logo" style="width: 60px; height: 60px; border-radius: 10px; object-fit: contain; background: rgba(255,255,255,0.1); padding: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        </div>
                        <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: white; letter-spacing: 0.5px;">INVENTORY</h2>
                    </div>
                    
                    <!-- User Info Box -->
                    <div style="background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.15) 100%); padding: 12px 14px; border-radius: 10px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                        <p style="margin: 0 0 4px 0; font-weight: 600; font-size: 13px; color: white; line-height: 1.4; word-wrap: break-word; word-break: break-word;"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p style="margin: 0; font-size: 11px; color: rgba(255,255,255,0.85); font-weight: 400; text-transform: capitalize; line-height: 1.3;"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=dashboard" class="nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <?php if (in_array($user['role'], ['administrator', 'manager', 'finance'])): ?>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=input_barang" class="nav-item <?php echo $page === 'input_barang' ? 'active' : ''; ?>">
                    <span class="nav-icon">📥</span>
                    <span>Input Barang</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock_masuk.php" class="nav-item">
                    <span class="nav-icon">📥</span>
                    <span>Stock Masuk</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock_keluar.php" class="nav-item">
                    <span class="nav-icon">📤</span>
                    <span>Stock Keluar</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=input_penjualan" class="nav-item <?php echo $page === 'input_penjualan' ? 'active' : ''; ?>">
                    <span class="nav-icon">💰</span>
                    <span>Input Penjualan</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Stock</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_laporan.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>Laporan Penjualan</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="../../logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>
                    <?php 
                    if ($page === 'dashboard') echo 'Dashboard Inventory';
                    elseif ($page === 'input_barang') echo 'Input Barang';
                    elseif ($page === 'input_penjualan') echo 'Input Penjualan';
                    elseif ($page === 'stock') echo 'Monitoring Stock';
                    elseif ($page === 'laporan_penjualan') echo 'Laporan Penjualan';
                    ?>
                </h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($page === 'dashboard'): ?>
                    <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 30px;">
                        <h3 style="color: #2c3e50; margin: 0 0 20px 0;">📅 Filter Periode Dashboard</h3>
                        <form method="GET">
                            <input type="hidden" name="page" value="dashboard">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Period</label>
                                    <select name="period" id="periodSelect" onchange="toggleDateInputs()" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-family: 'Lexend', sans-serif; font-size: 14px;">
                                        <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Daily (Hari Ini)</option>
                                        <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Weekly (7 Hari Terakhir)</option>
                                        <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly (Bulan Ini)</option>
                                        <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                    </select>
                                </div>
                                
                                <div id="startDateDiv" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Mulai</label>
                                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-family: 'Lexend', sans-serif; font-size: 14px;">
                                </div>
                                
                                <div id="endDateDiv" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Akhir</label>
                                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-family: 'Lexend', sans-serif; font-size: 14px;">
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button type="submit" class="btn-add">🔍 Terapkan Filter</button>
                                <a href="?page=dashboard" style="padding: 10px 20px; background: #e9ecef; color: #495057; text-decoration: none; border-radius: 8px; font-weight: 500;">🔄 Reset</a>
                                <div style="margin-left: auto; color: #7f8c8d; font-size: 14px;">
                                    <strong>Periode:</strong> <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <script>
                    function toggleDateInputs() {
                        const period = document.getElementById('periodSelect').value;
                        const startDateDiv = document.getElementById('startDateDiv');
                        const endDateDiv = document.getElementById('endDateDiv');
                        
                        if (period === 'custom') {
                            startDateDiv.style.display = 'block';
                            endDateDiv.style.display = 'block';
                        } else {
                            startDateDiv.style.display = 'none';
                            endDateDiv.style.display = 'none';
                        }
                    }
                    </script>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #3498db;">📊</div>
                            <div class="stat-info">
                                <h3><?php echo number_format($total_transaksi); ?></h3>
                                <p>Total Transaksi</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #27ae60;">💰</div>
                            <div class="stat-info">
                                <h3>Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></h3>
                                <p>Total Penjualan</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #9b59b6;">📈</div>
                            <div class="stat-info">
                                <h3>Rp <?php echo $total_transaksi > 0 ? number_format($total_penjualan / $total_transaksi, 0, ',', '.') : 0; ?></h3>
                                <p>Rata-rata</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #e74c3c;">📅</div>
                            <div class="stat-info">
                                <h3 style="font-size: 16px;"><?php echo date('d M', strtotime($start_date)); ?> - <?php echo date('d M', strtotime($end_date)); ?></h3>
                                <p>Period</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 30px;">
                        <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 20px;">📈 Grafik Penjualan</h2>
                        <canvas id="salesChart" height="80"></canvas>
                    </div>
                    
                    <div class="table-container">
                        <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 20px;">📋 Summary per Reseller</h2>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Reseller</th>
                                    <th>Total Transaksi</th>
                                    <th>Total Penjualan</th>
                                    <th>Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($sales_data) {
                                    $sales_data->data_seek(0);
                                    while ($row = $sales_data->fetch_assoc()): 
                                        $avg = $row['total_transaksi'] > 0 ? $row['total_penjualan'] / $row['total_transaksi'] : 0;
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_reseller']); ?></td>
                                    <td><?php echo number_format($row['total_transaksi']); ?></td>
                                    <td>Rp <?php echo number_format($row['total_penjualan'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($avg, 0, ',', '.'); ?></td>
                                </tr>
                                <?php 
                                    endwhile;
                                }
                                if (!$sales_data || $sales_data->num_rows === 0): 
                                ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">Tidak ada data</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <script>
                        const ctx = document.getElementById('salesChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode($chart_labels); ?>,
                                datasets: [
                                    <?php
                                    $colors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a'];
                                    $colorIndex = 0;
                                    foreach ($reseller_data as $reseller => $data) {
                                        $values = [];
                                        foreach ($chart_labels as $label) {
                                            $values[] = isset($data[$label]) ? $data[$label] : 0;
                                        }
                                        $color = $colors[$colorIndex % count($colors)];
                                        echo "{
                                            label: '" . addslashes($reseller) . "',
                                            data: " . json_encode($values) . ",
                                            borderColor: '$color',
                                            backgroundColor: '$color' + '20',
                                            tension: 0.4
                                        },";
                                        $colorIndex++;
                                    }
                                    ?>
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { position: 'top' }
                                }
                            }
                        });
                    </script>
                    
                                <?php elseif ($page === 'input_barang'): ?>
                                    <?php if (!in_array($user['role'], ['administrator', 'manager', 'finance'])): ?>
                                        <div style="background:#f8d7da;color:#721c24;padding:25px;border-radius:12px;margin-bottom:20px;">
                                            <h2 style="margin:0 0 10px 0;">🚫 Akses Ditolak</h2>
                                            <p>Role Anda (<strong><?php echo htmlspecialchars($user['role']); ?></strong>) tidak memiliki akses ke halaman Input Barang.</p>
                                            <p>Hanya <strong>Administrator, Manager, Finance</strong> yang diizinkan.</p>
                                            <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=dashboard" class="btn-add" style="margin-top:15px;display:inline-block;">← Kembali ke Dashboard</a>
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        $partial_path = __DIR__ . '/partials/input_barang.php';
                                        if (file_exists($partial_path) && is_readable($partial_path)) {
                                            include $partial_path;
                                        } else {
                                            $error_msg = '<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                                            $error_msg .= '<h3 style="margin-top:0;">⚠️ Error: File tidak dapat dimuat</h3>';
                                            $error_msg .= '<p><strong>Path:</strong> ' . htmlspecialchars($partial_path) . '</p>';
                                            $error_msg .= '<p><strong>File exists:</strong> ' . (file_exists($partial_path) ? 'Yes' : 'No') . '</p>';
                                            $error_msg .= '<p><strong>Is readable:</strong> ' . (is_readable($partial_path) ? 'Yes' : 'No') . '</p>';
                                            $error_msg .= '<p><strong>Current dir:</strong> ' . htmlspecialchars(__DIR__) . '</p>';
                                            $error_msg .= '<hr style="margin: 15px 0;">';
                                            $error_msg .= '<p><strong>Solusi:</strong></p>';
                                            $error_msg .= '<ol style="margin-left: 20px;">';
                                            $error_msg .= '<li>Pastikan folder <code>partials/</code> ada di <code>modules/inventory/</code></li>';
                                            $error_msg .= '<li>Pastikan file <code>input_barang.php</code> ada di folder <code>partials/</code></li>';
                                            $error_msg .= '<li>Periksa permission file (chmod 644 atau 755)</li>';
                                            $error_msg .= '</ol></div>';
                                            echo $error_msg;
                                        }
                                        ?>
                                    <?php endif; ?>
                    
                <?php elseif ($page === 'input_penjualan'): ?>
                    <div class="form-container" style="max-width: 900px;">
                        <h2>💰 Form Input Penjualan</h2>
                        <p style="color: #7f8c8d; margin-bottom: 20px;">Input penjualan untuk 1 reseller dengan multiple produk</p>
                        
                        <form method="POST" id="penjualanForm">
                            <input type="hidden" name="action" value="input_penjualan">
                            
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_penjualan" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Reseller</label>
                                <select name="reseller_id" id="resellerSelect" onchange="updateCabangReseller()" required>
                                    <option value="">-- Pilih Reseller --</option>
                                    <?php 
                                    if ($resellers) {
                                        $resellers->data_seek(0);
                                        while ($r = $resellers->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $r['reseller_id']; ?>" 
                                                data-cabang-id="<?php echo $r['cabang_id'] ?? ''; ?>" 
                                                data-cabang-nama="<?php echo htmlspecialchars($r['nama_cabang'] ?? '-'); ?>">
                                            <?php echo htmlspecialchars($r['nama_reseller']); ?>
                                            <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                                                (<?php echo htmlspecialchars($r['nama_cabang'] ?? '-'); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Cabang Reseller</label>
                                <input type="text" id="cabangResellerDisplay" readonly style="background: #f8f9fa; cursor: not-allowed; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; width: 100%;" placeholder="Pilih reseller terlebih dahulu">
                                <small style="color: #7f8c8d; font-size: 12px;">Cabang otomatis sesuai dengan reseller yang dipilih</small>
                            </div>
                            
                            <hr style="margin: 25px 0; border: none; border-top: 2px solid #e9ecef;">
                            
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">📦 Daftar Produk</h3>
                            
                            <div id="productContainer">
                                <div class="product-row" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <h4 style="margin: 0; color: #2c3e50;">Produk #1</h4>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 10px;">
                                        <div class="form-group" style="margin: 0;">
                                            <label>Produk</label>
                                            <select name="produk_id[]" class="produk-select" onchange="updateProductDetails(this)" required>
                                                <option value="">-- Pilih Produk --</option>
                                                <?php 
                                                if ($products) {
                                                    $products->data_seek(0);
                                                    while ($p = $products->fetch_assoc()): 
                                                ?>
                                                    <option value="<?php echo $p['produk_id']; ?>" 
                                                            data-harga="<?php echo $p['harga']; ?>" 
                                                            data-stok="<?php echo $p['stok']; ?>"
                                                            data-kategori="<?php echo htmlspecialchars($p['kategori']); ?>">
                                                        <?php echo htmlspecialchars($p['nama_produk']); ?>
                                                    </option>
                                                <?php endwhile; } ?>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label>Kategori</label>
                                            <input type="text" class="kategori-display" readonly style="background: #f8f9fa; font-size: 12px; padding: 8px;">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label>Harga</label>
                                            <input type="text" class="harga-display" readonly style="background: #f8f9fa; font-weight: 600; padding: 8px;">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label>Qty</label>
                                            <input type="number" name="qty[]" class="qty-input" min="1" onchange="calculateSubtotal(this)" required>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label>Subtotal</label>
                                            <input type="text" class="subtotal-display" readonly style="background: #e8f5e9; font-weight: 600; color: #27ae60; padding: 8px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" onclick="addProduct()" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-bottom: 20px; font-weight: 500;">
                                ➕ Tambah Produk
                            </button>
                            
                            <hr style="margin: 25px 0; border: none; border-top: 2px solid #e9ecef;">
                            
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">💳 Informasi Pembayaran</h3>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label>Metode Pembayaran</label>
                                    <select name="metode_pembayaran" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                        <option value="">-- Pilih Metode --</option>
                                        <option value="Transfer">Transfer</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Budget Komitmen">Budget Komitmen</option>
                                        <option value="Finpay">Finpay</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Status Pembayaran</label>
                                    <select name="status_pembayaran" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Paid (Lunas)">Paid (Lunas)</option>
                                        <option value="Pending (Menunggu)">Pending (Menunggu)</option>
                                        <option value="TOP (Term Off Payment)">TOP (Term Off Payment)</option>
                                        <option value="Cancelled (Dibatalkan)">Cancelled (Dibatalkan)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;">
                                <h3 style="margin: 0 0 10px 0; font-size: 16px; opacity: 0.9;">TOTAL KESELURUHAN</h3>
                                <div id="grandTotal" style="font-size: 32px; font-weight: 700;">Rp 0</div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">💰 Proses Penjualan</button>
                                <a href="?page=dashboard" class="btn-cancel">❌ Batal</a>
                            </div>
                        </form>
                    </div>
                    
                    <script>
                    let productCount = 1;
                    
                    function addProduct() {
                        productCount++;
                        const container = document.getElementById('productContainer');
                        const newRow = document.createElement('div');
                        newRow.className = 'product-row';
                        newRow.style.cssText = 'background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;';
                        
                        newRow.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="margin: 0; color: #2c3e50;">Produk #${productCount}</h4>
                                <button type="button" onclick="removeProduct(this)" style="background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">🗑️ Hapus</button>
                            </div>
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 10px;">
                                <div class="form-group" style="margin: 0;">
                                    <label>Produk</label>
                                    <select name="produk_id[]" class="produk-select" onchange="updateProductDetails(this)" required>
                                        <option value="">-- Pilih Produk --</option>
                                        <?php 
                                        if ($products) {
                                            $products->data_seek(0);
                                            while ($p = $products->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $p['produk_id']; ?>" 
                                                    data-harga="<?php echo $p['harga']; ?>" 
                                                    data-stok="<?php echo $p['stok']; ?>"
                                                    data-kategori="<?php echo htmlspecialchars($p['kategori']); ?>">
                                                <?php echo htmlspecialchars($p['nama_produk']); ?>
                                            </option>
                                        <?php endwhile; } ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Kategori</label>
                                    <input type="text" class="kategori-display" readonly style="background: #f8f9fa; font-size: 12px; padding: 8px;">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Harga</label>
                                    <input type="text" class="harga-display" readonly style="background: #f8f9fa; font-weight: 600; padding: 8px;">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Qty</label>
                                    <input type="number" name="qty[]" class="qty-input" min="1" onchange="calculateSubtotal(this)" required>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Subtotal</label>
                                    <input type="text" class="subtotal-display" readonly style="background: #e8f5e9; font-weight: 600; color: #27ae60; padding: 8px;">
                                </div>
                            </div>
                        `;
                        
                        container.appendChild(newRow);
                        updateRemoveButtons();
                    }
                    
                    function removeProduct(btn) {
                        const row = btn.closest('.product-row');
                        row.remove();
                        
                        const rows = document.querySelectorAll('.product-row');
                        rows.forEach((row, index) => {
                            row.querySelector('h4').textContent = `Produk #${index + 1}`;
                        });
                        
                        productCount = rows.length;
                        updateRemoveButtons();
                        calculateGrandTotal();
                    }
                    
                    function updateRemoveButtons() {
                        const rows = document.querySelectorAll('.product-row');
                        rows.forEach((row, index) => {
                            const removeBtn = row.querySelector('button[onclick^="removeProduct"]');
                            if (removeBtn) {
                                removeBtn.style.display = rows.length === 1 ? 'none' : 'inline-block';
                            }
                        });
                    }
                    
                    function updateProductDetails(element) {
                        const row = element.closest('.product-row');
                        const produkSelect = row.querySelector('.produk-select');
                        const kategoriDisplay = row.querySelector('.kategori-display');
                        const hargaDisplay = row.querySelector('.harga-display');
                        const qtyInput = row.querySelector('.qty-input');
                        const subtotalDisplay = row.querySelector('.subtotal-display');
                        
                        if (produkSelect.value) {
                            const selectedOption = produkSelect.options[produkSelect.selectedIndex];
                            const kategori = selectedOption.getAttribute('data-kategori');
                            const harga = parseInt(selectedOption.getAttribute('data-harga'));
                            
                            kategoriDisplay.value = kategori || '-';
                            hargaDisplay.value = 'Rp ' + harga.toLocaleString('id-ID');
                            
                            // Calculate subtotal if qty is filled
                            if (qtyInput.value) {
                                calculateSubtotal(qtyInput);
                            }
                        } else {
                            kategoriDisplay.value = '';
                            hargaDisplay.value = '';
                            subtotalDisplay.value = '';
                        }
                    }
                    
                    function calculateSubtotal(element) {
                        const row = element.closest('.product-row');
                        const produkSelect = row.querySelector('.produk-select');
                        const qtyInput = row.querySelector('.qty-input');
                        const subtotalDisplay = row.querySelector('.subtotal-display');
                        
                        const produkId = produkSelect.value;
                        const qty = parseInt(qtyInput.value) || 0;
                        
                        if (produkId && qty > 0) {
                            const selectedOption = produkSelect.options[produkSelect.selectedIndex];
                            const harga = parseInt(selectedOption.getAttribute('data-harga'));
                            const stok = parseInt(selectedOption.getAttribute('data-stok'));
                            
                            if (qty > stok) {
                                alert(`Stok tidak mencukupi! Stok tersedia: ${stok}`);
                                qtyInput.value = stok;
                                return;
                            }
                            
                            const subtotal = harga * qty;
                            subtotalDisplay.value = 'Rp ' + subtotal.toLocaleString('id-ID');
                        } else {
                            subtotalDisplay.value = '';
                        }
                        
                        calculateGrandTotal();
                    }
                    
                    function calculateGrandTotal() {
                        let total = 0;
                        
                        document.querySelectorAll('.product-row').forEach(row => {
                            const produkSelect = row.querySelector('.produk-select');
                            const qtyInput = row.querySelector('.qty-input');
                            
                            const produkId = produkSelect.value;
                            const qty = parseInt(qtyInput.value) || 0;
                            
                            if (produkId && qty > 0) {
                                const selectedOption = produkSelect.options[produkSelect.selectedIndex];
                                const harga = parseInt(selectedOption.getAttribute('data-harga'));
                                total += harga * qty;
                            }
                        });
                        
                        document.getElementById('grandTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
                    }
                    
                    document.getElementById('penjualanForm').addEventListener('submit', function(e) {
                        const rows = document.querySelectorAll('.product-row');
                        let hasProduct = false;
                        
                        rows.forEach(row => {
                            const produkId = row.querySelector('.produk-select').value;
                            const qty = row.querySelector('.qty-input').value;
                            
                            if (produkId && qty) {
                                hasProduct = true;
                            }
                        });
                        
                        if (!hasProduct) {
                            e.preventDefault();
                            alert('Minimal harus ada 1 produk yang diisi!');
                            return false;
                        }
                        
                        return confirm('Proses penjualan ini?');
                    });
                    
                    updateRemoveButtons();
                    
                    // Function to update cabang reseller display
                    function updateCabangReseller() {
                        const resellerSelect = document.getElementById('resellerSelect');
                        const cabangDisplay = document.getElementById('cabangResellerDisplay');
                        
                        if (resellerSelect.value) {
                            const selectedOption = resellerSelect.options[resellerSelect.selectedIndex];
                            const cabangNama = selectedOption.getAttribute('data-cabang-nama');
                            cabangDisplay.value = cabangNama || '-';
                        } else {
                            cabangDisplay.value = '';
                            cabangDisplay.placeholder = 'Pilih reseller terlebih dahulu';
                        }
                    }
                    
                    // Export to Excel function
                    function exportToExcel() {
                        const table = document.getElementById('penjualanTable');
                        if (!table) {
                            alert('Tidak ada data untuk di-export');
                            return;
                        }
                        
                        // Clone table to modify for export
                        const clonedTable = table.cloneNode(true);
                        
                        // Convert table to Excel format
                        let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                        html += '<head><meta charset="utf-8"><style>table {border-collapse: collapse;} th, td {border: 1px solid #ddd; padding: 8px;}</style></head>';
                        html += '<body>';
                        html += '<table>' + clonedTable.innerHTML + '</table>';
                        html += '</body></html>';
                        
                        const blob = new Blob(['\ufeff', html], {
                            type: 'application/vnd.ms-excel'
                        });
                        
                        const url = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = 'Laporan_Penjualan_' + new Date().toISOString().slice(0,10) + '.xls';
                        link.click();
                        window.URL.revokeObjectURL(url);
                    }
                    
                    // Export to CSV function
                    function exportToCSV() {
                        const table = document.getElementById('penjualanTable');
                        if (!table) {
                            alert('Tidak ada data untuk di-export');
                            return;
                        }
                        
                        let csv = [];
                        const rows = table.querySelectorAll('tr');
                        
                        for (let i = 0; i < rows.length; i++) {
                            const row = [];
                            const cols = rows[i].querySelectorAll('td, th');
                            
                            for (let j = 0; j < cols.length; j++) {
                                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""');
                                row.push('"' + data + '"');
                            }
                            
                            csv.push(row.join(','));
                        }
                        
                        const csvContent = csv.join('\n');
                        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
                        const url = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = 'Laporan_Penjualan_' + new Date().toISOString().slice(0,10) + '.csv';
                        link.click();
                        window.URL.revokeObjectURL(url);
                    }
                    </script>
                    
                    <!-- Laporan Penjualan -->
                    <div style="margin-top: 40px;">
                        <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
                            <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 20px;">📋 Laporan Penjualan</h2>
                            <form method="GET" style="margin-bottom: 20px;">
                                <input type="hidden" name="page" value="input_penjualan">
                                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Mulai</label>
                                        <input type="date" name="penjualan_start" value="<?php echo $penjualan_start_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                    </div>
                                    <div style="flex: 1; min-width: 200px;">
                                        <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Akhir</label>
                                        <input type="date" name="penjualan_end" value="<?php echo $penjualan_end_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                    </div>
                                    <div>
                                        <button type="submit" class="btn-add">🔍 Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div class="table-container">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0; color: #2c3e50;">Data Penjualan</h3>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="exportToExcel()" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                        📊 Export Excel
                                    </button>
                                    <button onclick="exportToCSV()" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                        📄 Export CSV
                                    </button>
                                </div>
                            </div>
                            <table class="data-table" id="penjualanTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Invoice</th>
                                        <th>Reseller</th>
                                        <th>Cabang</th>
                                        <th>Produk</th>
                                        <th>Metode</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $grand_total = 0;
                                    $current_invoice = '';
                                    $invoice_total = 0;
                                    $invoice_count = 0;
                                    
                                    if ($penjualan_data && $penjualan_data->num_rows > 0) {
                                        while ($row = $penjualan_data->fetch_assoc()) { 
                                            // Check if this is a new invoice
                                            if ($current_invoice !== $row['no_invoice']) {
                                                // Display subtotal for previous invoice if exists
                                                if ($current_invoice !== '' && $invoice_count > 1) {
                                                    ?>
                                            <tr style="background: #f0f8ff; font-weight: 600;">
                                                <td colspan="8" style="text-align: right; padding: 10px; font-size: 13px;">Subtotal Invoice <?php echo htmlspecialchars($current_invoice); ?>:</td>
                                                <td style="color: #3498db;"><strong>Rp <?php echo number_format($invoice_total, 0, ',', '.'); ?></strong></td>
                                            </tr>
                                                    <?php
                                                }
                                                
                                                $current_invoice = $row['no_invoice'];
                                                $invoice_total = $row['total_invoice'];
                                                $invoice_count = 0;
                                                $grand_total += $row['total_invoice'];
                                            }
                                            
                                            $invoice_count++;
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_penjualan'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['no_invoice']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['nama_reseller']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_cabang'] ?? '-'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['nama_produk']); ?></strong><br>
                                            <small style="color: #7f8c8d;">
                                                <?php echo $row['jumlah']; ?> × Rp <?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?> 
                                                = Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; font-weight: 500;">
                                                <?php echo htmlspecialchars($row['metode_pembayaran']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $row['status_pembayaran'];
                                            $badge_color = '';
                                            $badge_bg = '';
                                            
                                            switch($status) {
                                                // New canonical values
                                                case 'Paid (Lunas)':
                                                    $badge_color = '#27ae60';
                                                    $badge_bg = '#d4edda';
                                                    break;
                                                case 'Pending (Menunggu)':
                                                    $badge_color = '#f39c12';
                                                    $badge_bg = '#fff3cd';
                                                    break;
                                                case 'TOP (Term Off Payment)':
                                                    $badge_color = '#3498db';
                                                    $badge_bg = '#d1ecf1';
                                                    break;
                                                case 'Cancelled (Dibatalkan)':
                                                    $badge_color = '#e74c3c';
                                                    $badge_bg = '#f8d7da';
                                                    break;
                                                // Backward compatibility with old/simple values
                                                case 'Paid':
                                                case 'paid':
                                                    $badge_color = '#27ae60';
                                                    $badge_bg = '#d4edda';
                                                    break;
                                                case 'Pending':
                                                case 'pending':
                                                    $badge_color = '#f39c12';
                                                    $badge_bg = '#fff3cd';
                                                    break;
                                                case 'TOP':
                                                    $badge_color = '#3498db';
                                                    $badge_bg = '#d1ecf1';
                                                    break;
                                                case 'Cancelled':
                                                case 'cancelled':
                                                case 'refunded':
                                                    $badge_color = '#e74c3c';
                                                    $badge_bg = '#f8d7da';
                                                    break;
                                                default:
                                                    $badge_color = '#7f8c8d';
                                                    $badge_bg = '#e9ecef';
                                            }
                                            ?>
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td><strong>Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?></strong></td>
                                    </tr>
                                    <?php 
                                        }
                                        
                                        // Display subtotal for last invoice if it has multiple products
                                        if ($invoice_count > 1) {
                                            ?>
                                            <tr style="background: #f0f8ff; font-weight: 600;">
                                                <td colspan="8" style="text-align: right; padding: 10px; font-size: 13px;">Subtotal Invoice <?php echo htmlspecialchars($current_invoice); ?>:</td>
                                                <td style="color: #3498db;"><strong>Rp <?php echo number_format($invoice_total, 0, ',', '.'); ?></strong></td>
                                            </tr>
                                            <?php
                                        }
                                    } else { ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                            Tidak ada data penjualan untuk periode ini
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                <?php if ($penjualan_data && $penjualan_data->num_rows > 0): ?>
                                <tfoot>
                                    <tr style="background: #f8f9fa; font-weight: 600;">
                                        <td colspan="8" style="text-align: right; padding: 15px;">GRAND TOTAL:</td>
                                        <td style="color: #27ae60; font-size: 16px;"><strong>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
