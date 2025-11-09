<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Get current page/menu
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Handle form submissions
$message = '';
$error = '';

// Get products and resellers for dropdown
$products = $conn->query("SELECT produk_id, kode_produk, nama_produk, harga, stok FROM produk WHERE status = 'active' ORDER BY nama_produk");
$resellers = $conn->query("SELECT reseller_id, kode_reseller, nama_reseller FROM reseller WHERE status = 'active' ORDER BY nama_reseller");

// Dashboard data
$sales_data = null;
$chart_labels = [];
$reseller_data = [];
$total_transaksi = 0;
$total_penjualan = 0;
$period = 'daily';
$start_date = date('Y-m-d');
$end_date = date('Y-m-d');

if ($page === 'dashboard') {
    $period = isset($_GET['period']) ? $_GET['period'] : 'daily';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    if ($period === 'daily') {
        $start_date = $end_date = date('Y-m-d');
    } elseif ($period === 'weekly') {
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
    } elseif ($period === 'monthly') {
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
    }
    
    $sales_query = "
        SELECT 
            r.reseller_id,
            r.nama_reseller,
            COUNT(p.penjualan_id) as total_transaksi,
            COALESCE(SUM(p.total), 0) as total_penjualan
        FROM reseller r
        LEFT JOIN penjualan p ON r.reseller_id = p.reseller_id 
            AND p.tanggal_penjualan BETWEEN ? AND ?
            AND p.status_pembayaran = 'paid'
        WHERE r.status = 'active'
        GROUP BY r.reseller_id, r.nama_reseller
        ORDER BY total_penjualan DESC
    ";
    
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $sales_data = $stmt->get_result();
    
    $chart_query = "
        SELECT 
            DATE(p.tanggal_penjualan) as tanggal,
            r.nama_reseller,
            SUM(p.total) as total
        FROM penjualan p
        JOIN reseller r ON p.reseller_id = r.reseller_id
        WHERE p.tanggal_penjualan BETWEEN ? AND ?
            AND p.status_pembayaran = 'paid'
        GROUP BY DATE(p.tanggal_penjualan), r.nama_reseller
        ORDER BY tanggal, r.nama_reseller
    ";
    
    $stmt = $conn->prepare($chart_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $chart_data = $stmt->get_result();
    
    while ($row = $chart_data->fetch_assoc()) {
        $tanggal = $row['tanggal'];
        $reseller = $row['nama_reseller'];
        $total = $row['total'];
        
        if (!in_array($tanggal, $chart_labels)) {
            $chart_labels[] = $tanggal;
        }
        
        if (!isset($reseller_data[$reseller])) {
            $reseller_data[$reseller] = [];
        }
        
        $reseller_data[$reseller][$tanggal] = $total;
    }
    
    if ($sales_data) {
        $sales_data->data_seek(0);
        while ($row = $sales_data->fetch_assoc()) {
            $total_transaksi += $row['total_transaksi'];
            $total_penjualan += $row['total_penjualan'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Sinar Telkom Dashboard System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="admin/admin-styles.css">
    <?php if ($page === 'dashboard'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body class="admin-page">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Inventory</h2>
                <p><?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="?page=dashboard" class="nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="?page=input_barang" class="nav-item <?php echo $page === 'input_barang' ? 'active' : ''; ?>">
                    <span class="nav-icon">📥</span>
                    <span>Input Barang</span>
                </a>
                <a href="?page=input_penjualan" class="nav-item <?php echo $page === 'input_penjualan' ? 'active' : ''; ?>">
                    <span class="nav-icon">💰</span>
                    <span>Input Penjualan</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>
                    <?php 
                    if ($page === 'dashboard') echo 'Dashboard Inventory';
                    elseif ($page === 'input_barang') echo 'Input Barang';
                    elseif ($page === 'input_penjualan') echo 'Input Penjualan';
                    ?>
                </h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($page === 'dashboard'): ?>
                    <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 30px;">
                        <form method="GET">
                            <input type="hidden" name="page" value="dashboard">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Period</label>
                                    <select name="period" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                        <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>
                                <div style="padding-top: 32px;">
                                    <button type="submit" class="btn-add">🔍 Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #3498db;">📊</div>
                            <div class="stat-info">
                                <h3><?php echo number_format($total_transaksi); ?></h3>
                                <p>Total Transaksi</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #27ae60;">💰</div>
                            <div class="stat-info">
                                <h3>Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></h3>
                                <p>Total Penjualan</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #9b59b6;">📈</div>
                            <div class="stat-info">
                                <h3>Rp <?php echo $total_transaksi > 0 ? number_format($total_penjualan / $total_transaksi, 0, ',', '.') : 0; ?></h3>
                                <p>Rata-rata</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #e74c3c;">📅</div>
                            <div class="stat-info">
                                <h3 style="font-size: 16px;"><?php echo date('d M', strtotime($start_date)); ?> - <?php echo date('d M', strtotime($end_date)); ?></h3>
                                <p>Period</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 30px;">
                        <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 20px;">📈 Grafik Penjualan</h2>
                        <canvas id="salesChart" height="80"></canvas>
                    </div>
                    
                    <div class="table-container">
                        <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 20px;">📋 Summary per Reseller</h2>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Reseller</th>
                                    <th>Total Transaksi</th>
                                    <th>Total Penjualan</th>
                                    <th>Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($sales_data) {
                                    $sales_data->data_seek(0);
                                    while ($row = $sales_data->fetch_assoc()): 
                                        $avg = $row['total_transaksi'] > 0 ? $row['total_penjualan'] / $row['total_transaksi'] : 0;
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_reseller']); ?></td>
                                    <td><?php echo number_format($row['total_transaksi']); ?></td>
                                    <td>Rp <?php echo number_format($row['total_penjualan'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($avg, 0, ',', '.'); ?></td>
                                </tr>
                                <?php 
                                    endwhile;
                                }
                                if (!$sales_data || $sales_data->num_rows === 0): 
                                ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">Tidak ada data</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <script>
                        const ctx = document.getElementById('salesChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode($chart_labels); ?>,
                                datasets: [
                                    <?php
                                    $colors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a'];
                                    $colorIndex = 0;
                                    foreach ($reseller_data as $reseller => $data) {
                                        $values = [];
                                        foreach ($chart_labels as $label) {
                                            $values[] = isset($data[$label]) ? $data[$label] : 0;
                                        }
                                        $color = $colors[$colorIndex % count($colors)];
                                        echo "{
                                            label: '" . addslashes($reseller) . "',
                                            data: " . json_encode($values) . ",
                                            borderColor: '$color',
                                            backgroundColor: '$color' + '20',
                                            tension: 0.4
                                        },";
                                        $colorIndex++;
                                    }
                                    ?>
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { position: 'top' }
                                }
                            }
                        });
                    </script>
                    
                <?php elseif ($page === 'input_barang'): ?>
                    <div class="form-container">
                        <h2>📥 Form Input Barang</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="input_barang">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Produk</label>
                                <select name="produk_id" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php 
                                    if ($products) {
                                        $products->data_seek(0);
                                        while ($p = $products->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $p['produk_id']; ?>">
                                            <?php echo htmlspecialchars($p['nama_produk']); ?> (Stok: <?php echo $p['stok']; ?>)
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" name="qty" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">💾 Simpan</button>
                                <a href="?page=dashboard" class="btn-cancel">❌ Batal</a>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($page === 'input_penjualan'): ?>
                    <div class="form-container">
                        <h2>💰 Form Input Penjualan</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="input_penjualan">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_penjualan" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Reseller</label>
                                <select name="reseller_id" required>
                                    <option value="">-- Pilih Reseller --</option>
                                    <?php 
                                    if ($resellers) {
                                        $resellers->data_seek(0);
                                        while ($r = $resellers->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $r['reseller_id']; ?>">
                                            <?php echo htmlspecialchars($r['nama_reseller']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Produk</label>
                                <select name="produk_id_penjualan" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php 
                                    if ($products) {
                                        $products->data_seek(0);
                                        while ($p = $products->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $p['produk_id']; ?>">
                                            <?php echo htmlspecialchars($p['nama_produk']); ?> - Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" name="qty_penjualan" min="1" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">💰 Proses Penjualan</button>
                                <a href="?page=dashboard" class="btn-cancel">❌ Batal</a>
                            </div>
                        </form>
                    </div>
                    
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
