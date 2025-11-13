<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

// Check if user is administrator
if ($user['role'] !== 'administrator') {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getDBConnection();

// Get filter parameters
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today
$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';
$filter_produk = isset($_GET['produk_id']) ? $_GET['produk_id'] : '';
$filter_tipe = isset($_GET['tipe_transaksi']) ? $_GET['tipe_transaksi'] : '';

// Pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get statistics for current month
$stats = [
    'total_transaksi' => 0,
    'total_masuk' => 0,
    'total_keluar' => 0,
    'nilai_inventory' => 0
];

try {
    $stats_query = "SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN i.tipe_transaksi='masuk' THEN i.jumlah ELSE 0 END) as total_masuk,
        SUM(CASE WHEN i.tipe_transaksi='keluar' THEN i.jumlah ELSE 0 END) as total_keluar,
        SUM(CASE WHEN i.tipe_transaksi='masuk' THEN i.jumlah * p.harga ELSE -i.jumlah * p.harga END) as nilai_inventory
    FROM inventory i
    LEFT JOIN produk p ON i.produk_id = p.produk_id
    WHERE MONTH(i.tanggal) = MONTH(CURRENT_DATE())
    AND YEAR(i.tanggal) = YEAR(CURRENT_DATE())";
    
    $result = $conn->query($stats_query);
    if ($result && $result->num_rows > 0) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    $error_message = "Error loading statistics: " . $e->getMessage();
}

// Get low stock products
$low_stock_products = [];
try {
    $low_stock_query = "SELECT 
        p.produk_id,
        p.nama_produk,
        p.stok,
        p.kategori,
        COALESCE(c.nama_cabang, '-') as nama_cabang
    FROM produk p
    LEFT JOIN inventory i ON p.produk_id = i.produk_id
    LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
    WHERE p.stok < 10
    AND p.status = 'active'
    GROUP BY p.produk_id, i.cabang_id
    ORDER BY p.stok ASC
    LIMIT 5";
    
    $result = $conn->query($low_stock_query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $low_stock_products[] = $row;
        }
    }
} catch (Exception $e) {
    // Silent fail for low stock
}

// Build query for inventory history with filters
$where_conditions = ["1=1"];
$params = [];
$types = "";

// Date filter
$where_conditions[] = "i.tanggal BETWEEN ? AND ?";
$params[] = $filter_start_date;
$params[] = $filter_end_date;
$types .= "ss";

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

