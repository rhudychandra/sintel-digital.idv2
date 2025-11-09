<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">💰</div>
        <div class="stat-info">
            <h3>Rp <?php echo number_format($stats['total_penjualan'] ?? 0, 0, ',', '.'); ?></h3>
            <p>Total Penjualan</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">📊</div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['total_transaksi'] ?? 0); ?></h3>
            <p>Total Transaksi</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">📈</div>
        <div class="stat-info">
            <h3>Rp <?php echo number_format($stats['rata_rata'] ?? 0, 0, ',', '.'); ?></h3>
            <p>Rata-rata per Transaksi</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">📦</div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['total_produk_terjual'] ?? 0); ?></h3>
            <p>Total Produk Terjual</p>
        </div>
    </div>
</div>
