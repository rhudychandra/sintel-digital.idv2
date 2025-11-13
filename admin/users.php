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
$user_id = $_GET['id'] ?? null;

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $cabang_id = !empty($_POST['cabang_id']) ? $_POST['cabang_id'] : null;
            
            if ($cabang_id === null) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $_POST['username'], $password_hash, $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role'], $_POST['status']);
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, cabang_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssss", $_POST['username'], $password_hash, $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role'], $cabang_id, $_POST['status']);
            }
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "User berhasil ditambahkan!";
                $stmt->close();
                header('Location: users.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'edit') {
            $cabang_id = !empty($_POST['cabang_id']) ? $_POST['cabang_id'] : null;
            
            if (!empty($_POST['password'])) {
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                if ($cabang_id === null) {
                    $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=?, email=?, phone=?, role=?, cabang_id=NULL, status=? WHERE user_id=?");
                    $stmt->bind_param("sssssssi", $_POST['username'], $password_hash, $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role'], $_POST['status'], $_POST['user_id']);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=?, email=?, phone=?, role=?, cabang_id=?, status=? WHERE user_id=?");
                    $stmt->bind_param("ssssssssi", $_POST['username'], $password_hash, $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role'], $cabang_id, $_POST['status'], $_POST['user_id']);
                }
            } else {
                if ($cabang_id === null) {
                    $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, phone=?, role=?, cabang_id=NULL, status=? WHERE user_id=?");
                    $stmt->bind_param("ssssssi", $_POST['username'], $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role'], $_POST['status'], $_POST['user_id']);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, phone=?, role=?, cabang_id=?, status=? WHERE user_id=?");
                    $stmt->bind_param("sssssisi", $_POST['username'], $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role'], $cabang_id, $_POST['status'], $_POST['user_id']);
                }
            }
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "User berhasil diupdate!";
                $stmt->close();
                header('Location: users.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'reset_password') {
            // Reset password to default
            $default_password = 'password'; // Default password
            $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $stmt->bind_param("si", $password_hash, $_POST['user_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Password berhasil direset! Password baru: <strong>password</strong>";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error: " . $stmt->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
            header('Location: users.php');
            exit();
            
        } elseif ($_POST['action'] === 'delete') {
            // Check if user has related data
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory WHERE user_id = ?");
            $check_stmt->bind_param("i", $_POST['user_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_data = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($check_data['count'] > 0) {
                $_SESSION['message'] = "Error: Tidak dapat menghapus user ini karena masih memiliki " . $check_data['count'] . " transaksi inventory terkait. Silakan hapus atau update data inventory terlebih dahulu.";
                $_SESSION['message_type'] = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
                $stmt->bind_param("i", $_POST['user_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "User berhasil dihapus!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    // Check if it's a foreign key constraint error
                    if (strpos($stmt->error, 'foreign key constraint') !== false) {
                        $_SESSION['message'] = "Error: Tidak dapat menghapus user ini karena masih memiliki data terkait di sistem. Silakan hapus atau update data terkait terlebih dahulu.";
                    } else {
                        $_SESSION['message'] = "Error: " . $stmt->error;
                    }
                    $_SESSION['message_type'] = 'error';
                }
                $stmt->close();
            }
            header('Location: users.php');
            exit();
        }
    }
}

// Get user data for edit
$edit_data = null;
if ($action === 'edit' && $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all users
$users = [];
if ($action === 'list') {
    $result = $conn->query("SELECT u.*, c.nama_cabang FROM users u LEFT JOIN cabang c ON u.cabang_id = c.cabang_id ORDER BY u.user_id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// Get all branches for dropdown
$branches = [];
$result = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status='active' ORDER BY nama_cabang");
if ($result) {
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
    <title>Kelola Users - Administrator Panel</title>
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
                <a href="cabang.php" class="nav-item">
                    <span class="nav-icon">🏢</span>
                    <span>Cabang</span>
                </a>
                <a href="users.php" class="nav-item active">
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
                <h1>Kelola Users</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                <?php 
                $message_type = $_SESSION['message_type'] ?? 'success';
                unset($_SESSION['message_type']);
                $bg_color = $message_type === 'error' ? '#f8d7da' : '#d4edda';
                $text_color = $message_type === 'error' ? '#721c24' : '#155724';
                ?>
                <div class="alert" style="background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'list'): ?>
                <!-- Users List -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar Users</h2>
                        <a href="?action=add" class="btn-add">
                            <span>➕</span>
                            <span>Tambah User</span>
                        </a>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Cabang</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><span class="badge badge-active"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                <td><?php echo htmlspecialchars($u['nama_cabang'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $u['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $u['user_id']; ?>" class="btn-edit">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Reset password user ini ke default (password)?');">
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                            <button type="submit" class="btn-view" style="border: none; cursor: pointer;">Reset Password</button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
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
                    <h2><?php echo $action === 'add' ? 'Tambah User Baru' : 'Edit User'; ?></h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_data['user_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?php echo htmlspecialchars($edit_data['username'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password <?php echo $action === 'add' ? '*' : '(Kosongkan jika tidak diubah)'; ?></label>
                            <input type="password" id="password" name="password" <?php echo $action === 'add' ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Nama Lengkap *</label>
                            <input type="text" id="full_name" name="full_name" required 
                                   value="<?php echo htmlspecialchars($edit_data['full_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($edit_data['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telepon</label>
                            <input type="text" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($edit_data['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" required>
                                <option value="administrator" <?php echo (isset($edit_data['role']) && $edit_data['role'] === 'administrator') ? 'selected' : ''; ?>>Administrator</option>
                                <option value="admin" <?php echo (isset($edit_data['role']) && $edit_data['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="manager" <?php echo (isset($edit_data['role']) && $edit_data['role'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
                                <option value="supervisor" <?php echo (isset($edit_data['role']) && $edit_data['role'] === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                <option value="finance" <?php echo (isset($edit_data['role']) && $edit_data['role'] === 'finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="staff" <?php echo (isset($edit_data['role']) && $edit_data['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                                <option value="sales" <?php echo (isset($edit_data['role']) && $edit_data['role'] === 'sales') ? 'selected' : ''; ?>>Sales</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="cabang_id">Cabang</label>
                            <select id="cabang_id" name="cabang_id">
                                <option value="">- Pilih Cabang -</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['cabang_id']; ?>" 
                                        <?php echo (isset($edit_data['cabang_id']) && $edit_data['cabang_id'] == $branch['cabang_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['nama_cabang']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
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
                                <?php echo $action === 'add' ? 'Tambah User' : 'Update User'; ?>
                            </button>
                            <a href="users.php" class="btn-cancel">Batal</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