// Tipe transaksi filter
if (!empty($filter_tipe)) {
    $where_conditions[] = "i.tipe_transaksi = ?";
    $params[] = $filter_tipe;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
    FROM inventory i 
    WHERE " . $where_clause;

$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get inventory history with filters - FIXED: Better cabang display
$inventory_query = "SELECT 
    i.inventory_id,
    i.tanggal,
    i.tipe_transaksi,
    i.jumlah,
    i.stok_sebelum,
    i.stok_sesudah,
    i.referensi,
    i.keterangan,
    i.cabang_id,
    p.nama_produk,
    p.kategori,
    p.harga,
    COALESCE(c.nama_cabang, 
        CASE 
            WHEN i.cabang_id IS NULL THEN 'Pusat/Global'
            ELSE '-'
        END
    ) as nama_cabang,
    u.full_name as user_name,
    uc.nama_cabang as user_cabang
FROM inventory i
LEFT JOIN produk p ON i.produk_id = p.produk_id
LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
LEFT JOIN users u ON i.user_id = u.user_id
LEFT JOIN cabang uc ON u.cabang_id = uc.cabang_id
WHERE " . $where_clause . "
ORDER BY i.tanggal DESC, i.inventory_id DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($inventory_query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$inventory_result = $stmt->get_result();

$inventory_data = [];
while ($row = $inventory_result->fetch_assoc()) {
    $inventory_data[] = $row;
}

// Get cabang list for filter dropdown
$cabang_list = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");

// Get produk list for filter dropdown
$produk_list = $conn->query("SELECT produk_id, nama_produk FROM produk WHERE status = 'active' ORDER BY nama_produk");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Administrator Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin-styles.css">
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
        
        .filter-group {
            display: flex;
            flex-direction: column;
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
            transition: border-color 0.3s;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-filter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
        }
        
        .btn-reset {
            background: #e9ecef;
            color: #495057;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-reset:hover {
            background: #dee2e6;
        }
        
        .badge-masuk {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-keluar {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .low-stock-alert {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .low-stock-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ffe69c;
        }
        
        .low-stock-item:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Administrator</h2>
                <p><?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="produk.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Produk</span>
                </a>
                <a href="cabang.php" class="nav-item">
                    <span class="nav-icon">🏢</span>
                    <span>Cabang</span>
                </a>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Users</span>
                </a>
                <a href="reseller.php" class="nav-item">
                    <span class="nav-icon">🤝</span>
                    <span>Reseller</span>
                </a>
                <a href="outlet.php" class="nav-item">
                    <span class="nav-icon">🏪</span>
                    <span>Outlet</span>
                </a>
                <a href="penjualan.php" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Penjualan</span>
                </a>
                <a href="inventory.php" class="nav-item active">
                    <span class="nav-icon">📋</span>
                    <span>Inventory</span>
                </a>
                <a href="stock.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span>Stock</span>
                </a>
                <a href="grafik.php" class="nav-item">
                    <span class="nav-icon">📉</span>
                    <span>Grafik</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>📋 Inventory Management</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">📊</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_transaksi'] ?? 0); ?></h3>
                            <p>Total Transaksi (Bulan Ini)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #27ae60;">📥</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_masuk'] ?? 0); ?></h3>
                            <p>Total Stok Masuk</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">📤</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_keluar'] ?? 0); ?></h3>
                            <p>Total Stok Keluar</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">💰</div>
                        <div class="stat-info">
                            <h3>Rp <?php echo number_format($stats['nilai_inventory'] ?? 0, 0, ',', '.'); ?></h3>
                            <p>Nilai Inventory</p>
                        </div>
                    </div>
                </div>
                
                <!-- Low Stock Alert -->
                <?php if (!empty($low_stock_products)): ?>
                <div class="low-stock-alert">
                    <h3 style="margin: 0 0 15px 0; color: #856404;">⚠️ Produk dengan Stok Rendah</h3>
                    <?php foreach ($low_stock_products as $product): ?>
                    <div class="low-stock-item">
                        <div>
                            <strong><?php echo htmlspecialchars($product['nama_produk']); ?></strong>
                            <small style="color: #856404; margin-left: 10px;"><?php echo htmlspecialchars($product['nama_cabang']); ?></small>
                        </div>
                        <span style="color: #dc3545; font-weight: 600;">Stok: <?php echo $product['stok']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Filter Box -->
                <div class="filter-box">
                    <h2 style="margin: 0 0 20px 0; color: #2c3e50;">🔍 Filter Riwayat Transaksi</h2>
                    <form method="GET" action="inventory.php">
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
                            
                            <div class="filter-group">
                                <label>Tipe Transaksi</label>
                                <select name="tipe_transaksi">
                                    <option value="">-- Semua Tipe --</option>
                                    <option value="masuk" <?php echo $filter_tipe == 'masuk' ? 'selected' : ''; ?>>Stok Masuk</option>
                                    <option value="keluar" <?php echo $filter_tipe == 'keluar' ? 'selected' : ''; ?>>Stok Keluar</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">🔍 Terapkan Filter</button>
                            <a href="inventory.php" class="btn-reset">🔄 Reset Filter</a>
                        </div>
                    </form>
                </div>
                
                <!-- Inventory History Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>📋 Riwayat Transaksi Inventory</h2>
                        <div style="color: #7f8c8d; font-size: 14px;">
                            Menampilkan <?php echo count($inventory_data); ?> dari <?php echo number_format($total_records); ?> transaksi
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Cabang</th>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Tipe</th>
                                    <th>Jumlah</th>
                                    <th>Stok Sebelum</th>
                                    <th>Stok Sesudah</th>
                                    <th>Nilai</th>
                                    <th>Referensi</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventory_data)): ?>
                                    <?php foreach ($inventory_data as $item): ?>
                                    <tr>
                                        <td><?php echo $item['inventory_id']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($item['tanggal'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['nama_cabang']); ?></strong>
                                            <?php if ($item['cabang_id'] === null && $item['user_cabang']): ?>
                                                <br><small style="color: #7f8c8d;">User: <?php echo htmlspecialchars($item['user_cabang']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                                        <td><?php echo htmlspecialchars($item['kategori']); ?></td>
                                        <td>
                                            <span class="badge-<?php echo $item['tipe_transaksi']; ?>">
                                                <?php echo $item['tipe_transaksi'] == 'masuk' ? '📥 Masuk' : '📤 Keluar'; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo number_format($item['jumlah']); ?></strong></td>
                                        <td><?php echo number_format($item['stok_sebelum']); ?></td>
                                        <td><?php echo number_format($item['stok_sesudah']); ?></td>
                                        <td>Rp <?php echo number_format($item['jumlah'] * $item['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($item['referensi']): ?>
                                                <small style="color: #667eea; font-weight: 500;"><?php echo htmlspecialchars($item['referensi']); ?></small>
                                            <?php else: ?>
                                                <small style="color: #adb5bd;">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($item['user_name']); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                            <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                            <strong>Tidak ada data transaksi</strong><br>
                                            <small>Coba ubah filter atau range tanggal</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Build query string for pagination
                        $query_params = [
                            'start_date' => $filter_start_date,
                            'end_date' => $filter_end_date,
                            'cabang_id' => $filter_cabang,
                            'produk_id' => $filter_produk,
                            'tipe_transaksi' => $filter_tipe
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
                <div style="background: #e7f3ff; padding: 20px; border-radius: 12px; margin-top: 30px; border-left: 4px solid #3498db;">
                    <h3 style="margin: 0 0 10px 0; color: #2c3e50;">ℹ️ Informasi</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #495057;">
                        <li>Data menampilkan semua transaksi inventory dari <strong>semua cabang</strong></li>
                        <li><strong>Stok Masuk (📥)</strong>: Penambahan stok dari supplier/pembelian</li>
                        <li><strong>Stok Keluar (📤)</strong>: Pengurangan stok dari penjualan</li>
                        <li>Referensi menunjukkan nomor invoice untuk transaksi penjualan</li>
                        <li>Gunakan filter untuk mempersempit pencarian data</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
