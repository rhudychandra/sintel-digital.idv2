<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_penjualan_outlet') {
    $tanggal = $_POST['tanggal'];
    $sales_force_id = $_POST['sales_force_id'];
    $produk_ids = $_POST['produk_id'] ?? [];
    $outlet_ids = $_POST['outlet_id'] ?? [];
    $quantities = $_POST['qty'] ?? [];
    $keterangans = $_POST['keterangan'] ?? [];
    
    // Get cabang_id based on user role
    if (in_array($user['role'], ['admin', 'staff', 'supervisor'])) {
        $cabang_id = $user['cabang_id'];
    } else {
        $cabang_id = $_POST['cabang_id'] ?? null;
    }
    
    if (empty($produk_ids)) {
        $error = "Tidak ada data untuk disimpan!";
    } else {
        $conn->begin_transaction();
        try {
            $success_count = 0;
            
            foreach ($produk_ids as $index => $produk_id) {
                if (empty($produk_id) || empty($outlet_ids[$index]) || empty($quantities[$index])) continue;
                
                $qty = intval($quantities[$index]);
                $outlet_id = intval($outlet_ids[$index]);
                $keterangan = $keterangans[$index] ?? '';
                
                // Get product price
                $stmt = $conn->prepare("SELECT harga FROM produk WHERE produk_id = ?");
                $stmt->bind_param("i", $produk_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $produk = $result->fetch_assoc();
                $nominal = $produk['harga'] * $qty;
                
                // Insert into penjualan_outlet
                $stmt = $conn->prepare("INSERT INTO penjualan_outlet (tanggal, sales_force_id, produk_id, outlet_id, qty, nominal, keterangan, cabang_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siiidssii", $tanggal, $sales_force_id, $produk_id, $outlet_id, $qty, $nominal, $keterangan, $cabang_id, $user['user_id']);
                
                if ($stmt->execute()) {
                    $success_count++;
                }
            }
            
            $conn->commit();
            $message = "Berhasil menyimpan $success_count penjualan outlet!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}

// Get branches for dropdown (admin/manager only)
$branches = [];
if (in_array($user['role'], ['administrator', 'manager'])) {
    $result = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status='active' ORDER BY nama_cabang");
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Get sales forces based on role
$sales_forces = [];
if (in_array($user['role'], ['admin', 'staff', 'supervisor'])) {
    // Filter by user's cabang
    $stmt = $conn->prepare("SELECT reseller_id, nama_reseller FROM reseller WHERE cabang_id = ? AND status='active' ORDER BY nama_reseller");
    $stmt->bind_param("i", $user['cabang_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sales_forces[] = $row;
    }
} else {
    // Admin/Manager: all sales forces
    $result = $conn->query("SELECT reseller_id, nama_reseller, cabang_id FROM reseller WHERE status='active' ORDER BY nama_reseller");
    while ($row = $result->fetch_assoc()) {
        $sales_forces[] = $row;
    }
}

// Get products
$products = [];
$result = $conn->query("SELECT produk_id, nama_produk, harga FROM produk ORDER BY nama_produk");
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Get outlets
$outlets = [];
$result = $conn->query("SELECT outlet_id, nama_outlet, nomor_rs, sales_force_id FROM outlet ORDER BY nama_outlet");
while ($row = $result->fetch_assoc()) {
    $outlets[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Penjualan Per Outlet - Inventory System</title>
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .table-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .table-section h3 {
            margin-bottom: 15px;
            color: #8B1538;
            font-size: 16px;
        }
        
        .input-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .input-table th {
            background: #8B1538;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 10px;
        }
        
        .input-table td {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .input-table input,
        .input-table select {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 10px;
        }
        
        .btn-add-row {
            background: #27ae60;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11px;
            margin-top: 10px;
        }
        
        .btn-delete-row {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 9px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #8B1538 0%, #C84B31 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-top: 20px;
        }
        
        .summary-table {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #ffc107;
            margin: 10px 0;
        }
        
        .btn-add-djp {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .btn-add-djp:hover {
            background: #2980b9;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #8B1538;
            font-size: 18px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .modal-close:hover {
            color: #e74c3c;
        }
        
        .search-box {
            margin-bottom: 15px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #8B1538;
        }
        
        .outlet-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .outlet-item {
            padding: 12px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .outlet-item:hover {
            background: #f8f9fa;
            border-color: #8B1538;
            transform: translateX(5px);
        }
        
        .outlet-item h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 13px;
        }
        
        .outlet-item p {
            margin: 0;
            font-size: 11px;
            color: #7f8c8d;
        }
        
        .outlet-item .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #3498db;
            color: white;
            border-radius: 3px;
            font-size: 9px;
            margin-top: 5px;
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <aside class="admin-sidebar" style="width: 340px;">
            <div class="sidebar-header">
                <div style="display: flex; flex-direction: column; gap: 15px; align-items: stretch;">
                    <!-- Logo and Title -->
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div style="flex-shrink: 0;">
                            <img src="../../assets/images/logo_icon.png" alt="Logo" style="width: 60px; height: 60px; border-radius: 10px; object-fit: contain; background: rgba(255,255,255,0.1); padding: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        </div>
                        <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: white; letter-spacing: 0.5px;">INVENTORY</h2>
                    </div>
                    
                    <!-- User Info Box -->
                    <div style="background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.15) 100%); padding: 12px 14px; border-radius: 10px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                        <p style="margin: 0 0 4px 0; font-weight: 600; font-size: 13px; color: white; line-height: 1.4; word-wrap: break-word; word-break: break-word;"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p style="margin: 0; font-size: 11px; color: rgba(255,255,255,0.85); font-weight: 400; text-transform: capitalize; line-height: 1.3;"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=dashboard" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <?php if (in_array($user['role'], ['administrator', 'manager', 'finance'])): ?>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=input_barang" class="nav-item">
                    <span class="nav-icon">📥</span>
                    <span>Input Barang</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock_masuk.php" class="nav-item">
                    <span class="nav-icon">📥</span>
                    <span>Stock Masuk</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock_keluar.php" class="nav-item">
                    <span class="nav-icon">📤</span>
                    <span>Stock Keluar</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=input_penjualan" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Input Penjualan</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/input_penjualan_outlet.php" class="nav-item active">
                    <span class="nav-icon">🏪</span>
                    <span>Input Penjualan Per Outlet</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Stock</span>
                </a>
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_laporan.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>Laporan Penjualan</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
                <a href="../../logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Input Penjualan Per Outlet</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content" style="padding: 20px;">
            
            <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <h3><i class="fas fa-filter"></i> Filter Data</h3>
                <div class="filter-row">
                    <div class="form-group">
                        <label for="filter_tanggal">Tanggal *</label>
                        <input type="date" id="filter_tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                    <div class="form-group">
                        <label for="filter_cabang">Cabang *</label>
                        <select id="filter_cabang" required>
                            <option value="">Pilih Cabang</option>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['cabang_id']; ?>"><?php echo htmlspecialchars($branch['nama_cabang']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" id="filter_cabang" value="<?php echo $user['cabang_id']; ?>">
                    <div class="form-group">
                        <label>Cabang</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['cabang_name'] ?? '-'); ?>" disabled>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="filter_sales_force">Sales Force *</label>
                        <select id="filter_sales_force" required>
                            <option value="">Pilih Sales Force</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="button" class="btn-primary" onclick="loadSalesSummary()">
                            <i class="fas fa-search"></i> Tampilkan Data
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Table Penjualan Sales Force -->
            <div class="table-section" id="sales-summary-section" style="display: none;">
                <h3><i class="fas fa-chart-line"></i> Penjualan Sales Force</h3>
                <div class="summary-table" id="sales-summary-content"></div>
            </div>
            
            <!-- Input Penjualan Per Outlet -->
            <form method="POST" id="form-penjualan-outlet">
                <input type="hidden" name="action" value="submit_penjualan_outlet">
                <input type="hidden" name="tanggal" id="input_tanggal">
                <input type="hidden" name="sales_force_id" id="input_sales_force_id">
                <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                <input type="hidden" name="cabang_id" id="input_cabang_id">
                <?php endif; ?>
                
                <div class="table-section">
                    <h3><i class="fas fa-store"></i> Input Penjualan Per Outlet</h3>
                    <table class="input-table" id="outlet-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Produk *</th>
                                <th style="width: 10%;">Qty *</th>
                                <th style="width: 15%;">Nominal</th>
                                <th style="width: 20%;">Outlet *</th>
                                <th style="width: 10%;">ID Outlet</th>
                                <th style="width: 15%;">Keterangan</th>
                                <th style="width: 5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="outlet-tbody">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" class="btn-add-row" onclick="addOutletRow()">
                            <i class="fas fa-plus"></i> Tambah Baris
                        </button>
                        <button type="button" class="btn-add-djp" onclick="openDJPModal()">
                            <i class="fas fa-search"></i> Add DJP Outlet (Cross Selling)
                        </button>
                    </div>
                </div>
                
                <div class="alert-warning" id="validation-message" style="display: none;"></div>
                
                <button type="submit" class="btn-submit" onclick="return validateSubmit()">
                    <i class="fas fa-save"></i> Submit Penjualan
                </button>
            </form>
            
            <!-- Report Penjualan Per Outlet -->
            <div class="table-section">
                <h3><i class="fas fa-file-alt"></i> Report Penjualan Per Outlet</h3>
                <div id="report-content">
                    <p style="color: #7f8c8d; font-style: italic;">Pilih tanggal dan sales force untuk melihat report</p>
                </div>
            </div>
            </div>
        </main>
    </div>
    
    <!-- Modal Search Outlet -->
    <div class="modal-overlay" id="djpModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-search"></i> Search DJP Outlet</h3>
                <button class="modal-close" onclick="closeDJPModal()">&times;</button>
            </div>
            <div class="search-box">
                <input type="text" id="outlet-search" placeholder="Cari nama outlet, nomor RS, atau kota..." onkeyup="searchOutlets()">
            </div>
            <div class="outlet-list" id="outlet-results">
                <div class="no-results">
                    <i class="fas fa-info-circle" style="font-size: 40px; color: #bdc3c7; margin-bottom: 10px;"></i>
                    <p>Ketik minimal 2 karakter untuk mulai search</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const productsData = <?php echo json_encode($products); ?>;
        const outletsData = <?php echo json_encode($outlets); ?>;
        const salesForcesData = <?php echo json_encode($sales_forces); ?>;
        let salesSummaryData = {};
        let salesProductsData = {}; // Produk dari penjualan sales force
        let rowCounter = 0;
        let searchTimeout = null;
        
        // Load sales force based on cabang
        <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
        document.getElementById('filter_cabang').addEventListener('change', function() {
            const cabangId = this.value;
            const salesSelect = document.getElementById('filter_sales_force');
            salesSelect.innerHTML = '<option value="">Pilih Sales Force</option>';
            
            if (cabangId) {
                const filtered = salesForcesData.filter(sf => sf.cabang_id == cabangId);
                filtered.forEach(sf => {
                    const option = document.createElement('option');
                    option.value = sf.reseller_id;
                    option.textContent = sf.nama_reseller;
                    salesSelect.appendChild(option);
                });
            }
        });
        <?php else: ?>
        // Load all sales forces for user's cabang
        salesForcesData.forEach(sf => {
            const option = document.createElement('option');
            option.value = sf.reseller_id;
            option.textContent = sf.nama_reseller;
            document.getElementById('filter_sales_force').appendChild(option);
        });
        <?php endif; ?>
        
        // Load sales summary from inventory penjualan
        async function loadSalesSummary() {
            const tanggal = document.getElementById('filter_tanggal').value;
            const salesForceId = document.getElementById('filter_sales_force').value;
            
            if (!tanggal || !salesForceId) {
                alert('Harap pilih tanggal dan sales force!');
                return;
            }
            
            // Set hidden inputs
            document.getElementById('input_tanggal').value = tanggal;
            document.getElementById('input_sales_force_id').value = salesForceId;
            <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
            document.getElementById('input_cabang_id').value = document.getElementById('filter_cabang').value;
            <?php endif; ?>
            
            // Fetch data via AJAX
            const formData = new FormData();
            formData.append('action', 'get_sales_summary');
            formData.append('tanggal', tanggal);
            formData.append('reseller_id', salesForceId);
            
            console.log('Sending request with:', {
                action: 'get_sales_summary',
                tanggal: tanggal,
                reseller_id: salesForceId
            });
            
            try {
                const response = await fetch('ajax_penjualan_outlet.php', {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Error: Response bukan JSON valid. Check console untuk detail.');
                    return;
                }
                
                console.log('Parsed data:', data);
                
                if (data.success) {
                    salesSummaryData = data.data;
                    
                    // Build products map with harga from sales data
                    salesProductsData = {};
                    data.data.forEach(item => {
                        if (!salesProductsData[item.produk_id]) {
                            salesProductsData[item.produk_id] = {
                                produk_id: item.produk_id,
                                nama_produk: item.nama_produk,
                                harga: item.harga || 0
                            };
                        }
                    });
                    
                    displaySalesSummary(data.data);
                    document.getElementById('sales-summary-section').style.display = 'block';
                    
                    // Clear existing rows
                    document.getElementById('outlet-tbody').innerHTML = '';
                    
                    // Add first row
                    addOutletRow();
                    
                    // Load report
                    loadReport();
                } else {
                    alert(data.message || 'Tidak ada data penjualan untuk sales force ini pada tanggal tersebut');
                    document.getElementById('sales-summary-section').style.display = 'none';
                    document.getElementById('outlet-tbody').innerHTML = '';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengambil data');
            }
        }
        
        function displaySalesSummary(data) {
            let html = '<table class="input-table"><thead><tr>';
            html += '<th>Tanggal</th><th>Sales Force</th><th>Kategori</th><th>Produk</th><th>Qty</th><th>Nominal</th>';
            html += '</tr></thead><tbody>';
            
            let totalQty = 0;
            let totalNominal = 0;
            
            data.forEach(item => {
                html += '<tr>';
                html += '<td>' + item.tanggal + '</td>';
                html += '<td>' + item.nama_reseller + '</td>';
                html += '<td>' + item.kategori + '</td>';
                html += '<td>' + item.nama_produk + '</td>';
                html += '<td>' + item.qty + '</td>';
                html += '<td>Rp ' + parseInt(item.nominal).toLocaleString('id-ID') + '</td>';
                html += '</tr>';
                
                totalQty += parseInt(item.qty);
                totalNominal += parseFloat(item.nominal);
            });
            
            html += '<tr style="background: #f8f9fa; font-weight: bold;">';
            html += '<td colspan="4">TOTAL</td>';
            html += '<td>' + totalQty + '</td>';
            html += '<td>Rp ' + totalNominal.toLocaleString('id-ID') + '</td>';
            html += '</tr>';
            html += '</tbody></table>';
            
            document.getElementById('sales-summary-content').innerHTML = html;
        }
        
        function addOutletRow(isDJP = false, outletData = null) {
            rowCounter++;
            const tbody = document.getElementById('outlet-tbody');
            const row = document.createElement('tr');
            row.id = 'row-' + rowCounter;
            if (isDJP) {
                row.setAttribute('data-djp', 'true');
            }
            
            let html = '<td>';
            html += '<select name="produk_id[]" class="produk-select" onchange="updateNominal(this)" required>';
            html += '<option value="">Pilih Produk</option>';
            
            // Show products from sales summary (dari hasil penjualan reseller)
            if (salesProductsData && Object.keys(salesProductsData).length > 0) {
                for (const [id, product] of Object.entries(salesProductsData)) {
                    html += '<option value="' + id + '" data-harga="' + product.harga + '">' + product.nama_produk + '</option>';
                }
            }
            
            html += '</select></td>';
            
            html += '<td><input type="number" name="qty[]" class="qty-input" min="1" required onchange="updateNominal(this)"></td>';
            html += '<td><input type="text" class="nominal-display" readonly></td>';
            
            html += '<td>';
            
            if (isDJP && outletData) {
                // For DJP outlet, use text input (read-only) with hidden input for outlet_id
                html += '<input type="text" value="' + outletData.nama_outlet + ' (' + outletData.nomor_rs + ')" readonly style="background: #e8f5e9;">';
                html += '<input type="hidden" name="outlet_id[]" value="' + outletData.outlet_id + '">';
            } else {
                // Regular dropdown for sales force outlets
                html += '<select name="outlet_id[]" class="outlet-select" onchange="updateOutletId(this)" required>';
                html += '<option value="">Pilih Outlet</option>';
                
                // Filter outlets by sales force
                const salesForceId = document.getElementById('filter_sales_force').value;
                const filteredOutlets = outletsData.filter(o => o.sales_force_id == salesForceId);
                
                filteredOutlets.forEach(outlet => {
                    html += '<option value="' + outlet.outlet_id + '">' + outlet.nama_outlet + ' (' + outlet.nomor_rs + ')</option>';
                });
                
                html += '</select>';
            }
            
            html += '</td>';
            
            if (isDJP && outletData) {
                html += '<td><input type="text" class="outlet-id-display" value="' + outletData.outlet_id + '" readonly></td>';
            } else {
                html += '<td><input type="text" class="outlet-id-display" readonly></td>';
            }
            
            html += '<td><input type="text" name="keterangan[]" placeholder="' + (isDJP ? 'DJP - Cross Selling' : 'Opsional') + '"></td>';
            html += '<td><button type="button" class="btn-delete-row" onclick="deleteRow(' + rowCounter + ')"><i class="fas fa-trash"></i></button></td>';
            
            row.innerHTML = html;
            tbody.appendChild(row);
            
            if (isDJP) {
                closeDJPModal();
            }
        }
        
        function deleteRow(id) {
            const row = document.getElementById('row-' + id);
            if (row) row.remove();
        }
        
        function updateNominal(element) {
            const row = element.closest('tr');
            const produkSelect = row.querySelector('.produk-select');
            const qtyInput = row.querySelector('.qty-input');
            const nominalDisplay = row.querySelector('.nominal-display');
            
            const produkId = produkSelect.value;
            const qty = parseInt(qtyInput.value) || 0;
            
            if (produkId && qty > 0) {
                // Get harga from sales products data (harga aktual dari penjualan)
                let harga = 0;
                if (salesProductsData[produkId]) {
                    harga = parseFloat(salesProductsData[produkId].harga);
                } else {
                    // Fallback to productsData
                    const produk = productsData.find(p => p.produk_id == produkId);
                    if (produk) {
                        harga = parseFloat(produk.harga);
                    }
                }
                
                if (harga > 0) {
                    const nominal = harga * qty;
                    nominalDisplay.value = 'Rp ' + nominal.toLocaleString('id-ID');
                }
            }
        }
        
        function updateOutletId(element) {
            const row = element.closest('tr');
            const outletIdDisplay = row.querySelector('.outlet-id-display');
            outletIdDisplay.value = element.value;
        }
        
        function validateSubmit() {
            const rows = document.querySelectorAll('#outlet-tbody tr');
            
            if (rows.length === 0) {
                alert('Harap tambahkan minimal 1 data outlet!');
                return false;
            }
            
            // Calculate qty per product from outlet table
            const outletQty = {};
            rows.forEach(row => {
                const produkId = row.querySelector('.produk-select').value;
                const qty = parseInt(row.querySelector('.qty-input').value) || 0;
                
                if (produkId && qty > 0) {
                    outletQty[produkId] = (outletQty[produkId] || 0) + qty;
                }
            });
            
            // Calculate qty per product from sales summary
            const salesQty = {};
            if (salesSummaryData) {
                salesSummaryData.forEach(item => {
                    salesQty[item.produk_id] = (salesQty[item.produk_id] || 0) + parseInt(item.qty);
                });
            }
            
            // Validate
            let errors = [];
            for (const [produkId, qty] of Object.entries(salesQty)) {
                const outletTotal = outletQty[produkId] || 0;
                if (outletTotal !== qty) {
                    const produk = productsData.find(p => p.produk_id == produkId);
                    const produkName = produk ? produk.nama_produk : 'ID ' + produkId;
                    errors.push(produkName + ': Sales = ' + qty + ', Outlet = ' + outletTotal);
                }
            }
            
            if (errors.length > 0) {
                const msg = 'Total qty per produk tidak cocok!\n\n' + errors.join('\n');
                alert(msg);
                document.getElementById('validation-message').innerHTML = '<strong>Validasi Gagal:</strong><br>' + errors.join('<br>');
                document.getElementById('validation-message').style.display = 'block';
                return false;
            }
            
            document.getElementById('validation-message').style.display = 'none';
            return confirm('Apakah data sudah benar?');
        }
        
        async function loadReport() {
            const tanggal = document.getElementById('filter_tanggal').value;
            const salesForceId = document.getElementById('filter_sales_force').value;
            
            if (!tanggal || !salesForceId) return;
            
            const formData = new FormData();
            formData.append('action', 'get_report');
            formData.append('tanggal', tanggal);
            formData.append('sales_force_id', salesForceId);
            
            try {
                const response = await fetch('ajax_penjualan_outlet.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    let html = '<table class="input-table"><thead><tr>';
                    html += '<th>Tanggal</th><th>Sales Force</th><th>Produk</th><th>Outlet</th><th>Qty</th><th>Nominal</th><th>Keterangan</th>';
                    html += '</tr></thead><tbody>';
                    
                    let totalQty = 0;
                    let totalNominal = 0;
                    
                    data.data.forEach(item => {
                        html += '<tr>';
                        html += '<td>' + item.tanggal + '</td>';
                        html += '<td>' + item.nama_reseller + '</td>';
                        html += '<td>' + item.nama_produk + '</td>';
                        html += '<td>' + item.nama_outlet + '</td>';
                        html += '<td>' + item.qty + '</td>';
                        html += '<td>Rp ' + parseFloat(item.nominal).toLocaleString('id-ID') + '</td>';
                        html += '<td>' + (item.keterangan || '-') + '</td>';
                        html += '</tr>';
                        
                        totalQty += parseInt(item.qty);
                        totalNominal += parseFloat(item.nominal);
                    });
                    
                    html += '<tr style="background: #f8f9fa; font-weight: bold;">';
                    html += '<td colspan="4">TOTAL</td>';
                    html += '<td>' + totalQty + '</td>';
                    html += '<td>Rp ' + totalNominal.toLocaleString('id-ID') + '</td>';
                    html += '<td></td>';
                    html += '</tr>';
                    html += '</tbody></table>';
                    
                    document.getElementById('report-content').innerHTML = html;
                } else {
                    document.getElementById('report-content').innerHTML = '<p style="color: #7f8c8d; font-style: italic;">Belum ada data penjualan outlet untuk tanggal dan sales force ini</p>';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Modal Functions
        function openDJPModal() {
            const salesForceId = document.getElementById('filter_sales_force').value;
            if (!salesForceId) {
                alert('Harap pilih Sales Force terlebih dahulu!');
                return;
            }
            
            if (Object.keys(salesProductsData).length === 0) {
                alert('Harap klik "Tampilkan Data" terlebih dahulu untuk load data penjualan!');
                return;
            }
            
            document.getElementById('djpModal').style.display = 'flex';
            document.getElementById('outlet-search').value = '';
            document.getElementById('outlet-results').innerHTML = '<div class="no-results"><i class="fas fa-info-circle" style="font-size: 40px; color: #bdc3c7; margin-bottom: 10px;"></i><p>Ketik minimal 2 karakter untuk mulai search</p></div>';
        }
        
        function closeDJPModal() {
            document.getElementById('djpModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('djpModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDJPModal();
            }
        });
        
        function searchOutlets() {
            const searchValue = document.getElementById('outlet-search').value.trim();
            
            if (searchValue.length < 2) {
                document.getElementById('outlet-results').innerHTML = '<div class="no-results"><i class="fas fa-info-circle" style="font-size: 40px; color: #bdc3c7; margin-bottom: 10px;"></i><p>Ketik minimal 2 karakter untuk mulai search</p></div>';
                return;
            }
            
            // Debounce search
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(searchValue);
            }, 300);
        }
        
        async function performSearch(searchValue) {
            const resultsDiv = document.getElementById('outlet-results');
            resultsDiv.innerHTML = '<div class="no-results"><i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #3498db; margin-bottom: 10px;"></i><p>Searching...</p></div>';
            
            const formData = new FormData();
            formData.append('action', 'search_outlets');
            formData.append('search', searchValue);
            
            try {
                const response = await fetch('ajax_penjualan_outlet.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(outlet => {
                        html += '<div class="outlet-item" onclick="selectDJPOutlet(' + outlet.outlet_id + ', \'' + outlet.nama_outlet.replace(/'/g, "\\'") + '\', \'' + outlet.nomor_rs + '\')">';
                        html += '<h4>' + outlet.nama_outlet + '</h4>';
                        html += '<p><i class="fas fa-map-marker-alt"></i> ' + outlet.city + ' | <i class="fas fa-barcode"></i> ' + outlet.nomor_rs + '</p>';
                        html += '<p><i class="fas fa-store"></i> ' + outlet.type_outlet + '</p>';
                        if (outlet.sales_force_name) {
                            html += '<span class="badge">Sales Force: ' + outlet.sales_force_name + '</span>';
                        } else {
                            html += '<span class="badge" style="background: #e74c3c;">No Sales Force</span>';
                        }
                        html += '</div>';
                    });
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = '<div class="no-results"><i class="fas fa-search" style="font-size: 40px; color: #bdc3c7; margin-bottom: 10px;"></i><p>Tidak ada outlet ditemukan dengan keyword "' + searchValue + '"</p></div>';
                }
            } catch (error) {
                console.error('Error:', error);
                resultsDiv.innerHTML = '<div class="no-results"><i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #e74c3c; margin-bottom: 10px;"></i><p>Terjadi kesalahan saat search</p></div>';
            }
        }
        
        function selectDJPOutlet(outletId, outletName, nomorRs) {
            const outletData = {
                outlet_id: outletId,
                nama_outlet: outletName,
                nomor_rs: nomorRs
            };
            addOutletRow(true, outletData);
        }
    </script>
    
    <script src="../../assets/js/force-lexend.js"></script>
</body>
</html>
