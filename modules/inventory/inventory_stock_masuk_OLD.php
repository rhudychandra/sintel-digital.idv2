<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Get filter parameters
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';
$filter_produk = isset($_GET['produk_id']) ? $_GET['produk_id'] : '';

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

// Get stock masuk data with cabang asal (from related stock keluar for pindah gudang)
$stock_masuk_query = "SELECT 
    i.inventory_id,
    i.tanggal,
    i.jumlah,
    i.stok_sebelum,
    i.stok_sesudah,
    i.referensi,
    i.keterangan,
    p.nama_produk,
    p.kategori,
    p.harga,
    COALESCE(c.nama_cabang, 'Pusat/Global') as nama_cabang,
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
ORDER BY i.tanggal DESC, i.inventory_id DESC
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

while ($row = $stock_masuk_result->fetch_assoc()) {
    $stock_masuk_data[] = $row;
    $total_qty += $row['jumlah'];
    $total_nilai += $row['jumlah'] * $row['harga'];
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
    <title>Stock Masuk - Inventory System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
    <style>
        .filter-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .badge-masuk {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .summary-item h3 {
            font-size: 28px;
            margin: 0 0 5px 0;
        }
        
        .summary-item p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
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
                <a href="inventory_stock_masuk.php" class="nav-item active">
                    <span class="nav-icon">📥</span>
                    <span>Stock Masuk</span>
                </a>
                <a href="inventory_stock_keluar.php" class="nav-item">
                    <span class="nav-icon">📤</span>
                    <span>Stock Keluar</span>
                </a>
                <a href="inventory.php?page=input_penjualan" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Input Penjualan</span>
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
                <h1>📥 Riwayat Stock Masuk</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <!-- Summary Box -->
                <div class="summary-box">
                    <h2 style="margin: 0 0 5px 0; font-size: 18px;">📊 Summary Stock Masuk</h2>
                    <p style="margin: 0 0 20px 0; opacity: 0.9;">Periode: <?php echo date('d M Y', strtotime($filter_start_date)); ?> - <?php echo date('d M Y', strtotime($filter_end_date)); ?></p>
                    
                    <div class="summary-grid">
                        <div class="summary-item">
                            <h3><?php echo number_format(count($stock_masuk_data)); ?></h3>
                            <p>Total Transaksi</p>
                        </div>
                        <div class="summary-item">
                            <h3><?php echo number_format($total_qty); ?></h3>
                            <p>Total Quantity</p>
                        </div>
                        <div class="summary-item">
                            <h3>Rp <?php echo number_format($total_nilai, 0, ',', '.'); ?></h3>
                            <p>Total Nilai</p>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Box -->
                <div class="filter-box">
                    <h2 style="margin: 0 0 20px 0; color: #2c3e50;">🔍 Filter Data</h2>
                    <form method="GET">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label>Tanggal Mulai</label>
                                <input type="date" name="start_date" value="<?php echo $filter_start_date; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label>Tanggal Akhir</label>
                                <input type="date" name="end_date" value="<?php echo $filter_end_date; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label>Cabang</label>
                                <select name="cabang_id">
                                    <option value="">-- Semua Cabang --</option>
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
                            
                            <div class="filter-group">
                                <label>Produk</label>
                                <select name="produk_id">
                                    <option value="">-- Semua Produk --</option>
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
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" class="btn-add">🔍 Terapkan Filter</button>
                            <a href="inventory_stock_masuk.php" class="btn-cancel">🔄 Reset</a>
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
                                    <th>Kategori</th>
                                    <th>Qty</th>
                                    <th>Stok Sebelum</th>
                                    <th>Stok Sesudah</th>
                                    <th>Nilai</th>
                                    <th>Referensi</th>
                                    <th>Keterangan</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stock_masuk_data)): ?>
                                    <?php 
                                    $no = $offset + 1;
                                    foreach ($stock_masuk_data as $item): 
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($item['tanggal'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['nama_cabang']); ?></strong></td>
                                        <td>
                                            <?php 
                                            // Extract cabang asal from subquery or keterangan
                                            $cabang_asal_display = $item['cabang_asal'];
                                            
                                            // If subquery didn't find it, try to extract from keterangan
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
                                        <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                                        <td><?php echo htmlspecialchars($item['kategori']); ?></td>
                                        <td>
                                            <span class="badge-masuk">
                                                <strong>+<?php echo number_format($item['jumlah']); ?></strong>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($item['stok_sebelum']); ?></td>
                                        <td><strong><?php echo number_format($item['stok_sesudah']); ?></strong></td>
                                        <td><strong>Rp <?php echo number_format($item['jumlah'] * $item['harga'], 0, ',', '.'); ?></strong></td>
                                        <td>
                                            <?php if ($item['referensi']): ?>
                                                <small style="color: #27ae60; font-weight: 500;"><?php echo htmlspecialchars($item['referensi']); ?></small>
                                            <?php else: ?>
                                                <small style="color: #adb5bd;">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($item['keterangan']); ?></small>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($item['user_name']); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="13" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                            <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                            <strong>Tidak ada data stock masuk</strong><br>
                                            <small>Coba ubah filter atau range tanggal</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($stock_masuk_data)): ?>
                            <tfoot>
                                <tr style="background: #f8f9fa; font-weight: 600;">
                                    <td colspan="6" style="text-align: right; padding: 15px;">TOTAL:</td>
                                    <td><strong><?php echo number_format($total_qty); ?></strong></td>
                                    <td colspan="2"></td>
                                    <td style="color: #27ae60;"><strong>Rp <?php echo number_format($total_nilai, 0, ',', '.'); ?></strong></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $query_params = [
                            'start_date' => $filter_start_date,
                            'end_date' => $filter_end_date,
                            'cabang_id' => $filter_cabang,
                            'produk_id' => $filter_produk
                        ];
                        $query_string = http_build_query(array_filter($query_params));
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo $query_string; ?>&page_num=<?php echo $page - 1; ?>">← Previous</a>
                        <?php else: ?>
                            <span class="disabled">← Previous</span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo $query_string; ?>&page_num=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo $query_string; ?>&page_num=<?php echo $page + 1; ?>">Next →</a>
                        <?php else: ?>
                            <span class="disabled">Next →</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info Box -->
                <div style="background: #d4edda; padding: 20px; border-radius: 12px; margin-top: 30px; border-left: 4px solid #27ae60;">
                    <h3 style="margin: 0 0 10px 0; color: #155724;">ℹ️ Informasi Stock Masuk</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #155724;">
                        <li>Halaman ini menampilkan <strong>riwayat stock yang masuk</strong> ke cabang</li>
                        <li>Stock masuk bisa berasal dari: DO Cluster, Supplier, Return, dll</li>
                        <li>Setiap transaksi stock masuk akan <strong>menambah jumlah stock</strong> produk</li>
                        <li>Gunakan filter untuk mempersempit pencarian data</li>
                        <li>Referensi menunjukkan nomor dokumen terkait (jika ada)</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
