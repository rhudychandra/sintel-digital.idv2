<!-- Charts Section -->
<div class="charts-grid">
    <!-- Sales Trend Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h3>📈 Trend Penjualan</h3>
        </div>
        <div style="height: 300px;">
            <?php if (!empty($trend_dates)): ?>
                <canvas id="salesTrendChart"></canvas>
            <?php else: ?>
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #7f8c8d;">
                    <div style="text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📊</div>
                        <p>Tidak ada data untuk ditampilkan</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Payment Distribution Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h3>💳 Distribusi Metode Pembayaran</h3>
        </div>
        <div style="height: 300px;">
            <?php if (!empty($payment_labels)): ?>
                <canvas id="paymentDistChart"></canvas>
            <?php else: ?>
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #7f8c8d;">
                    <div style="text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 10px;">💳</div>
                        <p>Tidak ada data untuk ditampilkan</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
