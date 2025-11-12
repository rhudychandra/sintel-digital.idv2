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
$reseller_id = $_GET['id'] ?? null;

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO reseller (kode_reseller, nama_reseller, nama_perusahaan, kategori, alamat, kota, provinsi, telepon, email, contact_person, cabang_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssss", $_POST['kode_reseller'], $_POST['nama_reseller'], $_POST['nama_perusahaan'], $_POST['kategori'], $_POST['alamat'], $_POST['kota'], $_POST['provinsi'], $_POST['telepon'], $_POST['email'], $_POST['contact_person'], $_POST['cabang_id'], $_POST['status']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Reseller berhasil ditambahkan!";
                $stmt->close();
                header('Location: reseller.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $conn->prepare("UPDATE reseller SET kode_reseller=?, nama_reseller=?, nama_perusahaan=?, kategori=?, alamat=?, kota=?, provinsi=?, telepon=?, email=?, contact_person=?, cabang_id=?, status=? WHERE reseller_id=?");
            $stmt->bind_param("ssssssssssssi", $_POST['kode_reseller'], $_POST['nama_reseller'], $_POST['nama_perusahaan'], $_POST['kategori'], $_POST['alamat'], $_POST['kota'], $_POST['provinsi'], $_POST['telepon'], $_POST['email'], $_POST['contact_person'], $_POST['cabang_id'], $_POST['status'], $_POST['reseller_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Reseller berhasil diupdate!";
                $stmt->close();
                header('Location: reseller.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM reseller WHERE reseller_id=?");
            $stmt->bind_param("i", $_POST['reseller_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Reseller berhasil dihapus!";
            } else {
                $_SESSION['message'] = "Error: " . $stmt->error;
            }
            $stmt->close();
            header('Location: reseller.php');
            exit();
        }
    }
}

// Get reseller data for edit
$edit_data = null;
if ($action === 'edit' && $reseller_id) {
    $stmt = $conn->prepare("SELECT * FROM reseller WHERE reseller_id=?");
    $stmt->bind_param("i", $reseller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all resellers
$resellers = [];
if ($action === 'list') {
    $result = $conn->query("SELECT r.*, c.nama_cabang FROM reseller r LEFT JOIN cabang c ON r.cabang_id = c.cabang_id ORDER BY r.reseller_id DESC");
    while ($row = $result->fetch_assoc()) {
        $resellers[] = $row;
    }
}

// Get all branches for dropdown
$branches = [];
$result = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status='active' ORDER BY nama_cabang");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Reseller - Administrator Panel</title>
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
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Users</span>
                </a>
                <a href="reseller.php" class="nav-item active">
                    <span class="nav-icon">🤝</span>
                    <span>Reseller</span>
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
                <h1>Kelola Reseller</h1>
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
                <!-- Reseller List -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar Reseller</h2>
                        <div class="btn-group">
                            <button onclick="openUploadModal()" class="btn-upload-excel">
                                <span>📤</span>
                                <span>Upload Excel</span>
                            </button>
                            <a href="?action=add" class="btn-add">
                                <span>➕</span>
                                <span>Tambah Reseller</span>
                            </a>
                        </div>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Reseller</th>
                                <th>Perusahaan</th>
                                <th>Kategori</th>
                                <th>Kota</th>
                                <th>Contact Person</th>
                                <th>Cabang</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resellers as $reseller): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reseller['kode_reseller']); ?></td>
                                <td><?php echo htmlspecialchars($reseller['nama_reseller']); ?></td>
                                <td><?php echo htmlspecialchars($reseller['nama_perusahaan']); ?></td>
                                <td>
                                    <span style="background: #e3f2fd; color: #1976d2; padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 500;">
                                        <?php echo htmlspecialchars($reseller['kategori'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($reseller['kota']); ?></td>
                                <td><?php echo htmlspecialchars($reseller['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($reseller['nama_cabang'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $reseller['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo ucfirst($reseller['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $reseller['reseller_id']; ?>" class="btn-edit">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus reseller ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="reseller_id" value="<?php echo $reseller['reseller_id']; ?>">
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
                    <h2><?php echo $action === 'add' ? 'Tambah Reseller Baru' : 'Edit Reseller'; ?></h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="reseller_id" value="<?php echo $edit_data['reseller_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="kode_reseller">Kode Reseller *</label>
                            <input type="text" id="kode_reseller" name="kode_reseller" required 
                                   value="<?php echo htmlspecialchars($edit_data['kode_reseller'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="nama_reseller">Nama Reseller *</label>
                            <input type="text" id="nama_reseller" name="nama_reseller" required 
                                   value="<?php echo htmlspecialchars($edit_data['nama_reseller'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="nama_perusahaan">Nama Perusahaan</label>
                            <input type="text" id="nama_perusahaan" name="nama_perusahaan" 
                                   value="<?php echo htmlspecialchars($edit_data['nama_perusahaan'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="kategori">Kategori Reseller *</label>
                            <select id="kategori" name="kategori" required>
                                <option value="">- Pilih Kategori -</option>
                                <option value="Sales Force" <?php echo (isset($edit_data['kategori']) && $edit_data['kategori'] === 'Sales Force') ? 'selected' : ''; ?>>Sales Force</option>
                                <option value="General Manager" <?php echo (isset($edit_data['kategori']) && $edit_data['kategori'] === 'General Manager') ? 'selected' : ''; ?>>General Manager</option>
                                <option value="Manager" <?php echo (isset($edit_data['kategori']) && $edit_data['kategori'] === 'Manager') ? 'selected' : ''; ?>>Manager</option>
                                <option value="Supervisor" <?php echo (isset($edit_data['kategori']) && $edit_data['kategori'] === 'Supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                <option value="Player/Pemain" <?php echo (isset($edit_data['kategori']) && $edit_data['kategori'] === 'Player/Pemain') ? 'selected' : ''; ?>>Player/Pemain</option>
                                <option value="Merchant" <?php echo (isset($edit_data['kategori']) && $edit_data['kategori'] === 'Merchant') ? 'selected' : ''; ?>>Merchant</option>
                            </select>
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
                            <label for="contact_person">Contact Person</label>
                            <input type="text" id="contact_person" name="contact_person" 
                                   value="<?php echo htmlspecialchars($edit_data['contact_person'] ?? ''); ?>">
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
                                <?php echo $action === 'add' ? 'Tambah Reseller' : 'Update Reseller'; ?>
                            </button>
                            <a href="reseller.php" class="btn-cancel">Batal</a>
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
                <h2>Upload Reseller dari Excel/CSV</h2>
                <button class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="upload-info">
                    <h4>📋 Petunjuk Upload:</h4>
                    <ul>
                        <li>File harus dalam format CSV (Comma Separated Values)</li>
                        <li>Kolom wajib: <strong>kode_reseller, nama_reseller, kategori</strong></li>
                        <li>Kolom opsional: <strong>nama_perusahaan, alamat, kota, provinsi, telepon, email, contact_person, status</strong></li>
                        <li>Kategori harus: <strong>Sales Force, General Manager, Manager, Supervisor, Player/Pemain, Merchant</strong></li>
                        <li>Status default: <strong>active</strong> (jika kosong)</li>
                        <li>Ukuran file maksimal 5MB</li>
                    </ul>
                </div>
                
                <a href="template_reseller.csv" download class="template-download">
                    <span>📥</span>
                    <span>Download Template CSV</span>
                </a>
                
                <form id="uploadForm" action="upload_reseller_excel.php" method="POST" enctype="multipart/form-data">
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
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('uploadForm').reset();
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
    </script>
</body>
</html>
