<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Cluster - Sinar Telkom Dashboard System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body class="submenu-page">
    <header class="dashboard-header">
        <h1>Performance Cluster</h1>
        <div class="header-buttons">
            <span style="margin-right: 15px; color: #667eea; font-weight: 500;">
                <?php echo htmlspecialchars($user['full_name']); ?>
            </span>
            <a href="../../dashboard.php" class="back-button">← Kembali</a>
            <a href="../../logout.php" class="logout-button">Logout</a>
        </div>
    </header>
    
    <main class="submenu-main">
        <div class="rounded-menu-container">
            <a href="fundamental-cluster.php" class="rounded-menu-item">
                <div class="rounded-box">
                    <h2>Fundamental Cluster</h2>
                    <p>Analisis fundamental cluster</p>
                </div>
            </a>
            
            <a href="kpi-sales-force.php" class="rounded-menu-item">
                <div class="rounded-box">
                    <h2>KPI Sales Force</h2>
                    <p>Monitor KPI sales force</p>
                </div>
            </a>
            
            <a href="kpi-direct-sales.php" class="rounded-menu-item">
                <div class="rounded-box">
                    <h2>KPI Direct Sales</h2>
                    <p>Monitor KPI direct sales</p>
                </div>
            </a>
        </div>
    </main>
</body>
</html>
