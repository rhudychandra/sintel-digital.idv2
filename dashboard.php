<?php
require_once 'config/config.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sinar Telkom Dashboard System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="assets/images/logo_icon.png" alt="Logo" style="width: 50px; height: 50px; border-radius: 10px; object-fit: contain; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h1>Sinar Telkom Dashboard System</h1>
        </div>
        <div class="header-buttons">
            <?php if ($user['role'] === 'administrator'): ?>
            <a href="admin/" class="admin-button">Administrator</a>
            <?php endif; ?>
            
            <div class="account-dropdown">
                <button class="account-button">
                    <span>👤</span>
                    <span>Akun Saya</span>
                    <span style="font-size: 10px;">▼</span>
                </button>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <span class="dropdown-icon">👤</span>
                        <span>Informasi Akun</span>
                    </a>
                    <a href="profile.php?action=change_password" class="dropdown-item">
                        <span class="dropdown-icon">🔐</span>
                        <span>Ubah Password</span>
                    </a>
                </div>
            </div>
            
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </header>
    
    <main class="dashboard-main">
        <div class="hexagon-container">
            <a href="modules/performance/performance-cluster.php" class="hexagon-menu">
                <div class="hexagon">
                    <div class="hexagon-inner">
                        <div class="hexagon-content">
                            <span>Performance Cluster</span>
                        </div>
                    </div>
                </div>
            </a>
            
            <a href="modules/inventory/inventory.php" class="hexagon-menu">
                <div class="hexagon">
                    <div class="hexagon-inner">
                        <div class="hexagon-content">
                            <span>Inventory</span>
                        </div>
                    </div>
                </div>
            </a>
            
            <a href="info.php" class="hexagon-menu">
                <div class="hexagon">
                    <div class="hexagon-inner">
                        <div class="hexagon-content">
                            <span>Sinar Telekom Info</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </main>
</body>
</html>
