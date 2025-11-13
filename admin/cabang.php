<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

if ($user['role'] !== 'administrator') {
    header('Location: ' . BASE_PATH . '/dashboard');
    exit();
}

$conn = getDBConnection();
// Detect optional columns to keep queries compatible with different schemas
$hasEmailColumn = false;
$checkEmail = $conn->query("SHOW COLUMNS FROM cabang LIKE 'email'");
if ($checkEmail) {
    $hasEmailColumn = $checkEmail->num_rows > 0;
}
$message = '';
$action = $_GET['action'] ?? 'list';
$cabang_id = $_GET['id'] ?? null;

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            if ($hasEmailColumn) {
                $stmt = $conn->prepare("INSERT INTO cabang (kode_cabang, nama_cabang, alamat, kota, provinsi, telepon, email, manager_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss",
                    $_POST['kode_cabang'],
                    $_POST['nama_cabang'],
                    $_POST['alamat'],
                    $_POST['kota'],
                    $_POST['provinsi'],
                    $_POST['telepon'],
                    $_POST['email'],
                    $_POST['manager_name'],
                    $_POST['status']
                );
            } else {
                $stmt = $conn->prepare("INSERT INTO cabang (kode_cabang, nama_cabang, alamat, kota, provinsi, telepon, manager_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssss",
                    $_POST['kode_cabang'],
                    $_POST['nama_cabang'],
                    $_POST['alamat'],
                    $_POST['kota'],
                    $_POST['provinsi'],
                    $_POST['telepon'],
                    $_POST['manager_name'],
                    $_POST['status']
                );
            }
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Cabang berhasil ditambahkan!";
                $stmt->close();
                header('Location: cabang.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'edit') {
            if ($hasEmailColumn) {
                $stmt = $conn->prepare("UPDATE cabang SET kode_cabang=?, nama_cabang=?, alamat=?, kota=?, provinsi=?, telepon=?, email=?, manager_name=?, status=? WHERE cabang_id=?");
                $stmt->bind_param("sssssssssi",
                    $_POST['kode_cabang'],
                    $_POST['nama_cabang'],
                    $_POST['alamat'],
                    $_POST['kota'],
                    $_POST['provinsi'],
                    $_POST['telepon'],
                    $_POST['email'],
                    $_POST['manager_name'],
                    $_POST['status'],
                    $_POST['cabang_id']
                );
            } else {
                $stmt = $conn->prepare("UPDATE cabang SET kode_cabang=?, nama_cabang=?, alamat=?, kota=?, provinsi=?, telepon=?, manager_name=?, status=? WHERE cabang_id=?");
                $stmt->bind_param("ssssssssi",
                    $_POST['kode_cabang'],
                    $_POST['nama_cabang'],
                    $_POST['alamat'],
                    $_POST['kota'],
                    $_POST['provinsi'],
                    $_POST['telepon'],
                    $_POST['manager_name'],
                    $_POST['status'],
                    $_POST['cabang_id']
                );
            }
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Cabang berhasil diupdate!";
                $stmt->close();
                header('Location: cabang.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM cabang WHERE cabang_id=?");
            $stmt->bind_param("i", $_POST['cabang_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Cabang berhasil dihapus!";
            } else {
                $_SESSION['message'] = "Error: " . $stmt->error;
            }
            $stmt->close();
            header('Location: cabang.php');
            exit();
        }
    }
}

// Get cabang data for edit
$edit_data = null;
if ($action === 'edit' && $cabang_id) {
    $stmt = $conn->prepare("SELECT * FROM cabang WHERE cabang_id=?");
    $stmt->bind_param("i", $cabang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all cabang
$branches = [];
if ($action === 'list') {
    $result = $conn->query("SELECT * FROM cabang ORDER BY cabang_id DESC");
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Cabang - Administrator Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin-styles.css">
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
                <a href="cabang.php" class="nav-item active">
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
                <a href="grafik.php" class="nav-item">
                    <span class="nav-icon">📉</span>
                    <span>Grafik</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="<?php echo BASE_PATH; ?>/dashboard" class="btn-back">← Kembali ke Dashboard</a>
                <a href="<?php echo BASE_PATH; ?>/logout" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Kelola Cabang</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'list'): ?>
                <!-- Cabang List -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar Cabang</h2>
                        <a href="?action=add" class="btn-add">
                            <span>➕</span>
                            <span>Tambah Cabang</span>
                        </a>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Cabang</th>
                                <th>Kota</th>
                                <th>Manager</th>
                                <th>Telepon</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($branch['kode_cabang']); ?></td>
                                <td><?php echo htmlspecialchars($branch['nama_cabang']); ?></td>
                                <td><?php echo htmlspecialchars($branch['kota']); ?></td>
                                <td><?php echo htmlspecialchars($branch['manager_name']); ?></td>
                                <td><?php echo htmlspecialchars($branch['telepon']); ?></td>
                                <td>
                                    <span class="badge <?php echo $branch['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo ucfirst($branch['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $branch['cabang_id']; ?>" class="btn-edit">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus cabang ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="cabang_id" value="<?php echo $branch['cabang_id']; ?>">
                                            <button type="submit" class="btn-delete" style="border: none; cursor: pointer;">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Form -->
                <div class="form-container">
                    <h2><?php echo $action === 'add' ? 'Tambah Cabang Baru' : 'Edit Cabang'; ?></h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="cabang_id" value="<?php echo $edit_data['cabang_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="kode_cabang">Kode Cabang *</label>
                            <input type="text" id="kode_cabang" name="kode_cabang" required 
                                   value="<?php echo htmlspecialchars($edit_data['kode_cabang'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="nama_cabang">Nama Cabang *</label>
                            <input type="text" id="nama_cabang" name="nama_cabang" required 
                                   value="<?php echo htmlspecialchars($edit_data['nama_cabang'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat"><?php echo htmlspecialchars($edit_data['alamat'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="kota">Kota</label>
                            <input type="text" id="kota" name="kota" 
                                   value="<?php echo htmlspecialchars($edit_data['kota'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="provinsi">Provinsi</label>
                            <input type="text" id="provinsi" name="provinsi" 
                                   value="<?php echo htmlspecialchars($edit_data['provinsi'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="telepon">Telepon</label>
                            <input type="text" id="telepon" name="telepon" 
                                   value="<?php echo htmlspecialchars($edit_data['telepon'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($edit_data['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="manager_name">Nama Manager</label>
                            <input type="text" id="manager_name" name="manager_name" 
                                   value="<?php echo htmlspecialchars($edit_data['manager_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo (isset($edit_data['status']) && $edit_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($edit_data['status']) && $edit_data['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <?php echo $action === 'add' ? 'Tambah Cabang' : 'Update Cabang'; ?>
                            </button>
                            <a href="cabang.php" class="btn-cancel">Batal</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
