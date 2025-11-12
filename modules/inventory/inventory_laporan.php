<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Determine user access level
$is_admin_or_manager = in_array($user['role'], ['administrator', 'manager']);
$user_cabang_id = $user['cabang_id'];

// Get filter parameters
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';
$filter_reseller = isset($_GET['reseller_id']) ? $_GET['reseller_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_search = isset($_GET['search']) ? $_GET['search'] : '';

// Check if cabang_id column exists in penjualan table first
$check_column = $conn->query("SHOW COLUMNS FROM penjualan LIKE 'cabang_id'");
$has_cabang_column = ($check_column && $check_column->num_rows > 0);

// Apply role-based filtering
if (!$is_admin_or_manager && $user_cabang_id && $has_cabang_column) {
    $filter_cabang = $user_cabang_id;
}

// Build WHERE clause
$where_conditions = ["p.tanggal_penjualan BETWEEN ? AND ?"];
$params = [$filter_start_date, $filter_end_date];
$types = "ss";

if (!empty($filter_cabang) && $has_cabang_column) {
    $where_conditions[] = "p.cabang_id = ?";
    $params[] = $filter_cabang;
    $types .= "i";
}

if (!empty($filter_reseller)) {
    $where_conditions[] = "p.reseller_id = ?";
    $params[] = $filter_reseller;
    $types .= "i";
}

if (!empty($filter_status)) {
    $where_conditions[] = "p.status_pembayaran = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get summary statistics
$stats_query = "SELECT 
    COUNT(DISTINCT p.penjualan_id) as total_transaksi,
    COALESCE(SUM(p.total), 0) as total_penjualan,
    COALESCE(AVG(p.total), 0) as rata_rata,
    COALESCE(SUM(dp.jumlah), 0) as total_produk_terjual
FROM penjualan p
LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
WHERE {$where_clause}";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Get detailed sales data
if ($has_cabang_column) {
    $sales_query = "SELECT 
        p.penjualan_id,
        p.no_invoice,
        p.tanggal_penjualan,
        r.nama_reseller,
        COALESCE(c.nama_cabang, '-') as nama_cabang,
        COUNT(dp.detail_id) as total_items,
        p.subtotal,
        p.total,
        p.status_pembayaran,
        p.metode_pembayaran
    FROM penjualan p
    LEFT JOIN reseller r ON p.reseller_id = r.reseller_id
    LEFT JOIN cabang c ON p.cabang_id = c.cabang_id
    LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
    WHERE {$where_clause}";
} else {
    // Fallback query without cabang_id
    $sales_query = "SELECT 
        p.penjualan_id,
        p.no_invoice,
        p.tanggal_penjualan,
        r.nama_reseller,
        COALESCE(c.nama_cabang, '-') as nama_cabang,
        COUNT(dp.detail_id) as total_items,
        p.subtotal,
        p.total,
        p.status_pembayaran,
        p.metode_pembayaran
    FROM penjualan p
    LEFT JOIN reseller r ON p.reseller_id = r.reseller_id
    LEFT JOIN users u ON p.user_id = u.user_id
    LEFT JOIN cabang c ON u.cabang_id = c.cabang_id
    LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
    WHERE {$where_clause}";
}

$search_params = $params;
$search_types = $types;

if (!empty($filter_search)) {
    $sales_query .= " AND (p.no_invoice LIKE ? OR r.nama_reseller LIKE ? OR c.nama_cabang LIKE ?)";
    $search_param = "%{$filter_search}%";
    $search_params[] = $search_param;
    $search_params[] = $search_param;
    $search_params[] = $search_param;
    $search_types .= "sss";
}

$sales_query .= " GROUP BY p.penjualan_id ORDER BY p.tanggal_penjualan DESC, p.penjualan_id DESC";

$stmt = $conn->prepare($sales_query);
$stmt->bind_param($search_types, ...$search_params);
$stmt->execute();
$sales_data = $stmt->get_result();

// Get cabang list for filter (only for admin/manager)
$cabang_list = [];
if ($is_admin_or_manager) {
    $result = $conn->query("SELECT cabang_id, nama_cabang, kota FROM cabang WHERE status = 'active' ORDER BY nama_cabang");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cabang_list[] = $row;
        }
    }
}

