<!-- Filter Box -->
<div class="filter-box">
    <h2>🔍 Filter & Pencarian Laporan</h2>
    <form method="GET" id="filterForm">
        <div class="filter-grid">
            <div class="filter-group">
                <label>Tanggal Mulai</label>
                <input type="date" name="start_date" value="<?php echo $filter_start_date; ?>" required>
            </div>
            
            <div class="filter-group">
                <label>Tanggal Akhir</label>
                <input type="date" name="end_date" value="<?php echo $filter_end_date; ?>" required>
            </div>
            
            <?php if ($is_admin_or_manager): ?>
            <div class="filter-group">
                <label>Cabang</label>
                <select name="cabang_id" id="cabangFilter">
                    <option value="">-- Semua Cabang --</option>
                    <?php 
                    if (!empty($cabang_list)) {
                        foreach ($cabang_list as $cabang): 
                    ?>
                        <option value="<?php echo $cabang['cabang_id']; ?>" <?php echo $filter_cabang == $cabang['cabang_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cabang['nama_cabang']); ?> - <?php echo htmlspecialchars($cabang['kota']); ?>
                        </option>
                    <?php endforeach; } ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <label>Reseller</label>
                <select name="reseller_id">
                    <option value="">-- Semua Reseller --</option>
                    <?php 
                    if (!empty($reseller_list)) {
                        foreach ($reseller_list as $reseller): 
                    ?>
                        <option value="<?php echo $reseller['reseller_id']; ?>" <?php echo $filter_reseller == $reseller['reseller_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($reseller['nama_reseller']); ?>
                        </option>
                    <?php endforeach; } ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Status Pembayaran</label>
                <select name="status">
                    <option value="">-- Semua Status --</option>
                    <option value="paid" <?php echo $filter_status == 'paid' ? 'selected' : ''; ?>>✅ Paid</option>
                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                    <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Pencarian</label>
                <input type="text" name="search" placeholder="Cari invoice, reseller, cabang..." value="<?php echo htmlspecialchars($filter_search); ?>">
            </div>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn-filter">
                🔍 Terapkan Filter
            </button>
            <a href="inventory_laporan.php" class="btn-reset">
                🔄 Reset Filter
            </a>
        </div>
        
        <div class="date-presets">
            <span style="opacity: 0.9; font-size: 13px; margin-right: 10px;">Quick Select:</span>
            <a href="?start_date=<?php echo date('Y-m-d'); ?>&end_date=<?php echo date('Y-m-d'); ?><?php echo !empty($filter_cabang) ? '&cabang_id='.$filter_cabang : ''; ?>" class="preset-btn">Hari Ini</a>
            <a href="?start_date=<?php echo date('Y-m-d', strtotime('monday this week')); ?>&end_date=<?php echo date('Y-m-d'); ?><?php echo !empty($filter_cabang) ? '&cabang_id='.$filter_cabang : ''; ?>" class="preset-btn">Minggu Ini</a>
            <a href="?start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-d'); ?><?php echo !empty($filter_cabang) ? '&cabang_id='.$filter_cabang : ''; ?>" class="preset-btn">Bulan Ini</a>
            <a href="?start_date=<?php echo date('Y-m-01', strtotime('last month')); ?>&end_date=<?php echo date('Y-m-t', strtotime('last month')); ?><?php echo !empty($filter_cabang) ? '&cabang_id='.$filter_cabang : ''; ?>" class="preset-btn">Bulan Lalu</a>
            <a href="?start_date=<?php echo date('Y-01-01'); ?>&end_date=<?php echo date('Y-m-d'); ?><?php echo !empty($filter_cabang) ? '&cabang_id='.$filter_cabang : ''; ?>" class="preset-btn">Tahun Ini</a>
        </div>
    </form>
</div>
