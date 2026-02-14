<?php
require_once __DIR__ . '/../../config/config.php';
$user = getCurrentUser();
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reports</title>
	<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/admin-styles.css">
	<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/reports.css">
</head>
<body class="admin-page">
	<div class="admin-container">
		<aside class="admin-sidebar reports-sidebar">
			<div class="sidebar-header">
				<h2>Reports</h2>
				<div class="sidebar-user-card">
					<img src="<?php echo BASE_PATH; ?>/assets/images/logo_icon.png" alt="User" class="user-avatar" />
					<div class="user-info">
						<div class="user-name"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></div>
						<div class="user-role"><?php echo htmlspecialchars($user['role'] ?? ''); ?></div>
					</div>
				</div>
				<small class="sidebar-subtitle">Report & Piutang</small>
			</div>
			<nav class="sidebar-nav">
				<a href="report_piutang_sa.php" class="nav-item"><span class="nav-icon">🧾</span><span>Report Piutang SA</span></a>
				<a href="report_piutang_voucher.php" class="nav-item"><span class="nav-icon">🎟️</span><span>Report Piutang Voucher</span></a>
				<a href="report_stock_piutang_tap.php" class="nav-item"><span class="nav-icon">🏢</span><span>Stock Piutang TAP</span></a>
			</nav>
			<div class="sidebar-footer">
				<a href="<?php echo BASE_PATH; ?>/dashboard" class="btn-back">← Kembali ke Dashboard</a>
				<a href="<?php echo BASE_PATH; ?>/logout" class="btn-logout">Logout</a>
			</div>
		</aside>

		<main style="flex:1;margin-left:340px;padding:24px;">
			<h2>Reports</h2>
			<p>Pilih laporan di sidebar.</p>
		</main>
	</div>
</body>
</html>