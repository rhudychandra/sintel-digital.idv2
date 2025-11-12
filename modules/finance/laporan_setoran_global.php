<?php
require_once '../../config/config.php';
require_once __DIR__ . '/finance_global_metrics.php';
requireLogin();

$user = getCurrentUser();
$page_title = 'Laporan Setoran Global - Sinar Telkom Dashboard System';

// Period selection (default current month/year) - for future filtering
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get real-time metrics
$conn = getDBConnection();
$metrics = getGlobalMetrics($conn);

// Helper format functions
function format_rupiah($amount) { return 'Rp ' . number_format((int)$amount, 0, ',', '.'); }
function format_qty($qty) { return number_format((int)$qty, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/admin-styles.css" />
    <link rel="stylesheet" href="../../assets/css/laporan_styles.css" />
    <link rel="stylesheet" href="../../assets/css/laporan_setoran_global.css" />
</head>
<body class="submenu-page laporan-global-page">
<header class="dashboard-header">
    <div style="display:flex; align-items:center; gap:15px;">
        <img src="../../assets/images/logo_icon.png" alt="Logo" style="width:50px;height:50px;border-radius:10px;object-fit:contain;box-shadow:0 2px 8px rgba(0,0,0,0.1);" />
        <h1>Laporan Setoran Global</h1>
    </div>
    <div class="header-buttons">
        <a href="finance" class="back-button">← Kembali ke Finance</a>
        <a href="<?php echo BASE_PATH; ?>/logout" class="logout-button">Logout</a>
    </div>
</header>

<main class="laporan-global-main">
    <div class="laporan-container">
    <section class="period-filter">
        <div class="period-info">
            <div class="period-title">📊 Data Real-Time</div>
            <div class="period-current">
                Terakhir Update: <strong id="last-update-time"><?php echo date('H:i:s'); ?></strong>
                <span class="live-indicator">● LIVE</span>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="validate_stock_consistency.php" target="_blank" class="btn-validation" title="Stock Health Check">
                <i class="fas fa-check-circle"></i> Validation
            </a>
            <button type="button" onclick="refreshData()" class="btn-refresh" id="btn-refresh">
                <i class="fas fa-sync-alt"></i> Refresh Sekarang
            </button>
        </div>
    </section>

    <div class="laporan-layout">
        <!-- Left Column -->
        <div class="laporan-column">
            <!-- 1. Stock LinkAja & Finpay -->
            <?php 
                $linkaja_total = array_sum(array_column($metrics['linkaja_finpay'], 'nominal'));
            ?>
            <div class="laporan-group">
                <div class="group-header" onclick="toggleGroup('linkaja-finpay')">
                    <span class="group-summary">
                        💴 Stock LinkAja & Finpay
                        <span class="group-total-summary">
                            <span class="total-nominal"><?php echo format_rupiah($linkaja_total); ?></span>
                        </span>
                    </span>
                    <button class="group-toggle collapsed" type="button">Detail</button>
                </div>
                <ul class="group-items" id="group-linkaja-finpay">
                    <?php if (!empty($metrics['linkaja_finpay'])): ?>
                        <?php foreach($metrics['linkaja_finpay'] as $item): ?>
                            <li class="group-item">
                                <span class="item-label"><?php echo htmlspecialchars($item['nama']); ?></span>
                                <span class="item-value"><?php echo format_rupiah($item['nominal']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="group-item"><span class="item-label">Tidak ada data</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 2. Perdana & VF Segel -->
            <?php 
                $perdana_total_qty = array_sum(array_column($metrics['perdana_vf_segel'], 'qty'));
                $perdana_total_nominal = array_sum(array_column($metrics['perdana_vf_segel'], 'nominal'));
            ?>
            <div class="laporan-group">
                <div class="group-header" onclick="toggleGroup('perdana-segel')">
                    <span class="group-summary">
                        📕 Perdana & VF Segel
                        <span class="group-total-summary">
                            <span class="total-qty">Qty: <?php echo format_qty($perdana_total_qty); ?></span>
                            <span class="total-nominal"><?php echo format_rupiah($perdana_total_nominal); ?></span>
                        </span>
                    </span>
                    <button class="group-toggle collapsed" type="button">Detail</button>
                </div>
                <ul class="group-items" id="group-perdana-segel">
                    <?php if (!empty($metrics['perdana_vf_segel'])): ?>
                        <?php foreach($metrics['perdana_vf_segel'] as $item): ?>
                            <li class="group-item">
                                <span class="item-label"><?php echo htmlspecialchars($item['nama']); ?></span>
                                <span class="item-value">
                                    <small class="qty-badge">Qty: <?php echo format_qty($item['qty']); ?></small>
                                    <?php echo format_rupiah($item['nominal']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="group-item"><span class="item-label">Tidak ada data</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 3. Perdana Internet -->
            <?php 
                $perdana_inet_total_qty = array_sum(array_column($metrics['perdana_internet'], 'qty'));
                $perdana_inet_total_nominal = array_sum(array_column($metrics['perdana_internet'], 'nominal'));
            ?>
            <div class="laporan-group">
                <div class="group-header" onclick="toggleGroup('perdana-internet')">
                    <span class="group-summary">
                        📱 Perdana Internet
                        <span class="group-total-summary">
                            <span class="total-qty">Qty: <?php echo format_qty($perdana_inet_total_qty); ?></span>
                            <span class="total-nominal"><?php echo format_rupiah($perdana_inet_total_nominal); ?></span>
                        </span>
                    </span>
                    <button class="group-toggle collapsed" type="button">Detail</button>
                </div>
                <ul class="group-items" id="group-perdana-internet">
                    <?php if (!empty($metrics['perdana_internet'])): ?>
                        <?php foreach($metrics['perdana_internet'] as $item): ?>
                            <li class="group-item">
                                <span class="item-label"><?php echo htmlspecialchars($item['nama']); ?></span>
                                <span class="item-value">
                                    <small class="qty-badge">Qty: <?php echo format_qty($item['qty']); ?></small>
                                    <?php echo format_rupiah($item['nominal']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="group-item"><span class="item-label">Tidak ada data</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 4. Voucher Internet -->
            <?php 
                $voucher_total_qty = array_sum(array_column($metrics['voucher_internet'], 'qty'));
                $voucher_total_nominal = array_sum(array_column($metrics['voucher_internet'], 'nominal'));
            ?>
            <div class="laporan-group">
                <div class="group-header" onclick="toggleGroup('voucher-internet')">
                    <span class="group-summary">
                        🔖 Voucher Internet
                        <span class="group-total-summary">
                            <span class="total-qty">Qty: <?php echo format_qty($voucher_total_qty); ?></span>
                            <span class="total-nominal"><?php echo format_rupiah($voucher_total_nominal); ?></span>
                        </span>
                    </span>
                    <button class="group-toggle collapsed" type="button">Detail</button>
                </div>
                <ul class="group-items" id="group-voucher-internet">
                    <?php if (!empty($metrics['voucher_internet'])): ?>
                        <?php foreach($metrics['voucher_internet'] as $item): ?>
                            <li class="group-item">
                                <span class="item-label"><?php echo htmlspecialchars($item['nama']); ?></span>
                                <span class="item-value">
                                    <small class="qty-badge">Qty: <?php echo format_qty($item['qty']); ?></small>
                                    <?php echo format_rupiah($item['nominal']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="group-item"><span class="item-label">Tidak ada data</span></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Right Column -->
        <div class="laporan-column">
            <!-- 5. Piutang Kantor Pusat -->
            <div class="laporan-group">
                <div class="group-header">💰 Piutang Kantor Pusat</div>
                <ul class="group-items expanded" id="group-piutang">
                    <li class="group-item highlight-total">
                        <span class="item-label"><strong>Total Piutang Global</strong></span>
                        <span class="item-value total-value" id="piutang-total"><?php echo format_rupiah($metrics['piutang_kantor_pusat']); ?></span>
                    </li>
                </ul>
                <div class="group-note">
                    <small>⚠️ Sementara: Stok + Penjualan pending/TOP. Nanti akan diganti dengan data dari Report Piutang.</small>
                </div>
            </div>

            <!-- 6. Stock TAP per Cabang -->
            <?php 
                $stock_tap_total = array_sum(array_column($metrics['stock_tap_per_cabang'], 'nominal'));
            ?>
            <div class="laporan-group">
                <div class="group-header" onclick="toggleGroup('stock-tap')">
                    <span class="group-summary">
                        🏢 Stock TAP per Cabang
                        <span class="group-total-summary">
                            <span class="total-nominal"><?php echo format_rupiah($stock_tap_total); ?></span>
                        </span>
                    </span>
                    <button class="group-toggle collapsed" type="button">Detail</button>
                </div>
                <ul class="group-items" id="group-stock-tap">
                    <?php if (!empty($metrics['stock_tap_per_cabang'])): ?>
                        <?php foreach($metrics['stock_tap_per_cabang'] as $item): ?>
                            <li class="group-item">
                                <span class="item-label"><?php echo htmlspecialchars($item['cabang']); ?></span>
                                <span class="item-value"><?php echo format_rupiah($item['nominal']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="group-item"><span class="item-label">Tidak ada data cabang</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 7. TOP (Term Off Payment) -->
            <div class="laporan-group">
                <div class="group-header">⏳ TOP (Term Off Payment)</div>
                <ul class="group-items" id="group-top">
                    <li class="group-item highlight-total">
                        <span class="item-label"><strong>Total TOP Belum Dibayar</strong></span>
                        <span class="item-value total-value" id="top-total"><?php echo format_rupiah($metrics['top_payment']); ?></span>
                    </li>
                </ul>
                <div class="group-note">
                    <small>Semua transaksi penjualan dengan status pembayaran TOP</small>
                </div>
            </div>
        </div>
    </div>

    <section class="laporan-actions">
        <a href="#" class="action-card" title="Report Piutang (Coming Soon)">
            <div class="action-inner">Report PIUTANG</div>
        </a>
        <a href="#" class="action-card" title="Report Stock & Transferan (Coming Soon)">
            <div class="action-inner">Report Stock dan Transferan</div>
        </a>
        <a href="#" class="action-card" title="Pengajuan Stock SA & VF (Coming Soon)">
            <div class="action-inner">Pengajuan Stock SA & VF</div>
        </a>
    </section>

    <div class="info-box" style="margin-top:30px;font-size:11px;">
        <h3 style="font-size:13px;margin-bottom:8px;">ℹ️ Informasi Halaman</h3>
        <ul style="line-height:1.6;">
            <li><strong>Auto-refresh:</strong> Data diperbarui otomatis setiap 30 detik</li>
            <li><strong>Sumber Data:</strong> Langsung dari database (real-time)</li>
            <li><strong>LinkAja/Finpay:</strong> Nominal saldo saat ini</li>
            <li><strong>Perdana & VF Segel:</strong> Qty × Harga Pokok per produk</li>
            <li><strong>Voucher Internet:</strong> Qty × Harga Pokok per produk</li>
            <li><strong>Piutang Kantor Pusat:</strong> Hasil Report Piutang (Stock, TOP)</li>
            <li><strong>Stock TAP:</strong> Total nominal stok + piutang per cabang</li>
            <li><strong>TOP:</strong> Total transaksi dengan status pembayaran TOP</li>
        </ul>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script>
let autoRefreshInterval;
const REFRESH_INTERVAL = 30000; // 30 seconds

// Format currency
function formatRupiah(amount) {
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
}

// Format qty
function formatQty(qty) {
    return parseInt(qty).toLocaleString('id-ID');
}

// Refresh data via AJAX
function refreshData() {
    const btn = document.getElementById('btn-refresh');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';

    fetch('ajax_global_metrics.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                updateUI(result.data);
                document.getElementById('last-update-time').textContent = new Date().toLocaleTimeString('id-ID');
            } else {
                console.error('Error fetching metrics:', result.error);
                alert('Gagal memuat data: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            alert('Gagal terhubung ke server');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Sekarang';
        });
}

// Toggle accordion group
function toggleGroup(groupId) {
    const items = document.getElementById('group-' + groupId);
    const btn = items.previousElementSibling.querySelector('.group-toggle');
    
    if (items.classList.contains('expanded')) {
        items.classList.remove('expanded');
        btn.classList.remove('expanded');
        btn.classList.add('collapsed');
        btn.textContent = 'Detail';
    } else {
        items.classList.add('expanded');
        btn.classList.add('expanded');
        btn.classList.remove('collapsed');
        btn.textContent = 'Tutup';
    }
}

// Update UI with new data
function updateUI(metrics) {
    // Calculate totals
    const linkaja_total = metrics.linkaja_finpay ? metrics.linkaja_finpay.reduce((sum, item) => sum + parseFloat(item.nominal || 0), 0) : 0;
    const perdana_total_qty = metrics.perdana_vf_segel ? metrics.perdana_vf_segel.reduce((sum, item) => sum + parseInt(item.qty || 0), 0) : 0;
    const perdana_total_nominal = metrics.perdana_vf_segel ? metrics.perdana_vf_segel.reduce((sum, item) => sum + parseFloat(item.nominal || 0), 0) : 0;
    const perdana_inet_total_qty = metrics.perdana_internet ? metrics.perdana_internet.reduce((sum, item) => sum + parseInt(item.qty || 0), 0) : 0;
    const perdana_inet_total_nominal = metrics.perdana_internet ? metrics.perdana_internet.reduce((sum, item) => sum + parseFloat(item.nominal || 0), 0) : 0;
    const voucher_total_qty = metrics.voucher_internet ? metrics.voucher_internet.reduce((sum, item) => sum + parseInt(item.qty || 0), 0) : 0;
    const voucher_total_nominal = metrics.voucher_internet ? metrics.voucher_internet.reduce((sum, item) => sum + parseFloat(item.nominal || 0), 0) : 0;
    const stock_tap_total = metrics.stock_tap_per_cabang ? metrics.stock_tap_per_cabang.reduce((sum, item) => sum + parseFloat(item.nominal || 0), 0) : 0;

    // 1. LinkAja & Finpay
    const groupLinkaja = document.getElementById('group-linkaja-finpay');
    const headerLinkaja = groupLinkaja.previousElementSibling;
    headerLinkaja.querySelector('.total-nominal').textContent = formatRupiah(linkaja_total);
    
    groupLinkaja.innerHTML = '';
    if (metrics.linkaja_finpay && metrics.linkaja_finpay.length > 0) {
        metrics.linkaja_finpay.forEach(item => {
            const li = document.createElement('li');
            li.className = 'group-item';
            li.innerHTML = `
                <span class="item-label">${item.nama}</span>
                <span class="item-value">${formatRupiah(item.nominal)}</span>
            `;
            groupLinkaja.appendChild(li);
        });
    } else {
        groupLinkaja.innerHTML = '<li class="group-item"><span class="item-label">Tidak ada data</span></li>';
    }

    // 2. Perdana & VF Segel
    const groupPerdana = document.getElementById('group-perdana-segel');
    const headerPerdana = groupPerdana.previousElementSibling;
    headerPerdana.querySelector('.total-qty').textContent = 'Qty: ' + formatQty(perdana_total_qty);
    headerPerdana.querySelector('.total-nominal').textContent = formatRupiah(perdana_total_nominal);
    
    groupPerdana.innerHTML = '';
    if (metrics.perdana_vf_segel && metrics.perdana_vf_segel.length > 0) {
        metrics.perdana_vf_segel.forEach(item => {
            const li = document.createElement('li');
            li.className = 'group-item';
            li.innerHTML = `
                <span class="item-label">${item.nama}</span>
                <span class="item-value">
                    <small class="qty-badge">Qty: ${formatQty(item.qty)}</small>
                    ${formatRupiah(item.nominal)}
                </span>
            `;
            groupPerdana.appendChild(li);
        });
    } else {
        groupPerdana.innerHTML = '<li class="group-item"><span class="item-label">Tidak ada data</span></li>';
    }

    // 3. Perdana Internet
    const groupPerdanaInet = document.getElementById('group-perdana-internet');
    const headerPerdanaInet = groupPerdanaInet.previousElementSibling;
    headerPerdanaInet.querySelector('.total-qty').textContent = 'Qty: ' + formatQty(perdana_inet_total_qty);
    headerPerdanaInet.querySelector('.total-nominal').textContent = formatRupiah(perdana_inet_total_nominal);
    
    groupPerdanaInet.innerHTML = '';
    if (metrics.perdana_internet && metrics.perdana_internet.length > 0) {
        metrics.perdana_internet.forEach(item => {
            const li = document.createElement('li');
            li.className = 'group-item';
            li.innerHTML = `
                <span class="item-label">${item.nama}</span>
                <span class="item-value">
                    <small class="qty-badge">Qty: ${formatQty(item.qty)}</small>
                    ${formatRupiah(item.nominal)}
                </span>
            `;
            groupPerdanaInet.appendChild(li);
        });
    } else {
        groupPerdanaInet.innerHTML = '<li class="group-item"><span class="item-label">Tidak ada data</span></li>';
    }

    // 4. Voucher Internet
    const groupVoucher = document.getElementById('group-voucher-internet');
    const headerVoucher = groupVoucher.previousElementSibling;
    headerVoucher.querySelector('.total-qty').textContent = 'Qty: ' + formatQty(voucher_total_qty);
    headerVoucher.querySelector('.total-nominal').textContent = formatRupiah(voucher_total_nominal);
    
    groupVoucher.innerHTML = '';
    if (metrics.voucher_internet && metrics.voucher_internet.length > 0) {
        metrics.voucher_internet.forEach(item => {
            const li = document.createElement('li');
            li.className = 'group-item';
            li.innerHTML = `
                <span class="item-label">${item.nama}</span>
                <span class="item-value">
                    <small class="qty-badge">Qty: ${formatQty(item.qty)}</small>
                    ${formatRupiah(item.nominal)}
                </span>
            `;
            groupVoucher.appendChild(li);
        });
    } else {
        groupVoucher.innerHTML = '<li class="group-item"><span class="item-label">Tidak ada data</span></li>';
    }

    // 5. Piutang Kantor Pusat
    document.getElementById('piutang-total').textContent = formatRupiah(metrics.piutang_kantor_pusat);

    // 6. Stock TAP per Cabang
    const groupStockTap = document.getElementById('group-stock-tap');
    const headerStockTap = groupStockTap.previousElementSibling;
    headerStockTap.querySelector('.total-nominal').textContent = formatRupiah(stock_tap_total);
    
    groupStockTap.innerHTML = '';
    if (metrics.stock_tap_per_cabang && metrics.stock_tap_per_cabang.length > 0) {
        metrics.stock_tap_per_cabang.forEach(item => {
            const li = document.createElement('li');
            li.className = 'group-item';
            li.innerHTML = `
                <span class="item-label">${item.cabang}</span>
                <span class="item-value">${formatRupiah(item.nominal)}</span>
            `;
            groupStockTap.appendChild(li);
        });
    } else {
        groupStockTap.innerHTML = '<li class="group-item"><span class="item-label">Tidak ada data cabang</span></li>';
    }

    // 6. TOP
    document.getElementById('top-total').textContent = formatRupiah(metrics.top_payment);
}

// Start auto-refresh
function startAutoRefresh() {
    autoRefreshInterval = setInterval(refreshData, REFRESH_INTERVAL);
}

// Stop auto-refresh
function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Laporan Setoran Global - Auto-refresh enabled (30s)');
    startAutoRefresh();
    
    // Stop refresh when user leaves page
    window.addEventListener('beforeunload', stopAutoRefresh);
});
</script>
</body>
</html>
