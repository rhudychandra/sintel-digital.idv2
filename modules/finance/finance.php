<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$page_title = "Finance - Sinar Telkom Dashboard System";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
    <link rel="stylesheet" href="../../assets/css/laporan_styles.css">
</head>
<body class="submenu-page finance-page">
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="../../assets/images/logo_icon.png" alt="Logo" style="width: 50px; height: 50px; border-radius: 10px; object-fit: contain; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h1>Finance Management</h1>
        </div>
        <div class="header-buttons">
            <a href="<?php echo BASE_PATH; ?>/dashboard" class="back-button">← Kembali ke Dashboard</a>
            <a href="<?php echo BASE_PATH; ?>/logout" class="logout-button">Logout</a>
        </div>
    </header>

    <main class="submenu-main">
        <?php if (isset($_SESSION['error_message'])): ?>
        <!-- Error Popup Modal -->
        <div id="errorModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;">
            <div style="background:white;padding:30px;border-radius:15px;max-width:500px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;">
                <div style="width:80px;height:80px;background:#8B1538;border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-exclamation-circle" style="font-size:40px;color:white;"></i>
                </div>
                <h2 style="color:#8B1538;margin-bottom:15px;font-size:24px;">Akses Ditolak!</h2>
                <p style="color:#333;font-size:16px;line-height:1.6;margin-bottom:25px;">
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </p>
                <button onclick="document.getElementById('errorModal').style.display='none'" 
                        style="background:#8B1538;color:white;border:none;padding:12px 30px;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s;">
                    Mengerti
                </button>
            </div>
        </div>
        <script>
            // Auto close after 5 seconds
            setTimeout(function() {
                var modal = document.getElementById('errorModal');
                if (modal) modal.style.display = 'none';
            }, 5000);
        </script>
        <?php endif; ?>
        
        <div class="rounded-menu-container">
            <a href="setoran_harian_tap" class="rounded-menu-item">
                <div class="rounded-box">
                    <h2>💰 Setoran Harian TAP</h2>
                    <p>Monitoring dan laporan setoran harian dari TAP</p>
                </div>
            </a>

            <a href="laporan_setoran_global" class="rounded-menu-item">
                <div class="rounded-box">
                    <h2>🌍 Laporan Setoran Global</h2>
                    <p>Laporan setoran menyeluruh dari semua TAP secara global</p>
                </div>
            </a>

            <a href="budget_marketing" class="rounded-menu-item">
                <div class="rounded-box">
                    <h2>📈 Laporan KAS & Budget Marketing</h2>
                    <p>Pengelolaan KAS dan Budget Marketing</p>
                </div>
            </a>
        </div>
    </main>
</body>
</html>
