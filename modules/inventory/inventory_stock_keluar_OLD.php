<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Handle form submissions
$message = '';
$error = '';

// Handle Stock Keluar (Pengeluaran Stok Non-Penjualan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'stock_keluar') {
    $tanggal = $_POST['tanggal'];
    $produk_id = $_POST['produk_id'];
    $qty = $_POST['qty'];
    $alasan = $_POST['alasan'];
    $keterangan = $_POST['keterangan'] ?? '';
    
    // Get cabang_asal based on user role
    // Administrator & Manager: can select any branch
    // Others: use their own branch
    if (in_array($user['role'], ['administrator', 'manager'])) {
        $cabang_asal = $_POST['cabang_asal'] ?? null;
    } else {
        // Admin, Staff, Supervisor: cabang asal otomatis dari user cabang
        $cabang_asal = $user['cabang_id'];
    }
    
    // Get cabang_tujuan (only for "Pindah Gudang")
    $cabang_tujuan = ($alasan === 'Pindah Gudang') ? ($_POST['cabang_tujuan'] ?? null) : null;
    
    // Get current stock
    $stmt = $conn->prepare("SELECT nama_produk, stok FROM produk WHERE produk_id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Produk tidak ditemukan!";
    } else {
        $produk = $result->fetch_assoc();
        $stok_sebelum = $produk['stok'];
        
        if ($stok_sebelum < $qty) {
            $error = "Stok tidak mencukupi! Stok tersedia: " . $stok_sebelum;
        } else {
            $stok_sesudah = $stok_sebelum - $qty;
            
            // Update product stock
            $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE produk_id = ?");
            $stmt->bind_param("ii", $stok_sesudah, $produk_id);
            $stmt->execute();
            
            // Generate reference number
            $referensi = 'KELUAR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Build keterangan
            $full_keterangan = "Stock Keluar - Alasan: " . $alasan;
            if ($alasan === 'Pindah Gudang' && $cabang_tujuan) {
                $stmt_tujuan = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
                $stmt_tujuan->bind_param("i", $cabang_tujuan);
                $stmt_tujuan->execute();
                $result_tujuan = $stmt_tujuan->get_result();
                if ($result_tujuan->num_rows > 0) {
                    $cabang_tujuan_data = $result_tujuan->fetch_assoc();
                    $full_keterangan .= " ke " . $cabang_tujuan_data['nama_cabang'];
                }
            }
            if (!empty($keterangan)) {
                $full_keterangan .= " | " . $keterangan;
            }
            
            // Insert inventory record for cabang asal (stock keluar)
            if ($cabang_asal) {
                $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id, cabang_id) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiisssii", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id'], $cabang_asal);
            } else {
                $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiissssi", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id']);
            }
            
            if ($stmt->execute()) {
                // If "Pindah Gudang", create inventory record for cabang tujuan (stock masuk)
                if ($alasan === 'Pindah Gudang' && $cabang_tujuan) {
                    $referensi_masuk = 'MASUK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $keterangan_masuk = "Stock Masuk - Pindah Gudang dari Cabang Asal | Ref: " . $referensi;
                    
                    // Get current stock for destination (should be same as stok_sesudah from source)
                    $stok_sebelum_tujuan = $stok_sesudah; // Assuming same product
                    $stok_sesudah_tujuan = $stok_sebelum_tujuan + $qty;
                    
                    $stmt_masuk = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, user_id, cabang_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_masuk->bind_param("isiisssii", $produk_id, $tanggal, $qty, $stok_sebelum_tujuan, $stok_sesudah_tujuan, $referensi_masuk, $keterangan_masuk, $user['user_id'], $cabang_tujuan);
                    $stmt_masuk->execute();
                    
                    $message = "Pindah gudang berhasil! Ref Keluar: " . $referensi . " | Ref Masuk: " . $referensi_masuk . " | Produk: " . $produk['nama_produk'] . " | Qty: " . $qty;
                } else {
                    $message = "Stock keluar berhasil dicatat! Ref: " . $referensi . " | Produk: " . $produk['nama_produk'] . " | Qty: " . $qty;
                }
            } else {
                $error = "Gagal mencatat stock keluar: " . $conn->error;
            }
        }
    }
}

// Get products for dropdown
$products = $conn->query("SELECT produk_id, kode_produk, nama_produk, harga, stok FROM produk WHERE status = 'active' ORDER BY nama_produk");

// Get cabang for dropdown (for administrator/staff)
$cabang_list = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");

// Get stock keluar history with filter
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build history query with role-based filter
$history_query = "
    SELECT 
        i.inventory_id,
        i.tanggal,
        i.jumlah,
        i.stok_sebelum,
        i.stok_sesudah,
        i.referensi,
        i.keterangan,
        p.nama_produk,
        p.kategori,
        COALESCE(c.nama_cabang, '-') as cabang_asal,
        u.full_name as user_name,
        (
            SELECT COALESCE(c2.nama_cabang, NULL)
            FROM inventory i2 
            LEFT JOIN cabang c2 ON i2.cabang_id = c2.cabang_id
            WHERE i2.tipe_transaksi = 'masuk'
            AND i2.keterangan LIKE CONCAT('%Ref: ', i.referensi, '%')
            AND i2.produk_id = i.produk_id
            AND i2.tanggal = i.tanggal
            LIMIT 1
        ) as cabang_tujuan
    FROM inventory i
    LEFT JOIN produk p ON i.produk_id = p.produk_id
    LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
    LEFT JOIN users u ON i.user_id = u.user_id
    WHERE i.tipe_transaksi = 'keluar'
    AND i.referensi LIKE 'KELUAR-%'
    AND i.tanggal BETWEEN ? AND ?";

