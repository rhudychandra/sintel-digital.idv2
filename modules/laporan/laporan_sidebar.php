<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2>Inventory</h2>
        <p><?php echo htmlspecialchars($user['full_name']); ?></p>
        <small style="opacity: 0.8; font-size: 12px;">
            <?php echo ucfirst($user['role']); ?>
            <?php if (!$is_admin_or_manager && $user_cabang_id): ?>
                <?php
                $cabang_info = $conn->query("SELECT nama_cabang FROM cabang WHERE cabang_id = " . intval($user_cabang_id));
                if ($cabang_info && $cabang_row = $cabang_info->fetch_assoc()) {
                    echo " - " . htmlspecialchars($cabang_row['nama_cabang']);
                }
                ?>
            <?php endif; ?>
        </small>
    </div>
    
    <nav class="sidebar-nav">
        <a href="inventory.php?page=dashboard" class="nav-item">
            <span class="nav-icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="inventory.php?page=input_barang" class="nav-item">
            <span class="nav-icon">📥</span>
            <span>Input Barang</span>
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
            <span>Stock</span>
        </a>
                <a href="inventory_laporan.php" class="nav-item active">
                    <span class="nav-icon">📋</span>
                    <span>Laporan Penjualan</span>
                </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../../dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
        <a href="../../logout.php" class="btn-logout">Logout</a>
    </div>
</aside>
