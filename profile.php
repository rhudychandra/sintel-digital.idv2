<?php
require_once 'config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$action = $_GET['action'] ?? 'view';

// Handle change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Semua field harus diisi!";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "Password baru dan konfirmasi password tidak cocok!";
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = "Password baru minimal 6 karakter!";
        $message_type = 'error';
    } else {
        // Get current password hash from database
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();
        
        // Verify current password
        if (password_verify($current_password, $user_data['password'])) {
            // Hash new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $stmt->bind_param("si", $password_hash, $user['user_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Password berhasil diubah! Silakan login dengan password baru.";
                $_SESSION['message_type'] = 'success';
                $stmt->close();
                header('Location: profile.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Password lama tidak sesuai!";
            $message_type = 'error';
        }
    }
}

// Get user full information
$stmt = $conn->prepare("
    SELECT 
        u.*,
        c.nama_cabang,
        c.alamat as cabang_alamat,
        c.manager_name
    FROM users u
    LEFT JOIN cabang c ON u.cabang_id = c.cabang_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();
$stmt->close();

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sinar Telkom Dashboard System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/admin-styles.css?v=<?php echo time(); ?>">
</head>
<body class="admin-page">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Profil Saya</h2>
                <p><?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="profile.php" class="nav-item <?php echo $action === 'view' ? 'active' : ''; ?>">
                    <span class="nav-icon">👤</span>
                    <span>Informasi Akun</span>
                </a>
                <a href="profile.php?action=change_password" class="nav-item <?php echo $action === 'change_password' ? 'active' : ''; ?>">
                    <span class="nav-icon">🔐</span>
                    <span>Ubah Password</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1><?php echo $action === 'change_password' ? 'Ubah Password' : 'Informasi Akun'; ?></h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                <?php 
                $bg_color = $message_type === 'error' ? '#f8d7da' : '#d4edda';
                $text_color = $message_type === 'error' ? '#721c24' : '#155724';
                ?>
                <div class="alert" style="background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'view'): ?>
                <!-- User Information -->
                <div class="form-container" style="max-width: 800px;">
                    <h2 style="margin-bottom: 30px; color: #2c3e50; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 32px;">👤</span>
                        Informasi Akun Lengkap
                    </h2>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                        <!-- Personal Information -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; border-left: 4px solid #8B1538;">
                            <h3 style="color: #8B1538; margin-bottom: 15px; font-size: 16px;">📋 Informasi Personal</h3>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Nama Lengkap</label>
                                <div style="color: #2c3e50; font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($user_info['full_name']); ?></div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Username</label>
                                <div style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($user_info['username']); ?></div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Email</label>
                                <div style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($user_info['email']); ?></div>
                            </div>
                            
                            <div>
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Nomor Telepon</label>
                                <div style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($user_info['phone'] ?? '-'); ?></div>
                            </div>
                        </div>
                        
                        <!-- Work Information -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; border-left: 4px solid #C84B31;">
                            <h3 style="color: #C84B31; margin-bottom: 15px; font-size: 16px;">💼 Informasi Pekerjaan</h3>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Role / Jabatan</label>
                                <div>
                                    <span style="background: linear-gradient(135deg, #8B1538 0%, #C84B31 100%); color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                        <?php echo strtoupper(htmlspecialchars($user_info['role'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Cabang</label>
                                <div style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($user_info['nama_cabang'] ?? '-'); ?></div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Manager Cabang</label>
                                <div style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($user_info['manager_name'] ?? '-'); ?></div>
                            </div>
                            
                            <div>
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Status</label>
                                <div>
                                    <span style="background: <?php echo $user_info['status'] === 'active' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $user_info['status'] === 'active' ? '#155724' : '#721c24'; ?>; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        <?php echo ucfirst($user_info['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    <div style="background: linear-gradient(135deg, #F5E6E8 0%, #FFF5F5 100%); padding: 20px; border-radius: 12px; margin-top: 25px; border-left: 4px solid #8B1538;">
                        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 16px;">📊 Informasi Tambahan</h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">User ID</label>
                                <div style="color: #2c3e50; font-weight: 600;">#<?php echo $user_info['user_id']; ?></div>
                            </div>
                            
                            <div>
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Terakhir Login</label>
                                <div style="color: #2c3e50; font-weight: 600;">
                                    <?php 
                                    if ($user_info['last_login']) {
                                        echo date('d F Y, H:i', strtotime($user_info['last_login']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($user_info['nama_cabang']): ?>
                    <!-- Branch Information -->
                    <div style="background: white; padding: 20px; border-radius: 12px; margin-top: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 16px;">🏢 Informasi Cabang</h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                            <div>
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Nama Cabang</label>
                                <div style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($user_info['nama_cabang']); ?></div>
                            </div>
                            
                            <?php if ($user_info['cabang_alamat']): ?>
                            <div>
                                <label style="display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 5px;">Alamat Cabang</label>
                                <div style="color: #2c3e50;"><?php echo htmlspecialchars($user_info['cabang_alamat']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 30px;">
                        <a href="dashboard.php" class="btn-cancel">← Kembali ke Dashboard</a>
                    </div>
                </div>
                
                <?php elseif ($action === 'change_password'): ?>
                <!-- Change Password Form -->
                <div class="form-container" style="max-width: 600px;">
                    <h2 style="margin-bottom: 10px;">🔐 Ubah Password</h2>
                    <p style="color: #7f8c8d; margin-bottom: 30px;">Ubah password akun Anda untuk keamanan yang lebih baik</p>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Password Lama *</label>
                            <input type="password" id="current_password" name="current_password" required placeholder="Masukkan password lama">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Password Baru *</label>
                            <input type="password" id="new_password" name="new_password" required placeholder="Masukkan password baru (min. 6 karakter)">
                            <small style="color: #7f8c8d; font-size: 12px;">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ulangi password baru">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">💾 Ubah Password</button>
                            <a href="profile.php" class="btn-cancel">Batal</a>
                        </div>
                    </form>
                    
                    <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 13px;">
                        <strong>💡 Tips Keamanan:</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li>Gunakan kombinasi huruf besar, kecil, angka, dan simbol</li>
                            <li>Jangan gunakan password yang mudah ditebak</li>
                            <li>Ubah password secara berkala</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
