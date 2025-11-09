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

// Handle Input Barang (Stok Masuk) - UPDATED WITH CABANG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'input_barang') {
    $tanggal = $_POST['tanggal'];
    $produk_id = $_POST['produk_id'];
    $qty = $_POST['qty'];
    $keterangan = $_POST['keterangan'] ?? '';
    
    // Get cabang_id based on user role
    if ($user['role'] === 'admin') {
        $cabang_id = $user['cabang_id'];
    } else {
        $cabang_id = $_POST['cabang_id'] ?? null;
    }
    
    // Get current stock
    $stmt = $conn->prepare("SELECT stok FROM produk WHERE produk_id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();
    $stok_sebelum = $produk['stok'];
    $stok_sesudah = $stok_sebelum + $qty;
    
    // Update product stock
    $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE produk_id = ?");
    $stmt->bind_param("ii", $stok_sesudah, $produk_id);
    $stmt->execute();
    
    // Insert inventory record WITH cabang_id
    if ($cabang_id) {
        $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id, cabang_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiissii", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $keterangan, $user['user_id'], $cabang_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiissi", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $keterangan, $user['user_id']);
    }
    
    if ($stmt->execute()) {
        $message = "Stok barang berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan stok: " . $conn->error;
    }
}

// Handle Input Penjualan (Multiple Products) - NEW
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'input_penjualan') {
    $tanggal = $_POST['tanggal_penjualan'];
    $reseller_id = $_POST['reseller_id'];
    $produk_ids = $_POST['produk_id'] ?? [];
    $quantities = $_POST['qty'] ?? [];
    
    if (empty($produk_ids) || empty($quantities)) {
        $error = "Minimal harus ada 1 produk!";
    } else {
        $valid = true;
        $products_data = [];
        $subtotal_all = 0;
        
        foreach ($produk_ids as $index => $produk_id) {
            if (empty($produk_id) || empty($quantities[$index]) || $quantities[$index] <= 0) continue;
            
            $qty = $quantities[$index];
            $stmt = $conn->prepare("SELECT nama_produk, harga, stok FROM produk WHERE produk_id = ?");
            $stmt->bind_param("i", $produk_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "Produk tidak ditemukan!";
                $valid = false;
                break;
            }
            
            $produk = $result->fetch_assoc();
            
            if ($produk['stok'] < $qty) {
                $error = "Stok tidak mencukupi untuk " . $produk['nama_produk'] . "! Stok tersedia: " . $produk['stok'];
                $valid = false;
                break;
            }
            
            $subtotal = $produk['harga'] * $qty;
            $subtotal_all += $subtotal;
            
            $products_data[] = [
                'produk_id' => $produk_id,
                'nama_produk' => $produk['nama_produk'],
                'harga' => $produk['harga'],
                'qty' => $qty,
                'subtotal' => $subtotal,
                'stok_sebelum' => $produk['stok']
            ];
        }
        
        if (empty($products_data)) {
            $error = "Minimal harus ada 1 produk yang diisi!";
            $valid = false;
        }
        
        if ($valid && !empty($products_data)) {
            $no_invoice = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("SELECT nama_reseller FROM reseller WHERE reseller_id = ?");
            $stmt->bind_param("i", $reseller_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $reseller = $result->fetch_assoc();
            
            $stmt = $conn->prepare("SELECT pelanggan_id FROM pelanggan WHERE nama_pelanggan = ?");
            $stmt->bind_param("s", $reseller['nama_reseller']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $pelanggan = $result->fetch_assoc();
                $pelanggan_id = $pelanggan['pelanggan_id'];
            } else {
                $kode_pelanggan = 'CUST-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $stmt = $conn->prepare("INSERT INTO pelanggan (kode_pelanggan, nama_pelanggan, phone, tipe_pelanggan) VALUES (?, ?, '000000000', 'corporate')");
                $stmt->bind_param("ss", $kode_pelanggan, $reseller['nama_reseller']);
                $stmt->execute();
                $pelanggan_id = $conn->insert_id;
            }
            
            $stmt = $conn->prepare("INSERT INTO penjualan (no_invoice, tanggal_penjualan, pelanggan_id, user_id, reseller_id, subtotal, total, metode_pembayaran, status_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?, 'transfer', 'paid')");
            $stmt->bind_param("ssiiidd", $no_invoice, $tanggal, $pelanggan_id, $user['user_id'], $reseller_id, $subtotal_all, $subtotal_all);
            $stmt->execute();
            $penjualan_id = $conn->insert_id;
            
            foreach ($products_data as $prod) {
                $stmt = $conn->prepare("INSERT INTO detail_penjualan (penjualan_id, produk_id, nama_produk, harga_satuan, jumlah, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdid", $penjualan_id, $prod['produk_id'], $prod['nama_produk'], $prod['harga'], $prod['qty'], $prod['subtotal']);
                $stmt->execute();
                
                $stok_sesudah = $prod['stok_sebelum'] - $prod['qty'];
                $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE produk_id = ?");
                $stmt->bind_param("ii", $stok_sesudah, $prod['produk_id']);
                $stmt->execute();
                
                $referensi = $no_invoice;
                $keterangan = "Penjualan ke reseller: " . $reseller['nama_reseller'];
                $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiisssi", $prod['produk_id'], $tanggal, $prod['qty'], $prod['stok_sebelum'], $stok_sesudah, $referensi, $keterangan, $user['user_id']);
                $stmt->execute();
            }
            
            $message = "Penjualan berhasil! No Invoice: " . $no_invoice . " | Total Produk: " . count($products_data) . " | Total: Rp " . number_format($subtotal_all, 0, ',', '.');
        }
    }
}

// Get products and resellers for dropdown
$products = $conn->query("SELECT produk_id, kode_produk, nama_produk, harga, stok FROM produk WHERE status = 'active' ORDER BY nama_produk");
$resellers = $conn->query("SELECT reseller_id, kode_reseller, nama_reseller FROM reseller WHERE status = 'active' ORDER BY nama_reseller");

// Get cabang for dropdown (for administrator/staff)
$cabang_list = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");

// Get penjualan data for report (on input_penjualan page)
$penjualan_data = null;
$penjualan_start_date = isset($_GET['penjualan_start']) ? $_GET['penjualan_start'] : date('Y-m-d', strtotime('-7 days'));
$penjualan_end_date = isset($_GET['penjualan_end']) ? $_GET['penjualan_end'] : date('Y-m-d');

if ($page === 'input_penjualan') {
    $penjualan_query = "
        SELECT 
            p.penjualan_id,
            p.no_invoice,
            p.tanggal_penjualan,
            r.nama_reseller,
            c.nama_cabang,
            dp.nama_produk,
            dp.jumlah,
            dp.harga_satuan,
            dp.subtotal,
            p.total as total_invoice
        FROM penjualan p
        JOIN reseller r ON p.reseller_id = r.reseller_id
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN cabang c ON u.cabang_id = c.cabang_id
        JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
        WHERE p.tanggal_penjualan BETWEEN ? AND ?
        ORDER BY p.tanggal_penjualan DESC, p.penjualan_id DESC, dp.detail_id ASC
    ";
    
    $stmt = $conn->prepare($penjualan_query);
    $stmt->bind_param("ss", $penjualan_start_date, $penjualan_end_date);
    $stmt->execute();
    $penjualan_data = $stmt->get_result();
}

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
                            
                            <?php if ($user['role'] === 'administrator' || $user['role'] === 'staff'): ?>
                            <!-- Dropdown Cabang untuk Administrator & Staff -->
                            <div class="form-group">
                                <label>Cabang</label>
                                <select name="cabang_id" required>
                                    <option value="">-- Pilih Cabang --</option>
                                    <?php 
                                    if ($cabang_list) {
                                        $cabang_list->data_seek(0);
                                        while ($c = $cabang_list->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $c['cabang_id']; ?>">
                                            <?php echo htmlspecialchars($c['nama_cabang']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <!-- Readonly Cabang untuk Admin -->
                            <div class="form-group">
                                <label>Cabang</label>
                                <input type="text" value="<?php 
                                    $stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
                                    $stmt->bind_param("i", $user['cabang_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows > 0) {
                                        $cabang = $result->fetch_assoc();
                                        echo htmlspecialchars($cabang['nama_cabang']);
                                    } else {
                                        echo 'Cabang tidak ditemukan';
                                    }
                                ?>" readonly style="background: #f8f9fa; cursor: not-allowed;">
                                <small style="color: #7f8c8d; font-size: 12px;">Cabang otomatis sesuai dengan akun Anda</small>
                            </div>
                            <?php endif; ?>
                            
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
                                <input type="number" name="qty" min="1" required placeholder="Masukkan jumlah">
                            </div>
                            
                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan" placeholder="Keterangan tambahan (opsional)"></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">💾 Simpan</button>
                                <a href="?page=dashboard" class="btn-cancel">❌ Batal</a>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($page === 'input_penjualan'): ?>
                    <div class="form-container" style="max-width: 900px;">
                        <h2>💰 Form Input Penjualan</h2>
                        <p style="color: #7f8c8d; margin-bottom: 20px;">Input penjualan untuk 1 reseller dengan multiple produk</p>
                        
                        <form method="POST" id="penjualanForm">
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
                            
                            <hr style="margin: 25px 0; border: none; border-top: 2px solid #e9ecef;">
                            
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">📦 Daftar Produk</h3>
                            
                            <div id="productContainer">
                                <div class="product-row" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <h4 style="margin: 0; color: #2c3e50;">Produk #1</h4>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px;">
                                        <div class="form-group" style="margin: 0;">
                                            <label>Produk</label>
                                            <select name="produk_id[]" class="produk-select" onchange="calculateSubtotal(this)" required>
                                                <option value="">-- Pilih Produk --</option>
                                                <?php 
                                                if ($products) {
                                                    $products->data_seek(0);
                                                    while ($p = $products->fetch_assoc()): 
                                                ?>
                                                    <option value="<?php echo $p['produk_id']; ?>" data-harga="<?php echo $p['harga']; ?>" data-stok="<?php echo $p['stok']; ?>">
                                                        <?php echo htmlspecialchars($p['nama_produk']); ?> - Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?>
                                                    </option>
                                                <?php endwhile; } ?>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label>Qty</label>
                                            <input type="number" name="qty[]" class="qty-input" min="1" onchange="calculateSubtotal(this)" required>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label>Subtotal</label>
                                            <input type="text" class="subtotal-display" readonly style="background: #e8f5e9; font-weight: 600; color: #27ae60;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" onclick="addProduct()" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-bottom: 20px; font-weight: 500;">
                                ➕ Tambah Produk
                            </button>
                            
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;">
                                <h3 style="margin: 0 0 10px 0; font-size: 16px; opacity: 0.9;">TOTAL KESELURUHAN</h3>
                                <div id="grandTotal" style="font-size: 32px; font-weight: 700;">Rp 0</div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">💰 Proses Penjualan</button>
                                <a href="?page=dashboard" class="btn-cancel">❌ Batal</a>
                            </div>
                        </form>
                    </div>
                    
                    <script>
                    let productCount = 1;
                    
                    function addProduct() {
                        productCount++;
                        const container = document.getElementById('productContainer');
                        const newRow = document.createElement('div');
                        newRow.className = 'product-row';
                        newRow.style.cssText = 'background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;';
                        
                        newRow.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="margin: 0; color: #2c3e50;">Produk #${productCount}</h4>
                                <button type="button" onclick="removeProduct(this)" style="background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">🗑️ Hapus</button>
                            </div>
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px;">
                                <div class="form-group" style="margin: 0;">
                                    <label>Produk</label>
                                    <select name="produk_id[]" class="produk-select" onchange="calculateSubtotal(this)" required>
                                        <option value="">-- Pilih Produk --</option>
                                        <?php 
                                        if ($products) {
                                            $products->data_seek(0);
                                            while ($p = $products->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $p['produk_id']; ?>" data-harga="<?php echo $p['harga']; ?>" data-stok="<?php echo $p['stok']; ?>">
                                                <?php echo htmlspecialchars($p['nama_produk']); ?> - Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?>
                                            </option>
                                        <?php endwhile; } ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Qty</label>
                                    <input type="number" name="qty[]" class="qty-input" min="1" onchange="calculateSubtotal(this)" required>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Subtotal</label>
                                    <input type="text" class="subtotal-display" readonly style="background: #e8f5e9; font-weight: 600; color: #27ae60;">
                                </div>
                            </div>
                        `;
                        
                        container.appendChild(newRow);
                        updateRemoveButtons();
                    }
                    
                    function removeProduct(btn) {
                        const row = btn.closest('.product-row');
                        row.remove();
                        
                        const rows = document.querySelectorAll('.product-row');
                        rows.forEach((row, index) => {
                            row.querySelector('h4').textContent = `Produk #${index + 1}`;
                        });
                        
                        productCount = rows.length;
                        updateRemoveButtons();
                        calculateGrandTotal();
                    }
                    
                    function updateRemoveButtons() {
                        const rows = document.querySelectorAll('.product-row');
                        rows.forEach((row, index) => {
                            const removeBtn = row.querySelector('button[onclick^="removeProduct"]');
                            if (removeBtn) {
                                removeBtn.style.display = rows.length === 1 ? 'none' : 'inline-block';
                            }
                        });
                    }
                    
                    function calculateSubtotal(element) {
                        const row = element.closest('.product-row');
                        const produkSelect = row.querySelector('.produk-select');
                        const qtyInput = row.querySelector('.qty-input');
                        const subtotalDisplay = row.querySelector('.subtotal-display');
                        
                        const produkId = produkSelect.value;
                        const qty = parseInt(qtyInput.value) || 0;
                        
                        if (produkId && qty > 0) {
                            const selectedOption = produkSelect.options[produkSelect.selectedIndex];
                            const harga = parseInt(selectedOption.getAttribute('data-harga'));
                            const stok = parseInt(selectedOption.getAttribute('data-stok'));
                            
                            if (qty > stok) {
                                alert(`Stok tidak mencukupi! Stok tersedia: ${stok}`);
                                qtyInput.value = stok;
                                return;
                            }
                            
                            const subtotal = harga * qty;
                            subtotalDisplay.value = 'Rp ' + subtotal.toLocaleString('id-ID');
                        } else {
                            subtotalDisplay.value = '';
                        }
                        
                        calculateGrandTotal();
                    }
                    
                    function calculateGrandTotal() {
                        let total = 0;
                        
                        document.querySelectorAll('.product-row').forEach(row => {
                            const produkSelect = row.querySelector('.produk-select');
                            const qtyInput = row.querySelector('.qty-input');
                            
                            const produkId = produkSelect.value;
                            const qty = parseInt(qtyInput.value) || 0;
                            
                            if (produkId && qty > 0) {
                                const selectedOption = produkSelect.options[produkSelect.selectedIndex];
                                const harga = parseInt(selectedOption.getAttribute('data-harga'));
                                total += harga * qty;
                            }
                        });
                        
                        document.getElementById('grandTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
                    }
                    
                    document.getElementById('penjualanForm').addEventListener('submit', function(e) {
                        const rows = document.querySelectorAll('.product-row');
                        let hasProduct = false;
                        
                        rows.forEach(row => {
                            const produkId = row.querySelector('.produk-select').value;
                            const qty = row.querySelector('.qty-input').value;
                            
                            if (produkId && qty) {
                                hasProduct = true;
                            }
                        });
                        
                        if (!hasProduct) {
                            e.preventDefault();
                            alert('Minimal harus ada 1 produk yang diisi!');
                            return false;
                        }
                        
                        return confirm('Proses penjualan ini?');
                    });
                    
                    updateRemoveButtons();
                    </script>
                    
                    <!-- Laporan Penjualan -->
                    <div style="margin-top: 40px;">
                        <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
                            <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 20px;">📋 Laporan Penjualan</h2>
                            <form method="GET" style="margin-bottom: 20px;">
                                <input type="hidden" name="page" value="input_penjualan">
                                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Mulai</label>
                                        <input type="date" name="penjualan_start" value="<?php echo $penjualan_start_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                    </div>
                                    <div style="flex: 1; min-width: 200px;">
                                        <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Akhir</label>
                                        <input type="date" name="penjualan_end" value="<?php echo $penjualan_end_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                    </div>
                                    <div>
                                        <button type="submit" class="btn-add">🔍 Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Invoice</th>
                                        <th>Reseller</th>
                                        <th>Cabang</th>
                                        <th>Produk</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $grand_total = 0;
                                    $current_invoice = '';
                                    $invoice_total = 0;
                                    $invoice_count = 0;
                                    
                                    if ($penjualan_data && $penjualan_data->num_rows > 0) {
                                        while ($row = $penjualan_data->fetch_assoc()) { 
                                            // Check if this is a new invoice
                                            if ($current_invoice !== $row['no_invoice']) {
                                                // Display subtotal for previous invoice if exists
                                                if ($current_invoice !== '' && $invoice_count > 1) {
                                                    ?>
                                                    <tr style="background: #f0f8ff; font-weight: 600;">
                                                        <td colspan="6" style="text-align: right; padding: 10px; font-size: 13px;">Subtotal Invoice <?php echo htmlspecialchars($current_invoice); ?>:</td>
                                                        <td style="color: #3498db;"><strong>Rp <?php echo number_format($invoice_total, 0, ',', '.'); ?></strong></td>
                                                    </tr>
                                                    <?php
                                                }
                                                
                                                $current_invoice = $row['no_invoice'];
                                                $invoice_total = $row['total_invoice'];
                                                $invoice_count = 0;
                                                $grand_total += $row['total_invoice'];
                                            }
                                            
                                            $invoice_count++;
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_penjualan'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['no_invoice']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['nama_reseller']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_cabang'] ?? '-'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['nama_produk']); ?></strong><br>
                                            <small style="color: #7f8c8d;">
                                                <?php echo $row['jumlah']; ?> × Rp <?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?> 
                                                = Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?>
                                            </small>
                                        </td>
                                        <td><strong>Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?></strong></td>
                                    </tr>
                                    <?php 
                                        }
                                        
                                        // Display subtotal for last invoice if it has multiple products
                                        if ($invoice_count > 1) {
                                            ?>
                                            <tr style="background: #f0f8ff; font-weight: 600;">
                                                <td colspan="6" style="text-align: right; padding: 10px; font-size: 13px;">Subtotal Invoice <?php echo htmlspecialchars($current_invoice); ?>:</td>
                                                <td style="color: #3498db;"><strong>Rp <?php echo number_format($invoice_total, 0, ',', '.'); ?></strong></td>
                                            </tr>
                                            <?php
                                        }
                                    } else { ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                            Tidak ada data penjualan untuk periode ini
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                <?php if ($penjualan_data && $penjualan_data->num_rows > 0): ?>
                                <tfoot>
                                    <tr style="background: #f8f9fa; font-weight: 600;">
                                        <td colspan="6" style="text-align: right; padding: 15px;">GRAND TOTAL:</td>
                                        <td style="color: #27ae60; font-size: 16px;"><strong>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
