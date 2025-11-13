<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Handle Stock Keluar Form Submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'stock_keluar') {
    $tanggal = $_POST['tanggal'];
    $produk_id = $_POST['produk_id'];
    $qty = $_POST['qty'];
    $alasan = $_POST['alasan'];
    $keterangan = $_POST['keterangan'] ?? '';
    $cabang_tujuan_id = $_POST['cabang_tujuan_id'] ?? null;
    
    // Get cabang_id (cabang asal) based on user role
    if (in_array($user['role'], ['admin', 'staff', 'supervisor', 'finance'])) {
        $cabang_id = $user['cabang_id'];
    } else {
        $cabang_id = $_POST['cabang_id'] ?? null;
    }
    
    // Get current stock from produk table (global stock)
    $stmt = $conn->prepare("SELECT nama_produk, stok FROM produk WHERE produk_id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Produk tidak ditemukan!";
    } else {
        $produk = $result->fetch_assoc();
        
        // Calculate stock at cabang asal from approved transactions
        if ($cabang_id) {
            $stmt_cabang_stock = $conn->prepare("SELECT 
                COALESCE(SUM(CASE WHEN tipe_transaksi = 'masuk' THEN jumlah ELSE -jumlah END), 0) as cabang_stock
                FROM inventory 
                WHERE produk_id = ? 
                AND cabang_id = ? 
                AND status_approval = 'approved'");
            $stmt_cabang_stock->bind_param("ii", $produk_id, $cabang_id);
            $stmt_cabang_stock->execute();
            $result_cabang = $stmt_cabang_stock->get_result();
            $stok_sebelum = $result_cabang->fetch_assoc()['cabang_stock'];
        } else {
            $stok_sebelum = $produk['stok'];
        }
        
        // For approval system: we don't check stock availability here
        // Stock will only be reduced when approved
        
        // Generate reference number
        $referensi = 'KELUAR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Build keterangan with cabang tujuan info
        $full_keterangan = "Stock Keluar - Alasan: " . $alasan;
        
        // Add cabang tujuan info if provided
        if ($cabang_tujuan_id) {
            $stmt_tujuan = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
            $stmt_tujuan->bind_param("i", $cabang_tujuan_id);
            $stmt_tujuan->execute();
            $result_tujuan = $stmt_tujuan->get_result();
            if ($result_tujuan->num_rows > 0) {
                $cabang_tujuan = $result_tujuan->fetch_assoc();
                $full_keterangan .= " | Tujuan: " . $cabang_tujuan['nama_cabang'];
            }
        }
        
        if (!empty($keterangan)) {
            $full_keterangan .= " | " . $keterangan;
        }
        
        // Insert stock keluar record (stok_sebelum and stok_sesudah are for reference only, not used for actual stock update)
        $stok_sesudah = $stok_sebelum; // Keep same for pending status
        
        if ($cabang_id) {
            $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id, cabang_id) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, 'pending', ?, ?)");
            $stmt->bind_param("isiisssii", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id'], $cabang_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->bind_param("isiisssi", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id']);
        }
        
        if ($stmt->execute()) {
            // If cabang tujuan is provided, create stock masuk record
            if ($cabang_tujuan_id) {
                // Get stock at destination cabang (independent from source)
                $stmt_dest_stock = $conn->prepare("SELECT stok FROM produk WHERE produk_id = ?");
                $stmt_dest_stock->bind_param("i", $produk_id);
                $stmt_dest_stock->execute();
                $result_dest_stock = $stmt_dest_stock->get_result();
                $dest_stok_sebelum = 0;
                if ($result_dest_stock->num_rows > 0) {
                    $dest_produk = $result_dest_stock->fetch_assoc();
                    $dest_stok_sebelum = $dest_produk['stok'];
                }
                $dest_stok_sesudah = $dest_stok_sebelum; // Keep same for pending status
                
                $keterangan_masuk = "Stock Masuk - Pindah Gudang dari " . ($cabang_id ? "Cabang" : "Pusat") . " | Ref: " . $referensi;
                
                $stmt_masuk = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id, cabang_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?, 'pending', ?, ?)");
                $stmt_masuk->bind_param("isiisssii", $produk_id, $tanggal, $qty, $dest_stok_sebelum, $dest_stok_sesudah, $referensi, $keterangan_masuk, $user['user_id'], $cabang_tujuan_id);
                $stmt_masuk->execute();
            }
            
            $message = "✅ Stock keluar berhasil diajukan! Ref: " . $referensi . " | Produk: " . $produk['nama_produk'] . " | Qty: " . $qty . " | Status: PENDING (Menunggu Approval)";
        } else {
            $error = "Gagal mengajukan stock keluar: " . $conn->error;
        }
    }
}

