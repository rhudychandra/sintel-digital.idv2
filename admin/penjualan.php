<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

if ($user['role'] !== 'administrator') {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getDBConnection();

// Get all sales data with error handling
$sales = [];
$error_message = '';

try {
    $query = "SELECT 
                p.penjualan_id,
                p.no_invoice,
                p.tanggal_penjualan,
                p.total AS total_harga,
                p.status_pembayaran,
                COALESCE(c.nama_cabang, '-') AS nama_cabang,
                COALESCE(pel.nama_pelanggan, '-') AS nama_pelanggan,
                COALESCE(u.full_name, '-') AS sales_person,
                COALESCE(r.nama_reseller, '-') AS nama_reseller
              FROM penjualan p 
              LEFT JOIN cabang c ON p.cabang_id = c.cabang_id 
              LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.pelanggan_id 
              LEFT JOIN users u ON p.user_id = u.user_id 
              LEFT JOIN reseller r ON p.reseller_id = r.reseller_id 
              ORDER BY p.tanggal_penjualan DESC 
              LIMIT 100";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
        }
    } else {
        $error_message = "Error loading data: " . $conn->error;
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penjualan - Administrator Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin-styles.css">
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
                <a href="penjualan.php" class="nav-item active">
                    <span class="nav-icon">💰</span>
                    <span>Penjualan</span>
                </a>
                <a href="inventory.php" class="nav-item">
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
                <h1>Data Penjualan Semua Cabang</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($error_message): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                    <br><small>Pastikan tabel cabang dan reseller sudah dibuat. Jalankan file fix_admin_menu_tables.sql</small>
                </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar Penjualan</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Cabang</th>
                                <th>Pelanggan</th>
                                <th>Sales Person</th>
                                <th>Reseller</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo $sale['penjualan_id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($sale['tanggal_penjualan'])); ?></td>
                                <td><?php echo htmlspecialchars($sale['nama_cabang']); ?></td>
                                <td><?php echo htmlspecialchars($sale['nama_pelanggan']); ?></td>
                                <td><?php echo htmlspecialchars($sale['sales_person']); ?></td>
                                <td><?php echo htmlspecialchars($sale['nama_reseller']); ?></td>
                                <td>Rp <?php echo number_format($sale['total_harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?php echo $sale['status_pembayaran'] === 'paid' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo ucfirst($sale['status_pembayaran']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                    Belum ada data penjualan
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
