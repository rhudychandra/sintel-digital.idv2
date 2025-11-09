<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

if ($user['role'] !== 'administrator') {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getDBConnection();

// Get all stock data with error handling
$stocks = [];
$error_message = '';

try {
    // FIXED: Show stock per cabang (1 produk bisa ada di multiple cabang)
    if ($user['role'] === 'administrator') {
        // Administrator: Show stock breakdown per cabang
        $query = "SELECT 
                    i.inventory_id,
                    i.produk_id,
                    i.stok_sesudah as stok,
                    p.nama_produk,
                    p.kategori,
                    p.harga,
                    COALESCE(c.nama_cabang, 'Pusat/Global') AS nama_cabang,
                    i.tanggal as last_update
                  FROM inventory i
                  INNER JOIN produk p ON i.produk_id = p.produk_id
                  LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
                  WHERE i.inventory_id IN (
                      SELECT MAX(i2.inventory_id)
                      FROM inventory i2
                      GROUP BY i2.produk_id, COALESCE(i2.cabang_id, 0)
                  )
                  AND p.status = 'active'
                  ORDER BY c.nama_cabang, i.stok_sesudah ASC, p.nama_produk ASC";
        
        $result = $conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stocks[] = $row;
            }
        } else {
            $error_message = "Error loading data: " . $conn->error;
        }
    } else {
        // Other roles: Show stock from their cabang only
        $user_cabang_id = $user['cabang_id'] ?? null;
        
        if ($user_cabang_id) {
            $stmt = $conn->prepare("SELECT 
                        i.inventory_id,
                        i.produk_id,
                        i.stok_sesudah as stok,
                        p.nama_produk,
                        p.kategori,
                        p.harga,
                        c.nama_cabang,
                        i.tanggal as last_update
                      FROM inventory i
                      INNER JOIN produk p ON i.produk_id = p.produk_id
                      LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
                      WHERE i.cabang_id = ?
                      AND i.inventory_id IN (
                          SELECT MAX(i2.inventory_id)
                          FROM inventory i2
                          WHERE i2.cabang_id = ?
                          GROUP BY i2.produk_id
                      )
                      AND p.status = 'active'
                      ORDER BY i.stok_sesudah ASC, p.nama_produk ASC");
            $stmt->bind_param("ii", $user_cabang_id, $user_cabang_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $stocks[] = $row;
            }
            $stmt->close();
        } else {
            $error_message = "User tidak memiliki cabang yang terdaftar. Hubungi administrator.";
        }
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
    <title>Data Stock - Administrator Panel</title>
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
                <a href="penjualan.php" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Penjualan</span>
                </a>
                <a href="inventory.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>Inventory</span>
                </a>
                <a href="stock.php" class="nav-item active">
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
                <h1>Data Stock <?php echo $user['role'] === 'administrator' ? 'Semua Cabang' : 'Cabang Anda'; ?></h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($error_message): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                    <br><small>Pastikan tabel cabang sudah dibuat. Jalankan file fix_admin_menu_tables.sql</small>
                </div>
                <?php endif; ?>
                
                <?php if ($user['role'] === 'administrator'): ?>
                <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>Mode Administrator:</strong> Anda melihat stock dari <strong>SEMUA CABANG</strong>
                </div>
                <?php else: ?>
                <div class="alert alert-info" style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>Mode User:</strong> Anda hanya melihat stock dari <strong>CABANG ANDA</strong>
                </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar Stock Inventory</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cabang</th>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Stock</th>
                                <th>Harga Satuan</th>
                                <th>Nilai Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><?php echo $stock['inventory_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($stock['nama_cabang']); ?></strong>
                                    <?php if ($stock['last_update']): ?>
                                        <br><small style="color: #7f8c8d;">Update: <?php echo date('d/m/Y', strtotime($stock['last_update'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($stock['nama_produk']); ?></td>
                                <td><?php echo htmlspecialchars($stock['kategori']); ?></td>
                                <td><strong style="font-size: 16px;"><?php echo number_format($stock['stok'], 0, ',', '.'); ?></strong></td>
                                <td>Rp <?php echo number_format($stock['harga'], 0, ',', '.'); ?></td>
                                <td><strong>Rp <?php echo number_format($stock['stok'] * $stock['harga'], 0, ',', '.'); ?></strong></td>
                                <td>
                                    <?php if ($stock['stok'] == 0): ?>
                                        <span class="badge" style="background: #f8d7da; color: #721c24;">❌ Out of Stock</span>
                                    <?php elseif ($stock['stok'] < 10): ?>
                                        <span class="badge badge-inactive">⚠️ Low Stock</span>
                                    <?php elseif ($stock['stok'] < 50): ?>
                                        <span class="badge" style="background: #d1ecf1; color: #0c5460;">📊 Medium</span>
                                    <?php else: ?>
                                        <span class="badge badge-active">✅ Good</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stocks)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                    Belum ada data stock
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