// Get filter parameters
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';
$filter_produk = isset($_GET['produk_id']) ? $_GET['produk_id'] : '';
$filter_alasan = isset($_GET['alasan']) ? $_GET['alasan'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["i.tipe_transaksi = 'keluar'"];
$params = [];
$types = "";

$where_conditions[] = "i.tanggal BETWEEN ? AND ?";
$params[] = $filter_start_date;
$params[] = $filter_end_date;
$types .= "ss";

if (!in_array($user['role'], ['administrator', 'manager'])) {
    $where_conditions[] = "(i.cabang_id = ? OR i.cabang_id IS NULL)";
    $params[] = $user['cabang_id'];
    $types .= "i";
}

if (!empty($filter_cabang)) {
    $where_conditions[] = "i.cabang_id = ?";
    $params[] = $filter_cabang;
    $types .= "i";
}

if (!empty($filter_produk)) {
    $where_conditions[] = "i.produk_id = ?";
    $params[] = $filter_produk;
    $types .= "i";
}

if (!empty($filter_alasan)) {
    $where_conditions[] = "i.keterangan LIKE ?";
    $params[] = "%Alasan: " . $filter_alasan . "%";
    $types .= "s";
}

if (!empty($filter_status)) {
    $where_conditions[] = "i.status_approval = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM inventory i WHERE " . $where_clause;
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get stock keluar data
$stock_keluar_query = "SELECT 
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
        SELECT COALESCE(SUM(CASE WHEN ii.tipe_transaksi='masuk' THEN ii.jumlah ELSE -ii.jumlah END),0)
        FROM inventory ii
        WHERE ii.produk_id = i.produk_id
          AND ((i.cabang_id IS NOT NULL AND ii.cabang_id = i.cabang_id) OR (i.cabang_id IS NULL AND ii.cabang_id IS NULL))
          AND ii.status_approval = 'approved'
          AND (ii.tanggal < i.tanggal OR (ii.tanggal = i.tanggal AND ii.inventory_id < i.inventory_id))
    ) AS stok_sebelum_cabang,
    CASE 
        WHEN i.status_approval = 'approved' THEN 
            (
                SELECT COALESCE(SUM(CASE WHEN ii.tipe_transaksi='masuk' THEN ii.jumlah ELSE -ii.jumlah END),0)
                FROM inventory ii
                WHERE ii.produk_id = i.produk_id
                  AND ((i.cabang_id IS NOT NULL AND ii.cabang_id = i.cabang_id) OR (i.cabang_id IS NULL AND ii.cabang_id IS NULL))
                  AND ii.status_approval = 'approved'
                  AND (ii.tanggal < i.tanggal OR (ii.tanggal = i.tanggal AND ii.inventory_id < i.inventory_id))
            ) - i.jumlah
        ELSE 
            (
                SELECT COALESCE(SUM(CASE WHEN ii.tipe_transaksi='masuk' THEN ii.jumlah ELSE -ii.jumlah END),0)
                FROM inventory ii
                WHERE ii.produk_id = i.produk_id
                  AND ((i.cabang_id IS NOT NULL AND ii.cabang_id = i.cabang_id) OR (i.cabang_id IS NULL AND ii.cabang_id IS NULL))
                  AND ii.status_approval = 'approved'
                  AND (ii.tanggal < i.tanggal OR (ii.tanggal = i.tanggal AND ii.inventory_id < i.inventory_id))
            )
    END AS stok_sesudah_cabang
FROM inventory i
LEFT JOIN produk p ON i.produk_id = p.produk_id
LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
LEFT JOIN users u ON i.user_id = u.user_id
WHERE " . $where_clause . "
ORDER BY i.status_approval ASC, i.tanggal DESC, i.inventory_id DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($stock_keluar_query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stock_keluar_result = $stmt->get_result();

$stock_keluar_data = [];
$total_qty = 0;
$total_nilai = 0;
$count_pending = 0;
$count_approved = 0;
$count_rejected = 0;

while ($row = $stock_keluar_result->fetch_assoc()) {
    $stock_keluar_data[] = $row;
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
$produk_list_filter = $conn->query("SELECT produk_id, nama_produk FROM produk WHERE status = 'active' ORDER BY nama_produk");

// Get produk list for form (with stock per cabang)
if (in_array($user['role'], ['administrator', 'manager'])) {
    // For admin/manager, show global stock (they can select any cabang)
    $produk_list = $conn->query("SELECT produk_id, nama_produk, stok FROM produk WHERE status = 'active' ORDER BY nama_produk");
} else {
    // For other users, show stock at their cabang
    $produk_query = "SELECT 
        p.produk_id, 
        p.nama_produk,
        COALESCE(SUM(CASE WHEN i.tipe_transaksi = 'masuk' THEN i.jumlah ELSE -i.jumlah END), 0) as stok_cabang
        FROM produk p
        LEFT JOIN inventory i ON p.produk_id = i.produk_id 
            AND i.cabang_id = ? 
            AND i.status_approval = 'approved'
        WHERE p.status = 'active'
        GROUP BY p.produk_id, p.nama_produk
        ORDER BY p.nama_produk";
    $stmt = $conn->prepare($produk_query);
    $stmt->bind_param("i", $user['cabang_id']);
    $stmt->execute();
    $produk_list = $stmt->get_result();
}

// Get cabang list for form (cabang asal)
$cabang_list_form = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");

// Get cabang list for cabang tujuan
$cabang_list_tujuan = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");

// Get user's cabang name for non-admin users (for form display)
$user_cabang_name = '';
if (!in_array($user['role'], ['administrator', 'manager'])) {
    $stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
    $stmt->bind_param("i", $user['cabang_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $cabang = $result->fetch_assoc();
        $user_cabang_name = $cabang['nama_cabang'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Keluar - Approval System</title>
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
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Inventory</h2>
                <p><?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="inventory.php?page=dashboard" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <?php if (in_array($user['role'], ['administrator', 'manager', 'finance'])): ?>
                <a href="inventory.php?page=input_barang" class="nav-item">
                    <span class="nav-icon">📥</span>
                    <span>Input Barang</span>
                </a>
                <?php endif; ?>
                <a href="inventory_stock_masuk.php" class="nav-item">
                    <span class="nav-icon">📥</span>
                    <span>Stock Masuk</span>
                </a>
                <a href="inventory_stock_keluar.php" class="nav-item active">
                    <span class="nav-icon">📤</span>
                    <span>Stock Keluar</span>
                </a>
                <a href="inventory.php?page=input_penjualan" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Input Penjualan</span>
                </a>
                <a href="input_penjualan_outlet.php" class="nav-item">
                    <span class="nav-icon">🏪</span>
                    <span>Input Penjualan Per Outlet</span>
                </a>
                <a href="inventory_stock.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Stock Information</span>
                </a>
                <a href="inventory_laporan.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>Laporan Penjualan</span>
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
                <h1>📤 Riwayat Stock Keluar</h1>
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
                
                <!-- Form Input Stock Keluar -->
                <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 30px;">
                    <h2 style="color: #2c3e50; margin: 0 0 20px 0;">📤 Form Input Stock Keluar</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="stock_keluar">
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Tanggal</label>
                                <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Cabang Asal</label>
                                <select name="cabang_id" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                                    <option value="">-- Pilih Cabang --</option>
                                    <?php 
                                    if ($cabang_list_form) {
                                        while ($c = $cabang_list_form->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $c['cabang_id']; ?>">
                                            <?php echo htmlspecialchars($c['nama_cabang']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Cabang Asal</label>
                                <input type="text" value="<?php echo htmlspecialchars($user_cabang_name); ?>" readonly style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; background: #f8f9fa; cursor: not-allowed; font-size: 14px;">
                            </div>
                            <?php endif; ?>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Produk</label>
                                <select name="produk_id" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                                    <option value="">-- Pilih Produk --</option>
                                    <?php 
                                    if ($produk_list) {
                                        $produk_list->data_seek(0);
                                        while ($p = $produk_list->fetch_assoc()): 
                                            $stok_display = isset($p['stok_cabang']) ? $p['stok_cabang'] : $p['stok'];
                                    ?>
                                        <option value="<?php echo $p['produk_id']; ?>">
                                            <?php echo htmlspecialchars($p['nama_produk']); ?> (Stok: <?php echo $stok_display; ?>)
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Quantity</label>
                                <input type="number" name="qty" min="1" required placeholder="Jumlah" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Alasan</label>
                                <select name="alasan" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                                    <option value="">-- Pilih Alasan --</option>
                                    <option value="Pindah Gudang">Pindah Gudang</option>
                                    <option value="Rusak">Rusak</option>
                                    <option value="Hilang">Hilang</option>
                                    <option value="Return">Return</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Cabang Tujuan</label>
                                <select name="cabang_tujuan_id" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                                    <option value="">-- Pilih Cabang Tujuan (Opsional) --</option>
                                    <?php 
                                    if ($cabang_list_tujuan) {
                                        while ($ct = $cabang_list_tujuan->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $ct['cabang_id']; ?>">
                                            <?php echo htmlspecialchars($ct['nama_cabang']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                                <small style="color: #7f8c8d; font-size: 12px;">Pilih jika untuk pindah gudang</small>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; font-size: 14px;">Keterangan</label>
                                <input type="text" name="keterangan" placeholder="Keterangan tambahan (opsional)" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn-add">💾 Simpan Stock Keluar</button>
                            <button type="reset" class="btn-cancel">🔄 Reset Form</button>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Box -->
                <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 25px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);">
                    <h2 style="margin: 0 0 5px 0; font-size: 18px;">📊 Summary Stock Keluar</h2>
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
                                    if ($produk_list_filter) {
                                        while ($produk = $produk_list_filter->fetch_assoc()): 
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
                            <a href="inventory_stock_keluar.php" class="btn-cancel">🔄 Reset</a>
                        </div>
                    </form>
                </div>
                
                <!-- Data Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>📋 Data Stock Keluar</h2>
                        <div style="color: #7f8c8d; font-size: 14px;">
                            Menampilkan <?php echo count($stock_keluar_data); ?> dari <?php echo number_format($total_records); ?> transaksi
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Cabang Asal</th>
                                    <th>Cabang Tujuan</th>
                                    <th>Produk</th>
                                    <th>Qty</th>
                                    <th>Stock Sebelum</th>
                                    <th>Stock Sesudah</th>
                                    <th>Nilai</th>
                                    <th>Alasan</th>
                                    <th>Referensi</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stock_keluar_data)): ?>
                                    <?php 
                                    $no = $offset + 1;
                                    foreach ($stock_keluar_data as $item): 
                                        // Check if user can approve
                                        $can_approve = false;
                                        if (in_array($user['role'], ['administrator', 'manager'])) {
                                            $can_approve = true;
                                        } elseif (in_array($user['role'], ['admin', 'staff', 'supervisor', 'finance'])) {
                                            if ($item['cabang_id'] == $user['cabang_id']) {
                                                $can_approve = true;
                                            }
                                        }
                                        
                                        // Extract alasan from keterangan
                                        $alasan = '-';
                                        if (preg_match('/Alasan: ([^|]+)/', $item['keterangan'], $matches)) {
                                            $alasan = trim($matches[1]);
                                        }
                                        
                                        // Extract cabang tujuan from keterangan
                                        $cabang_tujuan = '-';
                                        if (preg_match('/Tujuan: ([^|]+)/', $item['keterangan'], $matches)) {
                                            $cabang_tujuan = trim($matches[1]);
                                        }
                                    ?>
                                    <tr style="<?php echo $item['status_approval'] === 'pending' ? 'background: #fff8f0;' : ''; ?>">
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($item['tanggal'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['nama_cabang']); ?></strong></td>
                                        <td>
                                            <?php if ($cabang_tujuan !== '-'): ?>
                                                <span style="background: #d1ecf1; color: #0c5460; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 600;">
                                                    → <?php echo htmlspecialchars($cabang_tujuan); ?>
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
                                            <span style="background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                                <strong>-<?php echo number_format($item['jumlah']); ?></strong>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: #6c757d; font-size: 14px;" title="Per-cabang computed">
                                                <?php echo number_format($item['stok_sebelum_cabang']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: #e74c3c; font-weight: 600; font-size: 14px;" title="Per-cabang computed">
                                                <?php echo number_format($item['stok_sesudah_cabang']); ?>
                                            </span>
                                        </td>
                                        <td><strong>Rp <?php echo number_format($item['jumlah'] * $item['harga'], 0, ',', '.'); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars($alasan); ?></small></td>
                                        <td>
                                            <?php if ($item['referensi']): ?>
                                                <small style="color: #e74c3c; font-weight: 500;"><?php echo htmlspecialchars($item['referensi']); ?></small>
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
                                            <small style="color: #7f8c8d;">
                                                <?php if ($item['status_approval'] === 'pending'): ?>
                                                    Menunggu approval di cabang tujuan
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="13" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                            <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                            <strong>Tidak ada data stock keluar</strong><br>
                                            <small>Coba ubah filter atau range tanggal</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                        <?php
                        $query_params = [
                            'start_date' => $filter_start_date,
                            'end_date' => $filter_end_date,
                            'cabang_id' => $filter_cabang,
                            'produk_id' => $filter_produk,
                            'alasan' => $filter_alasan,
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
                                <span style="padding: 8px 16px; border-radius: 8px; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; font-weight: 600;"><?php echo $i; ?></span>
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
                <div style="background: #f8d7da; padding: 20px; border-radius: 12px; margin-top: 30px; border-left: 4px solid #e74c3c;">
                    <h3 style="margin: 0 0 10px 0; color: #721c24;">ℹ️ Informasi Stock Keluar</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #721c24;">
                        <li><strong>Pending (⏳)</strong>: Menunggu approval di cabang tujuan. Stock BELUM dikurangi.</li>
                        <li><strong>Approved (✅)</strong>: Sudah diapprove di cabang tujuan. Stock SUDAH dikurangi.</li>
                        <li><strong>Rejected (❌)</strong>: Ditolak di cabang tujuan. Stock TIDAK dikurangi.</li>
                        <li>Halaman ini hanya menampilkan status, tidak ada action approve/reject.</li>
                        <li>Approval dilakukan di halaman <strong>Stock Masuk</strong> oleh cabang tujuan.</li>
                        <li>Setelah cabang tujuan approve, stock akan otomatis berkurang di cabang asal.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
