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
$produk_id = $_GET['id'] ?? null;

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
                // Validate kategori
                if (empty($_POST['kategori'])) {
                    throw new Exception("Kategori harus dipilih.");
                }
                
                $kategori = trim($_POST['kategori']);
                
                // Generate kode_produk if not provided
                $kode_produk = !empty($_POST['kode_produk']) ? $_POST['kode_produk'] : 'PRD-' . time() . '-' . rand(100, 999);
                
                // Calculate profit margin: Harga Jual - (HPP Saldo + HPP Fisik)
                $hpp_saldo = isset($_POST['hpp_saldo']) ? floatval($_POST['hpp_saldo']) : 0;
                $hpp_fisik = isset($_POST['hpp_fisik']) ? floatval($_POST['hpp_fisik']) : 0;
                $harga = floatval($_POST['harga']);
                $total_hpp = $hpp_saldo + $hpp_fisik;
                $profit = $harga - $total_hpp;
                $profit_margin = ($total_hpp > 0) ? ($profit / $total_hpp) * 100 : 0;
                
                // Add new product (cabang_id = NULL for global products)
                $stmt = $conn->prepare("INSERT INTO produk (kode_produk, nama_produk, kategori, harga, hpp_saldo, hpp_fisik, profit_margin_saldo, profit_margin_fisik, deskripsi, cabang_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)");
                $stmt->bind_param("sssdddds", $kode_produk, $_POST['nama_produk'], $kategori, $_POST['harga'], $hpp_saldo, $hpp_fisik, $profit, $profit_margin, $_POST['deskripsi']);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Produk berhasil ditambahkan dengan kategori: " . htmlspecialchars($kategori);
                    $stmt->close();
                    header('Location: produk.php');
                    exit();
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                error_log("Produk Add Error: " . $e->getMessage());
            }
            
        } elseif ($_POST['action'] === 'edit') {
            try {
                // Validate kategori
                if (empty($_POST['kategori'])) {
                    throw new Exception("Kategori harus dipilih.");
                }
                
                $kategori = trim($_POST['kategori']);
                
                // Use existing kode_produk or generate new one
                $kode_produk = !empty($_POST['kode_produk']) ? $_POST['kode_produk'] : 'PRD-' . time() . '-' . rand(100, 999);
                
                // Calculate profit margin: Harga Jual - (HPP Saldo + HPP Fisik)
                $hpp_saldo = isset($_POST['hpp_saldo']) ? floatval($_POST['hpp_saldo']) : 0;
                $hpp_fisik = isset($_POST['hpp_fisik']) ? floatval($_POST['hpp_fisik']) : 0;
                $harga = floatval($_POST['harga']);
                $total_hpp = $hpp_saldo + $hpp_fisik;
                $profit = $harga - $total_hpp;
                $profit_margin = ($total_hpp > 0) ? ($profit / $total_hpp) * 100 : 0;
                
                // Update product (keep cabang_id = NULL for global products)
                $stmt = $conn->prepare("UPDATE produk SET kode_produk=?, nama_produk=?, kategori=?, hpp_saldo=?, hpp_fisik=?, harga=?, profit_margin_saldo=?, profit_margin_fisik=?, deskripsi=? WHERE produk_id=?");
                $stmt->bind_param("sssdddddsi", $kode_produk, $_POST['nama_produk'], $kategori, $hpp_saldo, $hpp_fisik, $harga, $profit, $profit_margin, $_POST['deskripsi'], $_POST['produk_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Produk berhasil diupdate dengan kategori: " . htmlspecialchars($kategori);
                    $stmt->close();
                    header('Location: produk.php');
                    exit();
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                error_log("Produk Edit Error: " . $e->getMessage());
            }
            
        } elseif ($_POST['action'] === 'delete') {
            // Check if product is used in transactions
            $check_sales = $conn->prepare("SELECT COUNT(*) as count FROM detail_penjualan WHERE produk_id = ?");
            $check_sales->bind_param("i", $_POST['produk_id']);
            $check_sales->execute();
            $sales_result = $check_sales->get_result()->fetch_assoc();
            
            // Check if product is used in inventory
            $check_inventory = $conn->prepare("SELECT COUNT(*) as count FROM inventory WHERE produk_id = ?");
            $check_inventory->bind_param("i", $_POST['produk_id']);
            $check_inventory->execute();
            $inventory_result = $check_inventory->get_result()->fetch_assoc();
            
            if ($sales_result['count'] > 0) {
                $_SESSION['message'] = "Error: Produk tidak bisa dihapus karena sudah digunakan dalam " . $sales_result['count'] . " transaksi penjualan!";
            } elseif ($inventory_result['count'] > 0) {
                $_SESSION['message'] = "Error: Produk tidak bisa dihapus karena memiliki " . $inventory_result['count'] . " record inventory!";
            } else {
                // Delete product
                $stmt = $conn->prepare("DELETE FROM produk WHERE produk_id=?");
                $stmt->bind_param("i", $_POST['produk_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Produk berhasil dihapus!";
                } else {
                    $_SESSION['message'] = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
            header('Location: produk.php');
            exit();
        }
    }
}

// Get product data for edit
$edit_data = null;
if ($action === 'edit' && $produk_id) {
    $stmt = $conn->prepare("SELECT * FROM produk WHERE produk_id=?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all products
$products = [];
if ($action === 'list') {
    $result = $conn->query("SELECT * FROM produk ORDER BY produk_id DESC");
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get all active categories for dropdown
$categories = [];
$result = $conn->query("SELECT kategori_id, nama_kategori, icon FROM kategori_produk WHERE status='active' ORDER BY nama_kategori");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Administrator Panel</title>
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
                <a href="produk.php" class="nav-item active">
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
                <h1>Kelola Produk</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'list'): ?>
                <!-- Product List -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar Produk</h2>
                        <div class="btn-group">
                            <button onclick="openUploadModal()" class="btn-upload-excel">
                                <span>📤</span>
                                <span>Upload Excel</span>
                            </button>
                            <a href="?action=add" class="btn-add">
                                <span>➕</span>
                                <span>Tambah Produk</span>
                            </a>
                        </div>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>HPP Saldo</th>
                                <th>HPP Fisik</th>
                                <th>Total HPP</th>
                                <th>Harga Jual</th>
                                <th>Profit</th>
                                <th>Margin %</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <?php
                                $hpp_saldo = $product['hpp_saldo'] ?? 0;
                                $hpp_fisik = $product['hpp_fisik'] ?? 0;
                                $total_hpp = $hpp_saldo + $hpp_fisik;
                                $harga = $product['harga'];
                                $profit = $harga - $total_hpp;
                                $margin = ($total_hpp > 0) ? ($profit / $total_hpp) * 100 : 0;
                                $color_margin = $margin > 20 ? '#27ae60' : ($margin > 10 ? '#f39c12' : '#e74c3c');
                            ?>
                            <tr>
                                <td><?php echo $product['produk_id']; ?></td>
                                <td><?php echo htmlspecialchars($product['nama_produk']); ?></td>
                                <td><?php echo htmlspecialchars($product['kategori']); ?></td>
                                <td style="color: #e74c3c; font-weight: 600;">Rp <?php echo number_format($hpp_saldo, 0, ',', '.'); ?></td>
                                <td style="color: #e67e22; font-weight: 600;">Rp <?php echo number_format($hpp_fisik, 0, ',', '.'); ?></td>
                                <td style="color: #8B1538; font-weight: 700; background: #f8f9fa;">Rp <?php echo number_format($total_hpp, 0, ',', '.'); ?></td>
                                <td style="color: #27ae60; font-weight: 700;">Rp <?php echo number_format($harga, 0, ',', '.'); ?></td>
                                <td style="color: #2980b9; font-weight: 600;">
                                    Rp <?php echo number_format($profit, 0, ',', '.'); ?>
                                </td>
                                <td>
                                    <span style="background: <?php echo $color_margin; ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                        <?php echo number_format($margin, 1); ?>%
                                    </span>
                                </td>
                                <td>
                                    <small style="color: #7f8c8d;">
                                        <?php 
                                        $desc = $product['deskripsi'] ?? '-';
                                        echo htmlspecialchars(strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc); 
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $product['produk_id']; ?>" class="btn-edit">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus produk ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="produk_id" value="<?php echo $product['produk_id']; ?>">
                                            <button type="submit" class="btn-delete" style="border: none; cursor: pointer;">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                    Belum ada data produk
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Form -->
                <div class="form-container">
                    <h2><?php echo $action === 'add' ? 'Tambah Produk Baru' : 'Edit Produk'; ?></h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="produk_id" value="<?php echo $edit_data['produk_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="nama_produk">Nama Produk *</label>
                            <input type="text" id="nama_produk" name="nama_produk" required 
                                   value="<?php echo htmlspecialchars($edit_data['nama_produk'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="kategori">Kategori *</label>
                            <select id="kategori" name="kategori" required>
                                <option value="">- Pilih Kategori -</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['nama_kategori']); ?>" 
                                        <?php echo (isset($edit_data['kategori']) && $edit_data['kategori'] == $cat['nama_kategori']) ? 'selected' : ''; ?>>
                                    <?php echo $cat['icon']; ?> <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: #7f8c8d; font-size: 12px;">
                                Tidak ada kategori yang sesuai? 
                                <a href="kategori.php?action=add" target="_blank" style="color: #667eea; font-weight: 600;">Tambah kategori baru</a>
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="hpp_saldo">HPP Saldo *</label>
                            <input type="number" id="hpp_saldo" name="hpp_saldo" required step="0.01" 
                                   value="<?php echo $edit_data['hpp_saldo'] ?? '0'; ?>"
                                   placeholder="HPP untuk produk saldo/virtual"
                                   onchange="calculateMargin()">
                            <small style="color: #7f8c8d;">Harga modal untuk produk saldo (LinkAja, Finpay, dll)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="hpp_fisik">HPP Fisik *</label>
                            <input type="number" id="hpp_fisik" name="hpp_fisik" required step="0.01" 
                                   value="<?php echo $edit_data['hpp_fisik'] ?? '0'; ?>"
                                   placeholder="HPP untuk produk fisik"
                                   onchange="calculateMargin()">
                            <small style="color: #7f8c8d;">Harga modal untuk produk fisik (Voucher, Perdana, dll)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="harga">Harga Jual *</label>
                            <input type="number" id="harga" name="harga" required step="0.01" 
                                   value="<?php echo $edit_data['harga'] ?? ''; ?>"
                                   placeholder="Harga jual ke customer"
                                   onchange="calculateMargin()">
                            <small style="color: #7f8c8d;">Harga jual ke customer</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin: 20px 0;">
                            <div class="form-group">
                                <label>Total HPP</label>
                                <div style="padding: 15px; background: #f8f9fa; border: 2px solid #8B1538; border-radius: 8px; text-align: center;">
                                    <span style="font-size: 22px; font-weight: 700; color: #8B1538;" id="totalHppValue">Rp 0</span>
                                    <br>
                                    <small style="color: #7f8c8d;">Saldo + Fisik</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Profit</label>
                                <div style="padding: 15px; background: #e8f8f5; border-radius: 8px; text-align: center;">
                                    <span style="font-size: 22px; font-weight: 700; color: #27ae60;" id="profitValue">Rp 0</span>
                                    <br>
                                    <small style="color: #7f8c8d;">Harga - Total HPP</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Profit Margin</label>
                                <div style="padding: 15px; background: #fff3cd; border-radius: 8px; text-align: center;">
                                    <span style="font-size: 24px; font-weight: 700; color: #e74c3c;" id="marginValue">0%</span>
                                    <br>
                                    <small style="color: #7f8c8d;">Profit / Total HPP</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="deskripsi">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi"><?php echo htmlspecialchars($edit_data['deskripsi'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <?php echo $action === 'add' ? 'Tambah Produk' : 'Update Produk'; ?>
                            </button>
                            <a href="produk.php" class="btn-cancel">Batal</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Upload Modal -->
    <div id="uploadModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Upload Produk dari Excel/CSV</h2>
                <button class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="upload-info">
                    <h4>📋 Petunjuk Upload:</h4>
                    <ul>
                        <li>File harus dalam format CSV (Comma Separated Values)</li>
                        <li>Kolom wajib: <strong>nama_produk, kategori, hpp_saldo, hpp_fisik, harga</strong></li>
                        <li>Kolom opsional: <strong>kode_produk, deskripsi</strong></li>
                        <li>HPP Saldo = Harga modal untuk produk saldo/virtual</li>
                        <li>HPP Fisik = Harga modal untuk produk fisik</li>
                        <li>Kategori harus sudah terdaftar di sistem</li>
                        <li>Kode produk akan di-generate otomatis jika kosong</li>
                        <li>Ukuran file maksimal 5MB</li>
                    </ul>
                </div>
                
                <a href="template_produk.csv" download class="template-download">
                    <span>📥</span>
                    <span>Download Template CSV</span>
                </a>
                
                <form id="uploadForm" action="upload_produk_excel.php" method="POST" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" id="excelFile" name="excel_file" accept=".csv,.xlsx,.xls" required>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-modal-cancel" onclick="closeUploadModal()">Batal</button>
                        <button type="submit" class="btn-upload" id="btnUpload">
                            <span>📤</span>
                            <span>Upload File</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Ensure DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Upload modal script loaded');
        });
        
        function openUploadModal() {
            console.log('Opening upload modal');
            const modal = document.getElementById('uploadModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                console.log('Modal opened successfully');
            } else {
                console.error('Modal element not found');
            }
        }
        
        function closeUploadModal() {
            console.log('Closing upload modal');
            const modal = document.getElementById('uploadModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
                document.getElementById('uploadForm').reset();
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('uploadModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUploadModal();
            }
        });
        
        // Handle file selection
        document.getElementById('excelFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = file.size / 1024 / 1024; // Convert to MB
                if (fileSize > 5) {
                    alert('Ukuran file terlalu besar! Maksimal 5MB');
                    this.value = '';
                    return;
                }
                
                const fileName = file.name;
                const fileExt = fileName.split('.').pop().toLowerCase();
                if (!['csv', 'xlsx', 'xls'].includes(fileExt)) {
                    alert('Format file tidak didukung! Gunakan CSV, XLS, atau XLSX');
                    this.value = '';
                    return;
                }
            }
        });
        
        // Handle form submission
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('excelFile');
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Silakan pilih file terlebih dahulu!');
                return;
            }
            
            const btnUpload = document.getElementById('btnUpload');
            btnUpload.disabled = true;
            btnUpload.innerHTML = '<span>⏳</span><span>Uploading...</span>';
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeUploadModal();
            }
        });
        
        // Calculate profit margins
        function calculateMargin() {
            const hpp_saldo = parseFloat(document.getElementById('hpp_saldo').value) || 0;
            const hpp_fisik = parseFloat(document.getElementById('hpp_fisik').value) || 0;
            const harga = parseFloat(document.getElementById('harga').value) || 0;
            
            // Calculate totals
            const totalHpp = hpp_saldo + hpp_fisik;
            const profit = harga - totalHpp;
            let margin = 0;
            if (totalHpp > 0) {
                margin = (profit / totalHpp) * 100;
            }
            
            // Update Total HPP
            const totalHppElement = document.getElementById('totalHppValue');
            if (totalHppElement) {
                totalHppElement.textContent = 'Rp ' + totalHpp.toLocaleString('id-ID');
                totalHppElement.style.color = '#8B1538';
                totalHppElement.style.fontWeight = '700';
            }
            
            // Update Profit
            const profitElement = document.getElementById('profitValue');
            if (profitElement) {
                profitElement.textContent = 'Rp ' + profit.toLocaleString('id-ID');
                profitElement.style.color = profit > 0 ? '#27ae60' : '#e74c3c';
                profitElement.style.fontWeight = '600';
            }
            
            // Update Margin
            const marginElement = document.getElementById('marginValue');
            if (marginElement) {
                marginElement.textContent = margin.toFixed(1) + '%';
                marginElement.style.fontWeight = '700';
                
                // Color based on margin
                if (margin > 20) {
                    marginElement.style.color = '#27ae60'; // Green
                } else if (margin > 10) {
                    marginElement.style.color = '#f39c12'; // Orange
                } else {
                    marginElement.style.color = '#e74c3c'; // Red
                }
            }
        }
        
        // Calculate margin on page load if editing
        window.addEventListener('load', function() {
            calculateMargin();
        });
    </script>
</body>
</html>
