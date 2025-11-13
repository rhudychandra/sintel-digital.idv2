<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Handle Approval/Rejection
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $inventory_id = $_POST['inventory_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    
    // Get inventory details
    $stmt = $conn->prepare("SELECT i.*, p.nama_produk FROM inventory i 
                           LEFT JOIN produk p ON i.produk_id = p.produk_id 
                           WHERE i.inventory_id = ?");
    $stmt->bind_param("i", $inventory_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Transaksi tidak ditemukan!";
    } else {
        $inventory = $result->fetch_assoc();
        
        // Check if already processed
        if ($inventory['status_approval'] !== 'pending') {
            $error = "Transaksi sudah diproses sebelumnya!";
        } else {
            // Check permission: only cabang tujuan users, administrator, manager can approve
            $can_approve = false;
            
            if (in_array($user['role'], ['administrator', 'manager'])) {
                $can_approve = true;
            } elseif (in_array($user['role'], ['admin', 'staff', 'supervisor', 'finance'])) {
                // Check if user's cabang matches the inventory cabang
                if ($inventory['cabang_id'] == $user['cabang_id']) {
                    $can_approve = true;
                }
            }
            
            if (!$can_approve) {
                $error = "Anda tidak memiliki akses untuk approve transaksi ini!";
            } else {
                if ($action === 'approve') {
                    // Update product stock (GLOBAL STOCK - produk table has cabang_id = NULL)
                    // Simply add stock to the product
                    $stmt = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE produk_id = ?");
                    $stmt->bind_param("ii", $inventory['jumlah'], $inventory['produk_id']);
                    $stmt->execute();
                    
                    // Calculate stok_sesudah: stok_sebelum + jumlah
                    $stok_sebelum_masuk = $inventory['stok_sebelum'] ?? 0;
                    $new_stock_masuk = $stok_sebelum_masuk + $inventory['jumlah'];
                    
                    // Update status to approved AND update stok_sesudah
                    $stmt = $conn->prepare("UPDATE inventory SET status_approval = 'approved', stok_sesudah = ? WHERE inventory_id = ?");
                    $stmt->bind_param("ii", $new_stock_masuk, $inventory_id);
                    $stmt->execute();
                    
                    // Check if there's related stock keluar (pindah gudang)
                    if (preg_match('/Ref: ([A-Z0-9-]+)/', $inventory['keterangan'], $matches)) {
                        $related_ref = $matches[1];
                        
                        // Find and approve related stock keluar
                        $stmt = $conn->prepare("SELECT i.inventory_id, i.jumlah, i.produk_id, i.cabang_id, i.stok_sebelum 
                                               FROM inventory i
                                               WHERE i.tipe_transaksi = 'keluar' 
                                               AND i.referensi = ? 
                                               AND i.status_approval = 'pending'
                                               LIMIT 1");
                        $stmt->bind_param("s", $related_ref);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $stock_keluar = $result->fetch_assoc();
                            
                            // Reduce stock from global product (same product, global stock)
                            $stmt = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE produk_id = ?");
                            $stmt->bind_param("ii", $stock_keluar['jumlah'], $stock_keluar['produk_id']);
                            $stmt->execute();
                            
                            // Calculate new stock at cabang asal (from approved transactions INCLUDING this one)
                            $cabang_keluar_id = $stock_keluar['cabang_id'] ?? null;
                            if ($cabang_keluar_id) {
                                // Get stok_sebelum from the stock keluar record
                                $stok_sebelum_keluar = $stock_keluar['stok_sebelum'] ?? 0;
                                // Calculate: stok_sebelum - jumlah = stok_sesudah
                                $new_stock_keluar = $stok_sebelum_keluar - $stock_keluar['jumlah'];
                            } else {
                                // If no cabang_id, use global stock
                                $stmt = $conn->prepare("SELECT stok FROM produk WHERE produk_id = ?");
                                $stmt->bind_param("i", $stock_keluar['produk_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $new_stock_keluar = $result->fetch_assoc()['stok'];
                            }
                            
                            // Approve stock keluar AND update stok_sesudah with cabang stock
                            $stmt = $conn->prepare("UPDATE inventory SET status_approval = 'approved', stok_sesudah = ? WHERE inventory_id = ?");
                            $stmt->bind_param("ii", $new_stock_keluar, $stock_keluar['inventory_id']);
                            $stmt->execute();
                            
                            $message = "✅ Stock masuk APPROVED! Produk: " . $inventory['nama_produk'] . " | Qty: +" . $inventory['jumlah'] . " | Stock berhasil ditambahkan! (Stock keluar terkait juga di-approve)";
                        } else {
                            $message = "✅ Stock masuk APPROVED! Produk: " . $inventory['nama_produk'] . " | Qty: +" . $inventory['jumlah'] . " | Stock berhasil ditambahkan!";
                        }
                    } else {
                        $message = "✅ Stock masuk APPROVED! Produk: " . $inventory['nama_produk'] . " | Qty: +" . $inventory['jumlah'] . " | Stock berhasil ditambahkan!";
                    }
                    
                } elseif ($action === 'reject') {
                    // Update status to rejected
                    $stmt = $conn->prepare("UPDATE inventory SET status_approval = 'rejected' WHERE inventory_id = ?");
                    $stmt->bind_param("i", $inventory_id);
                    $stmt->execute();
                    
                    // Check if there's related stock keluar (pindah gudang)
                    if (preg_match('/Ref: ([A-Z0-9-]+)/', $inventory['keterangan'], $matches)) {
                        $related_ref = $matches[1];
                        
                        // Find and reject related stock keluar (NO STOCK CHANGES because it was never reduced)
                        $stmt = $conn->prepare("UPDATE inventory SET status_approval = 'rejected' 
                                               WHERE tipe_transaksi = 'keluar' 
                                               AND referensi = ? 
                                               AND status_approval = 'pending'");
                        $stmt->bind_param("s", $related_ref);
                        $stmt->execute();
                        
                        $message = "❌ Stock masuk REJECTED! Produk: " . $inventory['nama_produk'] . " | Qty: " . $inventory['jumlah'] . " | Stock TIDAK ditambahkan. (Stock keluar terkait juga di-reject)";
                    } else {
                        $message = "❌ Stock masuk REJECTED! Produk: " . $inventory['nama_produk'] . " | Qty: " . $inventory['jumlah'] . " | Stock TIDAK ditambahkan.";
                    }
                }
            }
        }
    }
}

// Get filter parameters
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';
$filter_produk = isset($_GET['produk_id']) ? $_GET['produk_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : ''; // NEW: filter by status

// Pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Build query for stock masuk with filters
$where_conditions = ["i.tipe_transaksi = 'masuk'"];
$params = [];
$types = "";

// Date filter
$where_conditions[] = "i.tanggal BETWEEN ? AND ?";
$params[] = $filter_start_date;
$params[] = $filter_end_date;
$types .= "ss";

// Role-based cabang filter
if (!in_array($user['role'], ['administrator', 'manager'])) {
    $where_conditions[] = "(i.cabang_id = ? OR i.cabang_id IS NULL)";
    $params[] = $user['cabang_id'];
    $types .= "i";
}

// Cabang filter
if (!empty($filter_cabang)) {
    $where_conditions[] = "i.cabang_id = ?";
    $params[] = $filter_cabang;
    $types .= "i";
}

// Produk filter
if (!empty($filter_produk)) {
    $where_conditions[] = "i.produk_id = ?";
    $params[] = $filter_produk;
    $types .= "i";
}

// Status filter
if (!empty($filter_status)) {
    $where_conditions[] = "i.status_approval = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM inventory i WHERE " . $where_clause;
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get stock masuk data with cabang asal
$stock_masuk_query = "SELECT 
    i.inventory_id,
    i.tanggal,
    i.jumlah,
    i.stok_sebelum,
    i.stok_sesudah,
    i.referensi,
    i.keterangan,
    i.status_approval,
    p.nama_produk,
    p.kategori,
    p.harga,
    COALESCE(c.nama_cabang, 'Pusat/Global') as nama_cabang,
    i.cabang_id,
    u.full_name as user_name,
    (
        SELECT COALESCE(c2.nama_cabang, NULL)
        FROM inventory i2 
        LEFT JOIN cabang c2 ON i2.cabang_id = c2.cabang_id
        WHERE i2.tipe_transaksi = 'keluar'
        AND i.keterangan LIKE CONCAT('%Ref: ', i2.referensi, '%')
        AND i2.produk_id = i.produk_id
        AND i2.tanggal = i.tanggal
        LIMIT 1
    ) as cabang_asal
FROM inventory i
LEFT JOIN produk p ON i.produk_id = p.produk_id
LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
LEFT JOIN users u ON i.user_id = u.user_id
WHERE " . $where_clause . "
ORDER BY i.status_approval ASC, i.tanggal DESC, i.inventory_id DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($stock_masuk_query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stock_masuk_result = $stmt->get_result();

$stock_masuk_data = [];
$total_qty = 0;
$total_nilai = 0;
$count_pending = 0;
$count_approved = 0;
$count_rejected = 0;

while ($row = $stock_masuk_result->fetch_assoc()) {
    $stock_masuk_data[] = $row;
    $total_qty += $row['jumlah'];
    $total_nilai += $row['jumlah'] * $row['harga'];
    
    if ($row['status_approval'] === 'pending') $count_pending++;
    elseif ($row['status_approval'] === 'approved') $count_approved++;
    elseif ($row['status_approval'] === 'rejected') $count_rejected++;
}

// Get cabang list for filter
if (in_array($user['role'], ['administrator', 'manager'])) {
    $cabang_list = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");
} else {
    $stmt = $conn->prepare("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' AND cabang_id = ? ORDER BY nama_cabang");
    $stmt->bind_param("i", $user['cabang_id']);
    $stmt->execute();
    $cabang_list = $stmt->get_result();
}

// Get produk list for filter
$produk_list = $conn->query("SELECT produk_id, nama_produk FROM produk WHERE status = 'active' ORDER BY nama_produk");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Masuk - Approval System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
    <style>
        .badge-pending {
            background: #fff3cd;
            color: #856404;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-approved {
            background: #d4edda;
            color: #155724;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-approve {
            background: #27ae60;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-reject {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-approve:hover {
            background: #229954;
        }
        
        .btn-reject:hover {
            background: #c0392b;
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <!-- Sidebar (Standardized) -->
        <aside class="admin-sidebar" style="width:340px;">
            <div class="sidebar-header">
                <div style="display:flex; flex-direction:column; gap:15px; align-items:stretch;">
                    <div style="display:flex; gap:12px; align-items:center;">
                        <div style="flex-shrink:0;">
                            <img src="../../assets/images/logo_icon.png" alt="Logo" style="width:60px; height:60px; border-radius:10px; object-fit:contain; background:rgba(255,255,255,0.1); padding:6px; box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                        </div>
                        <h2 style="margin:0; font-size:20px; font-weight:700; color:#fff; letter-spacing:.5px;">INVENTORY</h2>
                    </div>
                    <div style="background:linear-gradient(135deg, rgba(255,255,255,.25) 0%, rgba(255,255,255,.15) 100%); padding:12px 14px; border-radius:10px; backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,.2);">
                        <p style="margin:0 0 4px 0; font-weight:600; font-size:13px; color:#fff; line-height:1.4; word-break:break-word;"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p style="margin:0; font-size:11px; color:rgba(255,255,255,.85); font-weight:400; text-transform:capitalize; line-height:1.3;"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=dashboard" class="nav-item">
                    <span class="nav-icon">📊</span><span>Dashboard</span>
                </a>
                <?php if (in_array($user['role'], ['administrator','manager','finance'])): ?>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=input_barang" class="nav-item">
                    <span class="nav-icon">📥</span><span>Input Barang</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock_masuk.php" class="nav-item active">
                    <span class="nav-icon">📥</span><span>Stock Masuk</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock_keluar.php" class="nav-item">
                    <span class="nav-icon">📤</span><span>Stock Keluar</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=input_penjualan" class="nav-item">
                    <span class="nav-icon">💰</span><span>Input Penjualan</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/input_penjualan_outlet.php" class="nav-item">
                    <span class="nav-icon">🏪</span><span>Input Penjualan Per Outlet</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock.php" class="nav-item">
                    <span class="nav-icon">📦</span><span>Stock</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_laporan.php" class="nav-item">
                    <span class="nav-icon">📋</span><span>Laporan Penjualan</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../../dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="../../logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>📥 Stock Masuk - Approval System</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #27ae60;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #e74c3c;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Summary Box -->
                <div class="summary-box" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 25px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);">
                    <h2 style="margin: 0 0 5px 0; font-size: 18px;">📊 Summary Stock Masuk</h2>
                    <p style="margin: 0 0 20px 0; opacity: 0.9;">Periode: <?php echo date('d M Y', strtotime($filter_start_date)); ?> - <?php echo date('d M Y', strtotime($filter_end_date)); ?></p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 10px; text-align: center;">
                            <h3 style="font-size: 24px; margin: 0 0 5px 0;"><?php echo $count_pending; ?></h3>
                            <p style="margin: 0; opacity: 0.9; font-size: 13px;">⏳ Pending</p>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 10px; text-align: center;">
                            <h3 style="font-size: 24px; margin: 0 0 5px 0;"><?php echo $count_approved; ?></h3>
                            <p style="margin: 0; opacity: 0.9; font-size: 13px;">✅ Approved</p>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 10px; text-align: center;">
                            <h3 style="font-size: 24px; margin: 0 0 5px 0;"><?php echo $count_rejected; ?></h3>
                            <p style="margin: 0; opacity: 0.9; font-size: 13px;">❌ Rejected</p>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 10px; text-align: center;">
                            <h3 style="font-size: 24px; margin: 0 0 5px 0;"><?php echo number_format($total_qty); ?></h3>
                            <p style="margin: 0; opacity: 0.9; font-size: 13px;">📦 Total Qty</p>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 10px; text-align: center;">
                            <h3 style="font-size: 20px; margin: 0 0 5px 0;">Rp <?php echo number_format($total_nilai / 1000, 0); ?>K</h3>
                            <p style="margin: 0; opacity: 0.9; font-size: 13px;">💰 Total Nilai</p>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Box -->
                <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 30px;">
                    <h2 style="margin: 0 0 20px 0; color: #2c3e50;">🔍 Filter Data</h2>
                    <form method="GET">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Tanggal Mulai</label>
                                <input type="date" name="start_date" value="<?php echo $filter_start_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Tanggal Akhir</label>
                                <input type="date" name="end_date" value="<?php echo $filter_end_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Cabang</label>
                                <select name="cabang_id" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                                    <option value="">-- Semua --</option>
                                    <?php 
                                    if ($cabang_list) {
                                        while ($cabang = $cabang_list->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $cabang['cabang_id']; ?>" <?php echo $filter_cabang == $cabang['cabang_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cabang['nama_cabang']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Produk</label>
                                <select name="produk_id" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                                    <option value="">-- Semua --</option>
                                    <?php 
                                    if ($produk_list) {
                                        while ($produk = $produk_list->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $produk['produk_id']; ?>" <?php echo $filter_produk == $produk['produk_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($produk['nama_produk']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Status</label>
                                <select name="status" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                                    <option value="">-- Semua Status --</option>
                                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                    <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>✅ Approved</option>
                                    <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>❌ Rejected</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn-add">🔍 Terapkan Filter</button>
                            <a href="inventory_stock_masuk" class="btn-cancel">🔄 Reset</a>
                        </div>
                    </form>
                </div>
                
                <!-- Data Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>📋 Data Stock Masuk</h2>
                        <div style="color: #7f8c8d; font-size: 14px;">
                            Menampilkan <?php echo count($stock_masuk_data); ?> dari <?php echo number_format($total_records); ?> transaksi
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Cabang Tujuan</th>
                                    <th>Cabang Asal</th>
                                    <th>Produk</th>
                                    <th>Qty</th>
                                    <th>Stock Sebelum</th>
                                    <th>Stock Sesudah</th>
                                    <th>Nilai</th>
                                    <th>Referensi</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stock_masuk_data)): ?>
                                    <?php 
                                    $no = $offset + 1;
                                    foreach ($stock_masuk_data as $item): 
                                        // Check if user can approve this item
                                        $can_approve = false;
                                        if (in_array($user['role'], ['administrator', 'manager'])) {
                                            $can_approve = true;
                                        } elseif (in_array($user['role'], ['admin', 'staff', 'supervisor', 'finance'])) {
                                            if ($item['cabang_id'] == $user['cabang_id']) {
                                                $can_approve = true;
                                            }
                                        }
                                    ?>
                                    <tr style="<?php echo $item['status_approval'] === 'pending' ? 'background: #fffbf0;' : ''; ?>">
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($item['tanggal'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['nama_cabang']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $cabang_asal_display = $item['cabang_asal'];
                                            if (!$cabang_asal_display && strpos($item['keterangan'], 'Pindah Gudang dari') !== false) {
                                                preg_match('/Pindah Gudang dari (.+?)(\||$)/', $item['keterangan'], $matches);
                                                if (isset($matches[1])) {
                                                    $cabang_asal_display = trim($matches[1]);
                                                }
                                            }
                                            
                                            if ($cabang_asal_display): 
                                            ?>
                                                <span style="background: #d1ecf1; color: #0c5460; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 600;">
                                                    ← <?php echo htmlspecialchars($cabang_asal_display); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #adb5bd;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['nama_produk']); ?></strong><br>
                                            <small style="color: #7f8c8d;"><?php echo htmlspecialchars($item['kategori']); ?></small>
                                        </td>
                                        <td>
                                            <span style="background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                                <strong>+<?php echo number_format($item['jumlah']); ?></strong>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: #6c757d; font-size: 14px;">
                                                <?php echo number_format($item['stok_sebelum']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: #27ae60; font-weight: 600; font-size: 14px;">
                                                <?php echo number_format($item['stok_sesudah']); ?>
                                            </span>
                                        </td>
                                        <td><strong>Rp <?php echo number_format($item['jumlah'] * $item['harga'], 0, ',', '.'); ?></strong></td>
                                        <td>
                                            <?php if ($item['referensi']): ?>
                                                <small style="color: #27ae60; font-weight: 500;"><?php echo htmlspecialchars($item['referensi']); ?></small>
                                            <?php else: ?>
                                                <small style="color: #adb5bd;">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($item['status_approval'] === 'pending'): ?>
                                                <span class="badge-pending">⏳ Pending</span>
                                            <?php elseif ($item['status_approval'] === 'approved'): ?>
                                                <span class="badge-approved">✅ Approved</span>
                                            <?php else: ?>
                                                <span class="badge-rejected">❌ Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($item['status_approval'] === 'pending' && $can_approve): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Approve stock masuk ini?');">
                                                    <input type="hidden" name="inventory_id" value="<?php echo $item['inventory_id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn-approve">✓ Approve</button>
                                                </form>
                                                <form method="POST" style="display: inline; margin-left: 5px;" onsubmit="return confirm('Reject stock masuk ini?');">
                                                    <input type="hidden" name="inventory_id" value="<?php echo $item['inventory_id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn-reject">✗ Reject</button>
                                                </form>
                                            <?php elseif ($item['status_approval'] === 'pending'): ?>
                                                <small style="color: #856404;">🔒 No Access</small>
                                            <?php else: ?>
                                                <small style="color: #7f8c8d;">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                            <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                            <strong>Tidak ada data stock masuk</strong><br>
                                            <small>Coba ubah filter atau range tanggal</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                        <?php
                        $query_params = [
                            'start_date' => $filter_start_date,
                            'end_date' => $filter_end_date,
                            'cabang_id' => $filter_cabang,
                            'produk_id' => $filter_produk,
                            'status' => $filter_status
                        ];
                        $query_string = http_build_query(array_filter($query_params));
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo $query_string; ?>&page_num=<?php echo $page - 1; ?>" style="padding: 8px 16px; border-radius: 8px; text-decoration: none; color: #495057; font-weight: 500;">← Previous</a>
                        <?php else: ?>
                            <span style="padding: 8px 16px; opacity: 0.5; color: #adb5bd;">← Previous</span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span style="padding: 8px 16px; border-radius: 8px; background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; font-weight: 600;"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo $query_string; ?>&page_num=<?php echo $i; ?>" style="padding: 8px 16px; border-radius: 8px; text-decoration: none; color: #495057; font-weight: 500;"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo $query_string; ?>&page_num=<?php echo $page + 1; ?>" style="padding: 8px 16px; border-radius: 8px; text-decoration: none; color: #495057; font-weight: 500;">Next →</a>
                        <?php else: ?>
                            <span style="padding: 8px 16px; opacity: 0.5; color: #adb5bd;">Next →</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info Box -->
                <div style="background: #d4edda; padding: 20px; border-radius: 12px; margin-top: 30px; border-left: 4px solid #27ae60;">
                    <h3 style="margin: 0 0 10px 0; color: #155724;">ℹ️ Informasi Approval System</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #155724;">
                        <li><strong>Pending (⏳)</strong>: Menunggu approval. Stock BELUM ditambahkan.</li>
                        <li><strong>Approved (✅)</strong>: Sudah disetujui. Stock SUDAH ditambahkan ke cabang tujuan.</li>
                        <li><strong>Rejected (❌)</strong>: Ditolak. Stock TIDAK ditambahkan.</li>
                        <li>Hanya user cabang tujuan, Administrator, dan Manager yang bisa approve/reject.</li>
                        <li>Setelah approved, stock akan otomatis bertambah di cabang tujuan.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