// Add role-based filter
if (!in_array($user['role'], ['administrator', 'manager'])) {
    $history_query .= " AND (i.cabang_id = " . intval($user['cabang_id']) . " OR i.cabang_id IS NULL)";
}

$history_query .= " ORDER BY i.tanggal DESC, i.inventory_id DESC LIMIT 50";

$stmt = $conn->prepare($history_query);
$stmt->bind_param("ss", $filter_start_date, $filter_end_date);
$stmt->execute();
$history_data = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Keluar - Inventory System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Inventory</h2>
                <p><?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="inventory.php?page=dashboard" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <?php if (in_array($user['role'], ['administrator', 'manager', 'finance'])): ?>
                <a href="inventory.php?page=input_barang" class="nav-item">
                    <span class="nav-icon">📥</span>
                    <span>Input Barang</span>
                </a>
                <?php endif; ?>
                <a href="inventory_stock_masuk.php" class="nav-item">
                    <span class="nav-icon">📥</span>
                    <span>Stock Masuk</span>
                </a>
                <a href="inventory_stock_keluar.php" class="nav-item active">
                    <span class="nav-icon">📤</span>
                    <span>Stock Keluar</span>
                </a>
                <a href="inventory.php?page=input_penjualan" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Input Penjualan</span>
                </a>
                <a href="inventory_stock.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Stock Information</span>
                </a>
                <a href="inventory_laporan.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>Laporan Penjualan</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="../../logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>📤 Stock Keluar</h1>
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
                
                <!-- Form Input Stock Keluar -->
                <div class="form-container">
                    <h2>📤 Form Stock Keluar</h2>
                    <p style="color: #7f8c8d; margin-bottom: 20px;">Catat pengeluaran stok untuk keperluan selain penjualan (rusak, hilang, promosi, dll)</p>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="stock_keluar">
                        
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                        <!-- Administrator & Manager: Bisa pilih cabang asal -->
                        <div class="form-group">
                            <label>Cabang Asal</label>
                            <select name="cabang_asal" id="cabangAsal" required>
                                <option value="">-- Pilih Cabang Asal --</option>
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
                            <small style="color: #7f8c8d; font-size: 12px;">Pilih cabang asal untuk stock keluar</small>
                        </div>
                        <?php else: ?>
                        <!-- Admin, Staff, Supervisor & Finance: Cabang asal otomatis -->
                        <div class="form-group">
                            <label>Cabang Asal</label>
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
                            <small style="color: #7f8c8d; font-size: 12px;">Cabang asal otomatis sesuai dengan akun Anda</small>
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
                            <label>Alasan Pengeluaran</label>
                            <select name="alasan" id="alasanSelect" onchange="toggleCabangTujuan()" required>
                                <option value="">-- Pilih Alasan --</option>
                                <option value="Rusak">Rusak / Expired</option>
                                <option value="Hilang">Hilang</option>
                                <option value="Promosi">Promosi / Sample</option>
                                <option value="Internal">Internal Use</option>
                                <option value="Return">Return ke Supplier</option>
                                <option value="Pindah Gudang">Pindah Gudang</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        
                        <!-- Cabang Tujuan (only show when "Pindah Gudang" selected) -->
                        <div class="form-group" id="cabangTujuanGroup" style="display: none;">
                            <label>Cabang Tujuan</label>
                            <select name="cabang_tujuan" id="cabangTujuan">
                                <option value="">-- Pilih Cabang Tujuan --</option>
                                <?php 
                                if ($cabang_list) {
                                    $cabang_list->data_seek(0);
                                    while ($c = $cabang_list->fetch_assoc()): 
                                        // Untuk selain administrator & manager, exclude cabang sendiri dari pilihan
                                        if (!in_array($user['role'], ['administrator', 'manager']) && $c['cabang_id'] == $user['cabang_id']) {
                                            continue; // Skip cabang sendiri
                                        }
                                ?>
                                    <option value="<?php echo $c['cabang_id']; ?>">
                                        <?php echo htmlspecialchars($c['nama_cabang']); ?>
                                    </option>
                                <?php endwhile; } ?>
                            </select>
                            <small style="color: #7f8c8d; font-size: 12px;">
                                <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                                    Pilih cabang tujuan untuk pindah gudang
                                <?php else: ?>
                                    Pilih cabang tujuan (tidak termasuk cabang Anda sendiri)
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Keterangan Tambahan</label>
                            <textarea name="keterangan" rows="3" placeholder="Keterangan detail (opsional)"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">💾 Simpan Stock Keluar</button>
                            <a href="inventory.php?page=dashboard" class="btn-cancel">❌ Batal</a>
                        </div>
                    </form>
                </div>
                
                <script>
                function toggleCabangTujuan() {
                    const alasan = document.getElementById('alasanSelect').value;
                    const cabangTujuanGroup = document.getElementById('cabangTujuanGroup');
                    const cabangTujuanSelect = document.getElementById('cabangTujuan');
                    
                    if (alasan === 'Pindah Gudang') {
                        cabangTujuanGroup.style.display = 'block';
                        if (cabangTujuanSelect) {
                            cabangTujuanSelect.required = true;
                        }
                    } else {
                        cabangTujuanGroup.style.display = 'none';
                        if (cabangTujuanSelect) {
                            cabangTujuanSelect.required = false;
                            cabangTujuanSelect.value = '';
                        }
                    }
                }
                
                // Validate form before submit
                document.querySelector('form').addEventListener('submit', function(e) {
                    const alasan = document.getElementById('alasanSelect').value;
                    const cabangAsal = document.getElementById('cabangAsal');
                    const cabangTujuan = document.getElementById('cabangTujuan');
                    
                    if (alasan === 'Pindah Gudang') {
                        if (cabangAsal && cabangTujuan) {
                            if (cabangAsal.value === cabangTujuan.value) {
                                e.preventDefault();
                                alert('Cabang asal dan tujuan tidak boleh sama!');
                                return false;
                            }
                        }
                    }
                    
                    return confirm('Proses stock keluar ini?');
                });
                </script>
                
                <!-- Riwayat Stock Keluar -->
                <div style="margin-top: 40px;">
                    <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
                        <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 20px;">📋 Riwayat Stock Keluar</h2>
                        <form method="GET" style="margin-bottom: 20px;">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Mulai</label>
                                    <input type="date" name="start_date" value="<?php echo $filter_start_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Akhir</label>
                                    <input type="date" name="end_date" value="<?php echo $filter_end_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
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
                                    <th>Referensi</th>
                                    <th>Produk</th>
                                    <th>Cabang Asal</th>
                                    <th>Cabang Tujuan</th>
                                    <th>Qty</th>
                                    <th>Stok Sebelum</th>
                                    <th>Stok Sesudah</th>
                                    <th>Alasan</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($history_data && $history_data->num_rows > 0) {
                                    while ($row = $history_data->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td><strong style="color: #e74c3c;"><?php echo htmlspecialchars($row['referensi']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nama_produk']); ?></strong><br>
                                        <small style="color: #7f8c8d;"><?php echo htmlspecialchars($row['kategori']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['cabang_asal']); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        // Extract cabang tujuan from keterangan if exists
                                        $cabang_tujuan_display = $row['cabang_tujuan'];
                                        
                                        // If subquery didn't find it, try to extract from keterangan
                                        if (!$cabang_tujuan_display && strpos($row['keterangan'], 'Pindah Gudang ke') !== false) {
                                            // Extract cabang name from keterangan like "Stock Keluar - Alasan: Pindah Gudang ke Jakarta Pusat"
                                            preg_match('/Pindah Gudang ke (.+?)(\||$)/', $row['keterangan'], $matches);
                                            if (isset($matches[1])) {
                                                $cabang_tujuan_display = trim($matches[1]);
                                            }
                                        }
                                        
                                        if ($cabang_tujuan_display): 
                                        ?>
                                            <span style="background: #d1ecf1; color: #0c5460; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 600;">
                                                → <?php echo htmlspecialchars($cabang_tujuan_display); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #adb5bd;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong style="color: #e74c3c;"><?php echo number_format($row['jumlah']); ?></strong></td>
                                    <td><?php echo number_format($row['stok_sebelum']); ?></td>
                                    <td><?php echo number_format($row['stok_sesudah']); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($row['keterangan']); ?></small>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($row['user_name']); ?></small></td>
                                </tr>
                                <?php 
                                    endwhile;
                                } else { 
                                ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                        Tidak ada data stock keluar untuk periode ini
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Info Box -->
                <div style="background: #fff3cd; padding: 20px; border-radius: 12px; margin-top: 30px; border-left: 4px solid #ffc107;">
                    <h3 style="margin: 0 0 10px 0; color: #856404;">ℹ️ Informasi</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #856404;">
                        <li>Stock Keluar digunakan untuk mencatat pengeluaran stok <strong>selain penjualan</strong></li>
                        <li>Pilih alasan yang sesuai untuk memudahkan tracking</li>
                        <li><strong>Pindah Gudang:</strong> Otomatis create 2 transaksi (keluar dari cabang asal, masuk ke cabang tujuan)</li>
                        <li><strong>Role Admin, Staff, Supervisor & Finance:</strong> Cabang asal otomatis, bisa pilih cabang tujuan lain untuk pindah gudang</li>
                        <li><strong>Role Administrator & Manager:</strong> Bisa pilih cabang asal & tujuan bebas</li>
                        <li>Stok akan otomatis berkurang setelah transaksi disimpan</li>
                        <li>Semua transaksi tercatat dengan nomor referensi unik</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
