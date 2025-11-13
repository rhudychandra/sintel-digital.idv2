<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

// Check if user is administrator
if ($user['role'] !== 'administrator') {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getDBConnection();
$message = '';
$action = $_GET['action'] ?? 'list';
$kategori_id = $_GET['id'] ?? null;

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            try {
                $stmt = $conn->prepare("INSERT INTO kategori_produk (nama_kategori, deskripsi, icon) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $_POST['nama_kategori'], $_POST['deskripsi'], $_POST['icon']);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Kategori berhasil ditambahkan!";
                    $stmt->close();
                    header('Location: kategori.php');
                    exit();
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
            }
            
        } elseif ($_POST['action'] === 'edit') {
            try {
                $stmt = $conn->prepare("UPDATE kategori_produk SET nama_kategori=?, deskripsi=?, icon=?, status=? WHERE kategori_id=?");
                $stmt->bind_param("ssssi", $_POST['nama_kategori'], $_POST['deskripsi'], $_POST['icon'], $_POST['status'], $_POST['kategori_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Kategori berhasil diupdate!";
                    $stmt->close();
                    header('Location: kategori.php');
                    exit();
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
            }
            
        } elseif ($_POST['action'] === 'delete') {
            // Check if category is used by products
            $check = $conn->prepare("SELECT COUNT(*) as count FROM produk WHERE kategori COLLATE utf8mb4_general_ci = (SELECT nama_kategori COLLATE utf8mb4_general_ci FROM kategori_produk WHERE kategori_id = ?)");
            $check->bind_param("i", $_POST['kategori_id']);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                $_SESSION['message'] = "Error: Kategori tidak bisa dihapus karena masih digunakan oleh " . $result['count'] . " produk!";
            } else {
                $stmt = $conn->prepare("DELETE FROM kategori_produk WHERE kategori_id=?");
                $stmt->bind_param("i", $_POST['kategori_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Kategori berhasil dihapus!";
                } else {
                    $_SESSION['message'] = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
            header('Location: kategori.php');
            exit();
        }
    }
}

// Get category data for edit
$edit_data = null;
if ($action === 'edit' && $kategori_id) {
    $stmt = $conn->prepare("SELECT * FROM kategori_produk WHERE kategori_id=?");
    $stmt->bind_param("i", $kategori_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all categories with product count
$categories = [];
if ($action === 'list') {
    $result = $conn->query("
        SELECT k.*, 
               COUNT(p.produk_id) as jumlah_produk
        FROM kategori_produk k
        LEFT JOIN produk p ON k.nama_kategori COLLATE utf8mb4_general_ci = p.kategori COLLATE utf8mb4_general_ci
        GROUP BY k.kategori_id
        ORDER BY k.nama_kategori
    ");
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Administrator Panel</title>
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
                <a href="kategori.php" class="nav-item active">
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
                <a href="../dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>🏷️ Kelola Kategori Produk</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                <div class="alert" style="background: <?php echo strpos($message, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'list'): ?>
                <!-- Category List -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar Kategori</h2>
                        <a href="?action=add" class="btn-add">
                            <span>➕</span>
                            <span>Tambah Kategori</span>
                        </a>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Icon</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Jumlah Produk</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['kategori_id']; ?></td>
                                <td style="font-size: 24px;"><?php echo $category['icon']; ?></td>
                                <td><strong><?php echo htmlspecialchars($category['nama_kategori']); ?></strong></td>
                                <td><?php echo htmlspecialchars($category['deskripsi'] ?? '-'); ?></td>
                                <td>
                                    <span style="background: #e3f2fd; color: #1976d2; padding: 4px 12px; border-radius: 12px; font-weight: 600;">
                                        <?php echo $category['jumlah_produk']; ?> produk
                                    </span>
                                </td>
                                <td>
                                    <?php if ($category['status'] === 'active'): ?>
                                        <span style="background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">Active</span>
                                    <?php else: ?>
                                        <span style="background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $category['kategori_id']; ?>" class="btn-edit">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus kategori ini?<?php echo $category['jumlah_produk'] > 0 ? '\n\nPeringatan: Kategori ini digunakan oleh ' . $category['jumlah_produk'] . ' produk!' : ''; ?>');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="kategori_id" value="<?php echo $category['kategori_id']; ?>">
                                            <button type="submit" class="btn-delete" style="border: none; cursor: pointer;" <?php echo $category['jumlah_produk'] > 0 ? 'disabled title="Tidak bisa dihapus, masih digunakan"' : ''; ?>>Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                    Belum ada data kategori
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Form -->
                <div class="form-container">
                    <h2><?php echo $action === 'add' ? 'Tambah Kategori Baru' : 'Edit Kategori'; ?></h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="kategori_id" value="<?php echo $edit_data['kategori_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="nama_kategori">Nama Kategori *</label>
                            <input type="text" id="nama_kategori" name="nama_kategori" required 
                                   value="<?php echo htmlspecialchars($edit_data['nama_kategori'] ?? ''); ?>"
                                   placeholder="Contoh: Accessories, Gadgets, dll">
                            <small style="color: #7f8c8d; font-size: 12px;">Nama kategori harus unik</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="icon">Icon Emoji</label>
                            <input type="text" id="icon" name="icon" 
                                   value="<?php echo htmlspecialchars($edit_data['icon'] ?? '📦'); ?>"
                                   placeholder="📦" maxlength="10">
                            <small style="color: #7f8c8d; font-size: 12px;">Gunakan emoji untuk icon kategori (opsional)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="deskripsi">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Deskripsi kategori..."><?php echo htmlspecialchars($edit_data['deskripsi'] ?? ''); ?></textarea>
                        </div>
                        
                        <?php if ($action === 'edit'): ?>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo (isset($edit_data['status']) && $edit_data['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($edit_data['status']) && $edit_data['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <small style="color: #7f8c8d; font-size: 12px;">Kategori inactive tidak akan muncul di form produk</small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <?php echo $action === 'add' ? 'Tambah Kategori' : 'Update Kategori'; ?>
                            </button>
                            <a href="kategori.php" class="btn-cancel">Batal</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
