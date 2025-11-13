<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

// Check if user is administrator
if ($user['role'] !== 'administrator') {
    header('Location: ../dashboard.php');
    exit();
}

// Get dashboard statistics with error handling
$conn = getDBConnection();

$stats = [];
$error_message = '';

try {
    // Try to use view first
    $result = $conn->query("SELECT * FROM view_admin_dashboard");
    
    if ($result && $result->num_rows > 0) {
        $stats = $result->fetch_assoc();
    } else {
        // Fallback: Calculate statistics manually
        $stats = [
            'total_cabang' => 0,
            'total_reseller' => 0,
            'total_users' => 0,
            'total_produk' => 0,
            'total_penjualan' => 0,
            'total_stok' => 0
        ];
        
        // Get total cabang
        $result = $conn->query("SELECT COUNT(*) as total FROM cabang WHERE status='active'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_cabang'] = $row['total'];
        }
        
        // Get total reseller
        $result = $conn->query("SELECT COUNT(*) as total FROM reseller WHERE status='active'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_reseller'] = $row['total'];
        }
        
        // Get total users
        $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status='active'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_users'] = $row['total'];
        }
        
        // Get total produk
        $result = $conn->query("SELECT COUNT(*) as total FROM produk WHERE status='active'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_produk'] = $row['total'];
        }
        
        // Get total penjualan
        $result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM penjualan WHERE status_pembayaran='paid'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_penjualan'] = $row['total'];
        }
        
        // Get total stok
        $result = $conn->query("SELECT COALESCE(SUM(stok), 0) as total FROM produk WHERE status='active'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_stok'] = $row['total'];
        }
    }
} catch (Exception $e) {
    $error_message = "Error loading statistics: " . $e->getMessage();
    $stats = [
        'total_cabang' => 0,
        'total_reseller' => 0,
        'total_users' => 0,
        'total_produk' => 0,
        'total_penjualan' => 0,
        'total_stok' => 0
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Panel - Sinar Telkom Dashboard System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-styles.css?v=<?php echo time(); ?>">
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
                <a href="index.php" class="nav-item active">
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
                <h1>Dashboard Administrator</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($error_message): ?>
                <div class="alert alert-warning" style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                    <br><small>Beberapa data mungkin tidak akurat. Jalankan file fix_admin_menu_tables.sql untuk hasil optimal.</small>
                </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">🏢</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_cabang'] ?? 0; ?></h3>
                            <p>Total Cabang</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #9b59b6;">🤝</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_reseller'] ?? 0; ?></h3>
                            <p>Total Reseller</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">👥</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_users'] ?? 0; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">📦</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_produk'] ?? 0; ?></h3>
                            <p>Total Produk</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #27ae60;">💰</div>
                        <div class="stat-info">
                            <h3>Rp <?php echo number_format($stats['total_penjualan'] ?? 0, 0, ',', '.'); ?></h3>
                            <p>Total Penjualan</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #16a085;">📈</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_stok'] ?? 0, 0, ',', '.'); ?></h3>
                            <p>Total Stock</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="action-grid">
                        <a href="produk.php?action=add" class="action-card">
                            <span class="action-icon">➕</span>
                            <span>Tambah Produk</span>
                        </a>
                        <a href="cabang.php?action=add" class="action-card">
                            <span class="action-icon">➕</span>
                            <span>Tambah Cabang</span>
                        </a>
                        <a href="users.php?action=add" class="action-card">
                            <span class="action-icon">➕</span>
                            <span>Tambah User</span>
                        </a>
                        <a href="reseller.php?action=add" class="action-card">
                            <span class="action-icon">➕</span>
                            <span>Tambah Reseller</span>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h2>Menu Administrator</h2>
                    <div class="menu-grid">
                        <a href="produk.php" class="menu-card">
                            <div class="menu-icon">📦</div>
                            <h3>Produk</h3>
                            <p>Kelola data produk</p>
                        </a>
                        <a href="cabang.php" class="menu-card">
                            <div class="menu-icon">🏢</div>
                            <h3>Cabang</h3>
                            <p>Kelola data cabang</p>
                        </a>
                        <a href="users.php" class="menu-card">
                            <div class="menu-icon">👥</div>
                            <h3>Users</h3>
                            <p>Kelola data users</p>
                        </a>
                        <a href="reseller.php" class="menu-card">
                            <div class="menu-icon">🤝</div>
                            <h3>Reseller</h3>
                            <p>Kelola data reseller</p>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
