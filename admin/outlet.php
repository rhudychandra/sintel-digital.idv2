<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

if ($user['role'] !== 'administrator') {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getDBConnection();
$message = '';
$action = $_GET['action'] ?? 'list';
$outlet_id = $_GET['id'] ?? null;

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO outlet (nama_outlet, nomor_rs, id_digipos, nik_ktp, kelurahan_desa, kecamatan, city, nama_pemilik, nomor_hp_pemilik, type_outlet, jadwal_kategori, hari, sales_force_id, cabang_id, status_outlet, jenis_rs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $sales_force = !empty($_POST['sales_force_id']) ? $_POST['sales_force_id'] : null;
            $cabang = !empty($_POST['cabang_id']) ? $_POST['cabang_id'] : null;
            $nik = !empty($_POST['nik_ktp']) ? $_POST['nik_ktp'] : null;
            
            $stmt->bind_param("ssssssssssssiiss", 
                $_POST['nama_outlet'], 
                $_POST['nomor_rs'], 
                $_POST['id_digipos'], 
                $nik,
                $_POST['kelurahan_desa'], 
                $_POST['kecamatan'], 
                $_POST['city'], 
                $_POST['nama_pemilik'], 
                $_POST['nomor_hp_pemilik'], 
                $_POST['type_outlet'], 
                $_POST['jadwal_kategori'], 
                $_POST['hari'], 
                $sales_force, 
                $cabang, 
                $_POST['status_outlet'], 
                $_POST['jenis_rs']
            );
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Outlet berhasil ditambahkan!";
                $stmt->close();
                header('Location: outlet.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $conn->prepare("UPDATE outlet SET nama_outlet=?, nomor_rs=?, id_digipos=?, nik_ktp=?, kelurahan_desa=?, kecamatan=?, city=?, nama_pemilik=?, nomor_hp_pemilik=?, type_outlet=?, jadwal_kategori=?, hari=?, sales_force_id=?, cabang_id=?, status_outlet=?, jenis_rs=? WHERE outlet_id=?");
            
            $sales_force = !empty($_POST['sales_force_id']) ? $_POST['sales_force_id'] : null;
            $cabang = !empty($_POST['cabang_id']) ? $_POST['cabang_id'] : null;
            $nik = !empty($_POST['nik_ktp']) ? $_POST['nik_ktp'] : null;
            
            $stmt->bind_param("ssssssssssssiissi", 
                $_POST['nama_outlet'], 
                $_POST['nomor_rs'], 
                $_POST['id_digipos'], 
                $nik,
                $_POST['kelurahan_desa'], 
                $_POST['kecamatan'], 
                $_POST['city'], 
                $_POST['nama_pemilik'], 
                $_POST['nomor_hp_pemilik'], 
                $_POST['type_outlet'], 
                $_POST['jadwal_kategori'], 
                $_POST['hari'], 
                $sales_force, 
                $cabang, 
                $_POST['status_outlet'], 
                $_POST['jenis_rs'],
                $_POST['outlet_id']
            );
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Outlet berhasil diupdate!";
                $stmt->close();
                header('Location: outlet.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM outlet WHERE outlet_id=?");
            $stmt->bind_param("i", $_POST['outlet_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Outlet berhasil dihapus!";
            } else {
                $_SESSION['message'] = "Error: " . $stmt->error;
            }
            $stmt->close();
            header('Location: outlet.php');
            exit();
        }
    }
}

// Get outlet data for edit
$edit_data = null;
if ($action === 'edit' && $outlet_id) {
    $stmt = $conn->prepare("SELECT * FROM outlet WHERE outlet_id=?");
    $stmt->bind_param("i", $outlet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all outlets
$outlets = [];
if ($action === 'list') {
    $result = $conn->query("SELECT o.*, c.nama_cabang, r.nama_reseller as sales_force_name FROM outlet o LEFT JOIN cabang c ON o.cabang_id = c.cabang_id LEFT JOIN reseller r ON o.sales_force_id = r.reseller_id ORDER BY o.outlet_id DESC");
    while ($row = $result->fetch_assoc()) {
        $outlets[] = $row;
    }
}

// Get all branches for dropdown
$branches = [];
$result = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status='active' ORDER BY nama_cabang");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

// Get all sales force (resellers) for dropdown
$sales_forces = [];
$result = $conn->query("SELECT reseller_id, nama_reseller FROM reseller WHERE status='active' ORDER BY nama_reseller");
while ($row = $result->fetch_assoc()) {
    $sales_forces[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Outlet - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
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
                <a href="kategori.php" class="nav-item">
                    <span class="nav-icon">🏷️</span>
                    <span>Kategori</span>
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
                <a href="outlet.php" class="nav-item active">
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
                <a href="grafik.php" class="nav-item">
                    <span class="nav-icon">📉</span>
                    <span>Grafik</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Manajemen Outlet</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($action === 'list'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2>Outlet List Update <?php echo date('F Y'); ?></h2>
                    </div>
                    <div class="header-actions">
                        <a href="outlet.php?action=add" class="btn-primary">
                            <i class="fas fa-plus"></i> Tambah Outlet
                        </a>
                        <a href="upload_outlet_excel.php" class="btn-success">
                            <i class="fas fa-file-excel"></i> Upload Excel
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
            <!-- List View -->
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Outlet</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table outlet-table">
                        <thead>
                            <tr>
                                <th>Nama Outlet</th>
                                <th>Nomor RS</th>
                                <th>ID Digipos</th>
                                <th>NIK KTP</th>
                                <th>Kel/Desa</th>
                                <th>Kecamatan</th>
                                <th>Kota</th>
                                <th>Pemilik</th>
                                <th>No. HP</th>
                                <th>Type Outlet</th>
                                <th>Jadwal Kategori</th>
                                <th>Hari</th>
                                <th>Sales Force</th>
                                <th>Cabang</th>
                                <th>Status</th>
                                <th>Jenis RS</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($outlets)): ?>
                            <tr>
                                <td colspan="17" style="text-align: center; padding: 20px;">
                                    Belum ada data outlet
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($outlets as $outlet): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($outlet['nama_outlet']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($outlet['nomor_rs']); ?></span></td>
                                <td><?php echo htmlspecialchars($outlet['id_digipos'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['nik_ktp'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['kelurahan_desa'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['kecamatan'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['city'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['nama_pemilik']); ?></td>
                                <td><?php echo htmlspecialchars($outlet['nomor_hp_pemilik']); ?></td>
                                <td><?php echo htmlspecialchars($outlet['type_outlet'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['jadwal_kategori'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['hari'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['sales_force_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($outlet['nama_cabang'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $outlet['status_outlet'] === 'PJP' ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo $outlet['status_outlet']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?php echo $outlet['jenis_rs']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="outlet.php?action=edit&id=<?php echo $outlet['outlet_id']; ?>" class="btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h3><?php echo $action === 'add' ? 'Tambah Outlet Baru' : 'Edit Outlet'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="outlet_id" value="<?php echo $edit_data['outlet_id']; ?>">
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nama_outlet">Nama Outlet *</label>
                                <input type="text" id="nama_outlet" name="nama_outlet" required 
                                       value="<?php echo htmlspecialchars($edit_data['nama_outlet'] ?? ''); ?>"
                                       placeholder="Nama toko/outlet">
                            </div>

                            <div class="form-group">
                                <label for="nomor_rs">Nomor RS *</label>
                                <input type="text" id="nomor_rs" name="nomor_rs" required 
                                       value="<?php echo htmlspecialchars($edit_data['nomor_rs'] ?? ''); ?>"
                                       placeholder="RS-001">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_digipos">ID Digipos</label>
                                <input type="text" id="id_digipos" name="id_digipos" 
                                       value="<?php echo htmlspecialchars($edit_data['id_digipos'] ?? ''); ?>"
                                       placeholder="DGP-001">
                            </div>

                            <div class="form-group">
                                <label for="nik_ktp">NIK KTP</label>
                                <input type="text" id="nik_ktp" name="nik_ktp" maxlength="20"
                                       value="<?php echo htmlspecialchars($edit_data['nik_ktp'] ?? ''); ?>"
                                       placeholder="3201012345678901">
                                <small style="color: #7f8c8d;">Opsional - NIK pemilik</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nama_pemilik">Nama Pemilik *</label>
                                <input type="text" id="nama_pemilik" name="nama_pemilik" required 
                                       value="<?php echo htmlspecialchars($edit_data['nama_pemilik'] ?? ''); ?>"
                                       placeholder="Nama lengkap pemilik">
                            </div>

                            <div class="form-group">
                                <label for="nomor_hp_pemilik">Nomor HP Pemilik *</label>
                                <input type="text" id="nomor_hp_pemilik" name="nomor_hp_pemilik" required 
                                       value="<?php echo htmlspecialchars($edit_data['nomor_hp_pemilik'] ?? ''); ?>"
                                       placeholder="081234567890">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="kelurahan_desa">Kelurahan/Desa</label>
                                <input type="text" id="kelurahan_desa" name="kelurahan_desa" 
                                       value="<?php echo htmlspecialchars($edit_data['kelurahan_desa'] ?? ''); ?>"
                                       placeholder="Sukajadi">
                            </div>

                            <div class="form-group">
                                <label for="kecamatan">Kecamatan</label>
                                <input type="text" id="kecamatan" name="kecamatan" 
                                       value="<?php echo htmlspecialchars($edit_data['kecamatan'] ?? ''); ?>"
                                       placeholder="Bandung Wetan">
                            </div>

                            <div class="form-group">
                                <label for="city">Kota *</label>
                                <input type="text" id="city" name="city" required 
                                       value="<?php echo htmlspecialchars($edit_data['city'] ?? ''); ?>"
                                       placeholder="Bandung">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="type_outlet">Type Outlet</label>
                                <select id="type_outlet" name="type_outlet">
                                    <option value="">Pilih Type Outlet</option>
                                    <option value="Konter Pulsa" <?php echo ($edit_data['type_outlet'] ?? '') === 'Konter Pulsa' ? 'selected' : ''; ?>>Konter Pulsa</option>
                                    <option value="Warung" <?php echo ($edit_data['type_outlet'] ?? '') === 'Warung' ? 'selected' : ''; ?>>Warung</option>
                                    <option value="Device" <?php echo ($edit_data['type_outlet'] ?? '') === 'Device' ? 'selected' : ''; ?>>Device</option>
                                    <option value="Kantin" <?php echo ($edit_data['type_outlet'] ?? '') === 'Kantin' ? 'selected' : ''; ?>>Kantin</option>
                                    <option value="Toko" <?php echo ($edit_data['type_outlet'] ?? '') === 'Toko' ? 'selected' : ''; ?>>Toko</option>
                                    <option value="Restoran" <?php echo ($edit_data['type_outlet'] ?? '') === 'Restoran' ? 'selected' : ''; ?>>Restoran</option>
                                    <option value="Personal" <?php echo ($edit_data['type_outlet'] ?? '') === 'Personal' ? 'selected' : ''; ?>>Personal</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="jadwal_kategori">Jadwal Kategori</label>
                                <select id="jadwal_kategori" name="jadwal_kategori">
                                    <option value="">Pilih Jadwal Kategori</option>
                                    <option value="F1 - Kunjungan 1 Bulan sekali" <?php echo ($edit_data['jadwal_kategori'] ?? '') === 'F1 - Kunjungan 1 Bulan sekali' ? 'selected' : ''; ?>>F1 - Kunjungan 1 Bulan sekali</option>
                                    <option value="F2 - Kunjungan 2 Minggu sekali" <?php echo ($edit_data['jadwal_kategori'] ?? '') === 'F2 - Kunjungan 2 Minggu sekali' ? 'selected' : ''; ?>>F2 - Kunjungan 2 Minggu sekali</option>
                                    <option value="F4 - Kunjungan 1 Minggu sekali" <?php echo ($edit_data['jadwal_kategori'] ?? '') === 'F4 - Kunjungan 1 Minggu sekali' ? 'selected' : ''; ?>>F4 - Kunjungan 1 Minggu sekali</option>
                                    <option value="F8 - Kunjungan seminggu 2 kali" <?php echo ($edit_data['jadwal_kategori'] ?? '') === 'F8 - Kunjungan seminggu 2 kali' ? 'selected' : ''; ?>>F8 - Kunjungan seminggu 2 kali</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="hari">Hari Kunjungan</label>
                                <select id="hari" name="hari">
                                    <option value="">Pilih Hari</option>
                                    <?php 
                                    $hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                                    foreach ($hari_list as $hari): 
                                    ?>
                                    <option value="<?php echo $hari; ?>" <?php echo ($edit_data['hari'] ?? '') === $hari ? 'selected' : ''; ?>>
                                        <?php echo $hari; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="sales_force_id">Sales Force</label>
                                <select id="sales_force_id" name="sales_force_id">
                                    <option value="">Pilih Sales Force</option>
                                    <?php foreach ($sales_forces as $sf): ?>
                                    <option value="<?php echo $sf['reseller_id']; ?>" 
                                            <?php echo ($edit_data['sales_force_id'] ?? '') == $sf['reseller_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sf['nama_reseller']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="cabang_id">Cabang</label>
                                <select id="cabang_id" name="cabang_id">
                                    <option value="">Pilih Cabang</option>
                                    <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['cabang_id']; ?>" 
                                            <?php echo ($edit_data['cabang_id'] ?? '') == $branch['cabang_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($branch['nama_cabang']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status_outlet">Status Outlet *</label>
                                <select id="status_outlet" name="status_outlet" required>
                                    <option value="PJP" <?php echo ($edit_data['status_outlet'] ?? '') === 'PJP' ? 'selected' : ''; ?>>PJP</option>
                                    <option value="Non PJP" <?php echo ($edit_data['status_outlet'] ?? 'Non PJP') === 'Non PJP' ? 'selected' : ''; ?>>Non PJP</option>
                                </select>
                                <small style="color: #7f8c8d;">PJP = Productive Journey Plan</small>
                            </div>

                            <div class="form-group">
                                <label for="jenis_rs">Jenis RS *</label>
                                <select id="jenis_rs" name="jenis_rs" required>
                                    <option value="Retail" <?php echo ($edit_data['jenis_rs'] ?? 'Retail') === 'Retail' ? 'selected' : ''; ?>>Retail</option>
                                    <option value="Pareto" <?php echo ($edit_data['jenis_rs'] ?? '') === 'Pareto' ? 'selected' : ''; ?>>Pareto</option>
                                    <option value="RS Eksekusi Voucher" <?php echo ($edit_data['jenis_rs'] ?? '') === 'RS Eksekusi Voucher' ? 'selected' : ''; ?>>RS Eksekusi Voucher</option>
                                    <option value="RS Eksekusi SA" <?php echo ($edit_data['jenis_rs'] ?? '') === 'RS Eksekusi SA' ? 'selected' : ''; ?>>RS Eksekusi SA</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <?php echo $action === 'add' ? 'Tambah Outlet' : 'Update Outlet'; ?>
                            </button>
                            <a href="outlet.php" class="btn-cancel">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/force-lexend.js"></script>
</body>
</html>