// Get reseller list for filter
$reseller_list = [];
$reseller_query = "SELECT reseller_id, nama_reseller FROM reseller WHERE status = 'active'";
if (!empty($filter_cabang)) {
    $reseller_query .= " AND cabang_id = " . intval($filter_cabang);
}
$reseller_query .= " ORDER BY nama_reseller";
$result = $conn->query($reseller_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reseller_list[] = $row;
    }
}

// Get payment method distribution
$payment_dist_query = "SELECT 
    metode_pembayaran,
    COUNT(*) as jumlah,
    SUM(total) as total_nilai
FROM penjualan p
WHERE {$where_clause}
GROUP BY metode_pembayaran";

$stmt = $conn->prepare($payment_dist_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$payment_distribution = $stmt->get_result();

$payment_labels = [];
$payment_values = [];
while ($row = $payment_distribution->fetch_assoc()) {
    $payment_labels[] = ucfirst(str_replace('_', ' ', $row['metode_pembayaran']));
    $payment_values[] = floatval($row['total_nilai']);
}

// Get daily sales trend
$trend_query = "SELECT 
    DATE(tanggal_penjualan) as tanggal,
    COUNT(*) as jumlah_transaksi,
    SUM(total) as total_penjualan
FROM penjualan p
WHERE {$where_clause}
GROUP BY DATE(tanggal_penjualan) 
ORDER BY tanggal";

$stmt = $conn->prepare($trend_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$sales_trend = $stmt->get_result();

$trend_dates = [];
$trend_values = [];
while ($row = $sales_trend->fetch_assoc()) {
    $trend_dates[] = date('d/m', strtotime($row['tanggal']));
    $trend_values[] = floatval($row['total_penjualan']);
}

// Reset pointer for display
$stmt = $conn->prepare($sales_query);
$stmt->bind_param($search_types, ...$search_params);
$stmt->execute();
$sales_data = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Inventory System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../assets/css/laporan_styles.css">
    <style>
        /* Print only the detail table */
        @media print {
            body * { visibility: hidden !important; }
            #detail-penjualan, #detail-penjualan * { visibility: visible !important; }
            #detail-penjualan { position: absolute; left: 0; top: 0; width: 100%; background: #ffffff; }
            .admin-sidebar, .admin-header, .filter-box, .stats-grid, .charts-grid, .info-section { display: none !important; }
        }

        /* Ensure consistent print page and avoid row splits (default landscape, can be overridden dynamically) */
        @page { size: A4 landscape; margin: 10mm; }
        #detail-penjualan table { width: 100%; border-collapse: collapse; }
        #detail-penjualan thead { display: table-header-group; }
        #detail-penjualan tfoot { display: table-footer-group; }
        #detail-penjualan tr { page-break-inside: avoid; }
    </style>
    <!-- Client-side PDF libraries -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
</head>
<body class="admin-page">
    <!-- Hidden probe to measure A4 size in pixels -->
    <div id="a4-probe" style="position:absolute; visibility:hidden; width:297mm; height:210mm; left:-9999px; top:-9999px;"></div>
    <div class="admin-container">
        <?php include __DIR__ . '/../laporan/laporan_sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div>
                    <h1>📋 Laporan Penjualan</h1>
                    <p style="color: #7f8c8d; font-size: 14px; margin-top: 5px;">
                        Laporan lengkap penjualan dengan analisis dan export
                    </p>
                </div>
                <div class="header-info">
                    <span class="date">📅 <?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php include __DIR__ . '/../laporan/laporan_filter.php'; ?>
                <?php include __DIR__ . '/../laporan/laporan_stats.php'; ?>
                <?php include __DIR__ . '/../laporan/laporan_charts.php'; ?>
                <?php include __DIR__ . '/../laporan/laporan_table.php'; ?>
                <?php include __DIR__ . '/../laporan/laporan_info.php'; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Sales Trend Chart
        <?php if (!empty($trend_dates)): ?>
        const trendCtx = document.getElementById('salesTrendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($trend_dates); ?>,
                    datasets: [{
                        label: 'Total Penjualan (Rp)',
                        data: <?php echo json_encode($trend_values); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                                }
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
        
        // Payment Distribution Chart
        <?php if (!empty($payment_labels)): ?>
        const paymentCtx = document.getElementById('paymentDistChart');
        if (paymentCtx) {
            new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($payment_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($payment_values); ?>,
                        backgroundColor: [
                            '#27ae60',
                            '#3498db',
                            '#9b59b6',
                            '#e67e22',
                            '#e74c3c'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': Rp ' + context.parsed.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
        
        // Dynamically set @page orientation before print
        function applyPrintOrientation(orientation) {
            const id = 'dynamic-print-style';
            let style = document.getElementById(id);
            if (!style) {
                style = document.createElement('style');
                style.id = id;
                document.head.appendChild(style);
            }
            const safeOrientation = orientation === 'portrait' ? 'portrait' : 'landscape';
            style.textContent = `@page { size: A4 ${safeOrientation}; margin: 10mm; }`;
        }

        // Print function: only print the detail penjualan table and scale to fit A4
        function printReport() {
            const el = document.getElementById('detail-penjualan');
            if (!el) { window.print(); return; }

            const orientationSel = document.getElementById('print-orientation');
            const orientation = orientationSel ? orientationSel.value : 'landscape';
            applyPrintOrientation(orientation);

            const scaleInput = document.getElementById('print-scale');
            const userScale = scaleInput && scaleInput.value ? Math.max(0.3, Math.min(1, parseFloat(scaleInput.value) / 100)) : null;

            // Convert mm to px based on browser rendering
            const mmToPx = (() => {
                const probe = document.createElement('div');
                probe.style.width = '100mm';
                probe.style.position = 'absolute';
                probe.style.visibility = 'hidden';
                document.body.appendChild(probe);
                const px = probe.getBoundingClientRect().width / 100; // px per mm
                document.body.removeChild(probe);
                return px;
            })();

            const isLandscape = orientation !== 'portrait';
            const pageWidthPx = (isLandscape ? 297 : 210) * mmToPx;   // A4 width in orientation
            const pageHeightPx = (isLandscape ? 210 : 297) * mmToPx;  // A4 height in orientation
            const marginPx = 10 * mmToPx;       // match @page margin

            // Measure element size
            const rect = el.getBoundingClientRect();
            const elWidth = rect.width;
            const elHeight = rect.height;

            // Compute scale to fit within printable area
            const maxWidth = pageWidthPx - marginPx * 2;
            const maxHeight = pageHeightPx - marginPx * 2;
            const autoScale = Math.min(1, maxWidth / elWidth, maxHeight / elHeight);
            const scale = userScale ? Math.min(userScale, autoScale) : autoScale;

            const prevTransform = el.style.transform;
            const prevOrigin = el.style.transformOrigin;
            el.style.transformOrigin = 'top left';
            el.style.transform = `scale(${scale})`;

            const onAfterPrint = () => {
                el.style.transform = prevTransform;
                el.style.transformOrigin = prevOrigin;
                window.removeEventListener('afterprint', onAfterPrint);
            };
            window.addEventListener('afterprint', onAfterPrint);

            window.print();
        }
        
        // Export to Excel
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = '../laporan/export_laporan_excel.php?' + params.toString();
        }
        
        // Export to PDF (client-side): capture only the detail table
        async function exportToPDF() {
            const el = document.getElementById('detail-penjualan');
            if (!el) return;
            const { jsPDF } = window.jspdf;
            // Orientation from UI
            const orientationSel = document.getElementById('print-orientation');
            const orientation = orientationSel ? orientationSel.value : 'landscape';
            const scaleInput = document.getElementById('print-scale');
            const userScale = scaleInput && scaleInput.value ? Math.max(0.3, Math.min(1.0, parseFloat(scaleInput.value) / 100)) : null;

            // Use white background and higher html2canvas scale for quality
            const canvas = await html2canvas(el, { scale: 2, backgroundColor: '#ffffff', useCORS: true });
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF({ orientation: orientation === 'portrait' ? 'portrait' : 'landscape', unit: 'pt', format: 'a4' });
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const margin = 20;
            const imgWidth = pageWidth - margin * 2;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            const y = margin;
            // If content taller than a page, scale it to fit one page to avoid clipping (or use user scale if smaller)
            const autoScale = Math.min(1, (pageHeight - margin * 2) / imgHeight);
            const scaleToFit = userScale ? Math.min(userScale, autoScale) : autoScale;
            const drawWidth = imgWidth;
            const drawHeight = imgHeight * scaleToFit;
            pdf.addImage(imgData, 'PNG', margin, y, drawWidth, drawHeight);
            const fileName = 'Detail_Penjualan_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.pdf';
            pdf.save(fileName);
        }
    </script>
                
    
    <?php $conn->close(); ?>
</body>
</html>
