<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

if ($user['role'] !== 'administrator') {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getDBConnection();

// Get date range from GET parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default: first day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Default: today

// Get sales per cabang with date filter
$sales_per_cabang = [];
$error_message = '';

try {
    // Query with date filter
    $query = "SELECT 
                c.cabang_id,
                c.kode_cabang,
                c.nama_cabang,
                c.kota,
                COUNT(DISTINCT p.penjualan_id) AS total_transaksi,
                COALESCE(SUM(p.total), 0) AS total_penjualan,
                COUNT(DISTINCT r.reseller_id) AS jumlah_reseller
              FROM cabang c
              LEFT JOIN penjualan p ON c.cabang_id = p.cabang_id 
                  AND p.status_pembayaran = 'paid'
                  AND DATE(p.tanggal_penjualan) BETWEEN ? AND ?
              LEFT JOIN reseller r ON c.cabang_id = r.cabang_id AND r.status = 'active'
              WHERE c.status = 'active'
              GROUP BY c.cabang_id, c.kode_cabang, c.nama_cabang, c.kota
              ORDER BY total_penjualan DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sales_per_cabang[] = $row;
        }
    } else {
        $error_message = "Error loading data: " . $conn->error;
    }
    
    $stmt->close();
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

$conn->close();

// Prepare data for Chart.js
$chart_labels = [];
$chart_sales = [];
$chart_transactions = [];
$chart_colors = [
    'rgba(102, 126, 234, 0.8)',
    'rgba(118, 75, 162, 0.8)',
    'rgba(237, 100, 166, 0.8)',
    'rgba(255, 154, 158, 0.8)',
    'rgba(250, 208, 196, 0.8)'
];

foreach ($sales_per_cabang as $index => $cabang) {
    $chart_labels[] = $cabang['nama_cabang'];
    $chart_sales[] = $cabang['total_penjualan'];
    $chart_transactions[] = $cabang['total_transaksi'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik & Laporan - Administrator Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin: 30px 0;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .date-filter {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .date-filter h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }
        
        .filter-group input[type="date"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Lexend', sans-serif;
            transition: all 0.3s ease;
        }
        
        .filter-group input[type="date"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-filter {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-reset {
            padding: 12px 30px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-reset:hover {
            background: #7f8c8d;
        }
        
        .date-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        @media (max-width: 1024px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
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
                <a href="inventory.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>Inventory</span>
                </a>
                <a href="stock.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span>Stock</span>
                </a>
                <a href="grafik.php" class="nav-item active">
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
                <h1>Grafik & Laporan</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($error_message): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                    <br><small>Pastikan tabel cabang dan penjualan sudah dibuat. Jalankan file fix_admin_menu_tables.sql</small>
                </div>
                <?php endif; ?>
                
                <!-- Date Range Filter -->
                <div class="date-filter">
                    <h3>📅 Filter Periode Tanggal</h3>
                    <div class="date-info">
                        <strong>Periode Saat Ini:</strong> 
                        <?php 
                        $start_formatted = date('d F Y', strtotime($start_date));
                        $end_formatted = date('d F Y', strtotime($end_date));
                        echo "$start_formatted - $end_formatted";
                        ?>
                    </div>
                    <form method="GET" action="grafik.php" class="filter-form">
                        <div class="filter-group">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="filter-group">
                            <label for="end_date">Tanggal Akhir</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        <div class="filter-buttons">
                            <button type="submit" class="btn-filter">🔍 Tampilkan</button>
                            <a href="grafik.php" class="btn-reset">🔄 Reset</a>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($sales_per_cabang)): ?>
                <!-- Chart Visualizations -->
                <div class="chart-grid">
                    <!-- Bar Chart: Total Penjualan per Cabang -->
                    <div class="chart-container">
                        <h3 style="text-align: center; color: #2c3e50; margin-bottom: 20px;">Total Penjualan per Cabang</h3>
                        <canvas id="salesChart"></canvas>
                    </div>
                    
                    <!-- Pie Chart: Distribusi Penjualan -->
                    <div class="chart-container">
                        <h3 style="text-align: center; color: #2c3e50; margin-bottom: 20px;">Distribusi Penjualan</h3>
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-grid">
                    <!-- Line Chart: Transaksi per Cabang -->
                    <div class="chart-container">
                        <h3 style="text-align: center; color: #2c3e50; margin-bottom: 20px;">Total Transaksi per Cabang</h3>
                        <canvas id="transactionChart"></canvas>
                    </div>
                    
                    <!-- Doughnut Chart: Perbandingan Transaksi -->
                    <div class="chart-container">
                        <h3 style="text-align: center; color: #2c3e50; margin-bottom: 20px;">Perbandingan Transaksi</h3>
                        <canvas id="doughnutChart"></canvas>
                    </div>
                </div>
                <?php else: ?>
                <div class="date-info" style="background: #fff3cd; color: #856404; text-align: center; padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">📊</div>
                    <h3 style="color: #856404; margin-bottom: 10px;">Tidak Ada Data</h3>
                    <p>Tidak ada data penjualan untuk periode yang dipilih.</p>
                    <p style="margin-top: 15px;">Coba pilih periode tanggal yang berbeda atau pastikan ada data penjualan di database.</p>
                </div>
                <?php endif; ?>
                
                <!-- Sales Performance Table -->
                <div class="table-container" style="margin-top: 30px;">
                    <div class="table-header">
                        <h2>Performance Penjualan per Cabang</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Cabang</th>
                                <th>Kota</th>
                                <th>Total Transaksi</th>
                                <th>Total Penjualan</th>
                                <th>Jumlah Reseller</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_per_cabang as $cabang): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cabang['kode_cabang']); ?></td>
                                <td><?php echo htmlspecialchars($cabang['nama_cabang']); ?></td>
                                <td><?php echo htmlspecialchars($cabang['kota']); ?></td>
                                <td><?php echo number_format($cabang['total_transaksi'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($cabang['total_penjualan'], 0, ',', '.'); ?></td>
                                <td><?php echo number_format($cabang['jumlah_reseller'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sales_per_cabang)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                    Tidak ada data untuk periode yang dipilih
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <?php if (!empty($sales_per_cabang)): ?>
    <script>
        // Data from PHP
        const labels = <?php echo json_encode($chart_labels); ?>;
        const salesData = <?php echo json_encode($chart_sales); ?>;
        const transactionData = <?php echo json_encode($chart_transactions); ?>;
        const colors = <?php echo json_encode($chart_colors); ?>;
        
        // Chart.js default configuration
        Chart.defaults.font.family = 'Lexend, sans-serif';
        Chart.defaults.color = '#2c3e50';
        
        // 1. Bar Chart: Total Penjualan per Cabang
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Penjualan (Rp)',
                        data: salesData,
                        backgroundColor: colors,
                        borderColor: colors.map(c => c.replace('0.8', '1')),
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
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
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                                    }
                                    return 'Rp ' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // 2. Pie Chart: Distribusi Penjualan
        const pieCtx = document.getElementById('pieChart');
        if (pieCtx) {
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: salesData,
                        backgroundColor: colors,
                        borderColor: '#fff',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': Rp ' + context.parsed.toLocaleString('id-ID') + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // 3. Line Chart: Transaksi per Cabang
        const transactionCtx = document.getElementById('transactionChart');
        if (transactionCtx) {
            new Chart(transactionCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Transaksi',
                        data: transactionData,
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: 'rgba(102, 126, 234, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
        
        // 4. Doughnut Chart: Perbandingan Transaksi
        const doughnutCtx = document.getElementById('doughnutChart');
        if (doughnutCtx) {
            new Chart(doughnutCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: transactionData,
                        backgroundColor: colors,
                        borderColor: '#fff',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' transaksi (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
