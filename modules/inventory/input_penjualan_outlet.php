<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$error = '';

// ===== EXPORT CSV FEATURE =====
// Query & stream CSV based on optional filters: tanggal, sales_force_id
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Build query
    // Base select includes cabang (if any), sales force, product, outlet, IDs, qty, nominal, keterangan, author
    $sql = "SELECT 
                po.tanggal,
                COALESCE(c.nama_cabang, cr.nama_cabang) AS nama_cabang,
                r.nama_reseller,
                p.nama_produk,
                o.nama_outlet,
                o.id_digipos,
                o.nik_ktp,
                po.qty,
                po.nominal,
                po.keterangan,
                u.username AS author
            FROM penjualan_outlet po
            JOIN reseller r ON po.sales_force_id = r.reseller_id
            JOIN produk p ON po.produk_id = p.produk_id
            JOIN outlet o ON po.outlet_id = o.outlet_id
            LEFT JOIN cabang c ON po.cabang_id = c.cabang_id
            LEFT JOIN cabang cr ON r.cabang_id = cr.cabang_id
            LEFT JOIN users u ON po.created_by = u.user_id";

    $conditions = [];
    $params = [];
    $types = '';

    // Filter tanggal (support range)
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    if (!empty($start_date) && !empty($end_date)) {
        $conditions[] = 'po.tanggal BETWEEN ? AND ?';
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= 'ss';
    } elseif (!empty($start_date)) {
        $conditions[] = 'po.tanggal >= ?';
        $params[] = $start_date;
        $types .= 's';
    } elseif (!empty($_GET['tanggal'])) {
        $conditions[] = 'po.tanggal = ?';
        $params[] = $_GET['tanggal'];
        $types .= 's';
    } elseif (!empty($end_date)) {
        $conditions[] = 'po.tanggal <= ?';
        $params[] = $end_date;
        $types .= 's';
    }

    // Filter sales force
    if (!empty($_GET['sales_force_id'])) {
        $conditions[] = 'po.sales_force_id = ?';
        $params[] = (int)$_GET['sales_force_id'];
        $types .= 'i';
    }

    // Non administrator/manager restrict by user's cabang
    if (!in_array($user['role'], ['administrator', 'manager']) && !empty($user['cabang_id'])) {
        // Include records with explicit cabang_id or legacy NULL where reseller.cabang_id matches
        $conditions[] = '(po.cabang_id = ? OR (po.cabang_id IS NULL AND r.cabang_id = ?))';
        $params[] = (int)$user['cabang_id'];
        $params[] = (int)$user['cabang_id'];
        $types .= 'ii';
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY po.tanggal DESC, r.nama_reseller, p.nama_produk, o.nama_outlet';

    // Prepare & execute
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Gagal menyiapkan query export.';
        exit;
    }
    if ($params) {
        // Bind by reference for dynamic params
        $bindParams = array_merge([$types], $params);
        foreach ($bindParams as $k => $v) { $bindParams[$k] = $bindParams[$k]; }
        $refParams = [];
        foreach ($bindParams as $key => &$value) { $refParams[$key] = &$value; }
        call_user_func_array([$stmt, 'bind_param'], $refParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Output headers
    $filenameParts = ['penjualan_outlet'];
    if (!empty($_GET['tanggal'])) $filenameParts[] = str_replace('-', '', $_GET['tanggal']);
    if (!empty($_GET['sales_force_id'])) $filenameParts[] = 'sf' . (int)$_GET['sales_force_id'];
    $filename = implode('_', $filenameParts) . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['Tanggal', 'Cabang', 'Sales Force', 'Produk', 'Outlet', 'ID Digipos', 'NIK KTP', 'Qty', 'Nominal', 'Keterangan', 'Author']);

    $totalQty = 0;
    $totalNominal = 0.0;

    while ($row = $result->fetch_assoc()) {
        $csvRow = [
            $row['tanggal'],
            $row['nama_cabang'] ?? '-',
            $row['nama_reseller'] ?? '-',
            $row['nama_produk'] ?? '-',
            $row['nama_outlet'] ?? '-',
            $row['id_digipos'] ?? '-',
            $row['nik_ktp'] ?? '-',
            $row['qty'],
            $row['nominal'],
            $row['keterangan'] ?? '-',
            $row['author'] ?? '-'
        ];
        fputcsv($output, $csvRow);
        $totalQty += (int)$row['qty'];
        $totalNominal += (float)$row['nominal'];
    }

    // Summary line
    fputcsv($output, ['TOTAL', '', '', '', '', '', '', $totalQty, $totalNominal, '', '']);

    fclose($output);
    $stmt->close();
    $conn->close();
    exit; // Stop normal page rendering
}
// ===== END EXPORT CSV FEATURE =====

// ===== EXPORT EXCEL (XLS) FEATURE =====
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    // Build same query as CSV
    $sql = "SELECT 
                po.tanggal,
                COALESCE(c.nama_cabang, cr.nama_cabang) AS nama_cabang,
                r.nama_reseller,
                p.nama_produk,
                o.nama_outlet,
                o.id_digipos,
                o.nik_ktp,
                po.qty,
                po.nominal,
                po.keterangan,
                u.username AS author
            FROM penjualan_outlet po
            JOIN reseller r ON po.sales_force_id = r.reseller_id
            JOIN produk p ON po.produk_id = p.produk_id
            JOIN outlet o ON po.outlet_id = o.outlet_id
            LEFT JOIN cabang c ON po.cabang_id = c.cabang_id
            LEFT JOIN cabang cr ON r.cabang_id = cr.cabang_id
            LEFT JOIN users u ON po.created_by = u.user_id";

    $conditions = [];
    $params = [];
    $types = '';

    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    if (!empty($start_date) && !empty($end_date)) {
        $conditions[] = 'po.tanggal BETWEEN ? AND ?';
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= 'ss';
    } elseif (!empty($start_date)) {
        $conditions[] = 'po.tanggal >= ?';
        $params[] = $start_date;
        $types .= 's';
    } elseif (!empty($_GET['tanggal'])) {
        $conditions[] = 'po.tanggal = ?';
        $params[] = $_GET['tanggal'];
        $types .= 's';
    } elseif (!empty($end_date)) {
        $conditions[] = 'po.tanggal <= ?';
        $params[] = $end_date;
        $types .= 's';
    }
    if (!empty($_GET['sales_force_id'])) {
        $conditions[] = 'po.sales_force_id = ?';
        $params[] = (int)$_GET['sales_force_id'];
        $types .= 'i';
    }
    if (!in_array($user['role'], ['administrator', 'manager']) && !empty($user['cabang_id'])) {
        $conditions[] = '(po.cabang_id = ? OR (po.cabang_id IS NULL AND r.cabang_id = ?))';
        $params[] = (int)$user['cabang_id'];
        $params[] = (int)$user['cabang_id'];
        $types .= 'ii';
    }
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY po.tanggal DESC, r.nama_reseller, p.nama_produk, o.nama_outlet';

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Gagal menyiapkan query export.';
        exit;
    }
    if ($params) {
        $bindParams = array_merge([$types], $params);
        foreach ($bindParams as $k => $v) { $bindParams[$k] = $bindParams[$k]; }
        $refParams = [];
        foreach ($bindParams as $key => &$value) { $refParams[$key] = &$value; }
        call_user_func_array([$stmt, 'bind_param'], $refParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $filenameParts = ['penjualan_outlet'];
    if (!empty($_GET['tanggal'])) $filenameParts[] = str_replace('-', '', $_GET['tanggal']);
    if (!empty($_GET['sales_force_id'])) $filenameParts[] = 'sf' . (int)$_GET['sales_force_id'];
    $filename = implode('_', $filenameParts) . '_' . date('Ymd_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output as simple HTML table compatible with Excel
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<table border="1">';
    echo '<thead><tr>';
    $headers = ['Tanggal','Cabang','Sales Force','Produk','Outlet','ID Digipos','NIK KTP','Qty','Nominal','Keterangan','Author'];
    foreach ($headers as $h) echo '<th>'.htmlspecialchars($h).'</th>';
    echo '</tr></thead><tbody>';

    $totalQty = 0; $totalNominal = 0.0;
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['tanggal']).'</td>';
        echo '<td>'.htmlspecialchars($row['nama_cabang'] ?? '-').'</td>';
        echo '<td>'.htmlspecialchars($row['nama_reseller'] ?? '-').'</td>';
        echo '<td>'.htmlspecialchars($row['nama_produk'] ?? '-').'</td>';
        echo '<td>'.htmlspecialchars($row['nama_outlet'] ?? '-').'</td>';
        // Force text format for ID/NIK to avoid scientific notation in Excel
        $idDigipos = $row['id_digipos'] ?? '-';
        $nik = $row['nik_ktp'] ?? '-';
        echo '<td style="mso-number-format:\'@\';">'.htmlspecialchars($idDigipos).'</td>';
        echo '<td style="mso-number-format:\'@\';">'.htmlspecialchars($nik).'</td>';
        echo '<td>'.(int)$row['qty'].'</td>';
        echo '<td>'.(float)$row['nominal'].'</td>';
        echo '<td>'.htmlspecialchars($row['keterangan'] ?? '-').'</td>';
        echo '<td>'.htmlspecialchars($row['author'] ?? '-').'</td>';
        echo '</tr>';
        $totalQty += (int)$row['qty'];
        $totalNominal += (float)$row['nominal'];
    }
    echo '<tr>';
    echo '<td><strong>TOTAL</strong></td><td></td><td></td><td></td><td></td><td></td><td></td>';
    echo '<td><strong>'.$totalQty.'</strong></td>';
    echo '<td><strong>'.$totalNominal.'</strong></td>';
    echo '<td></td><td></td>';
    echo '</tr>';
    echo '</tbody></table>';

    $stmt->close();
    $conn->close();
    exit;
}
// ===== END EXPORT EXCEL (XLS) FEATURE =====

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

// Get cabang name for non-admin users
$user_cabang_name = '-';
if (!empty($user['cabang_id'])) {
    $stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
    $stmt->bind_param("i", $user['cabang_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_cabang_name = $row['nama_cabang'];
    }
}

// Get sales forces based on role
$sales_forces = [];
// For non-admin users, filter by cabang
if (in_array($user['role'], ['administrator', 'manager'])) {
    // Admin/Manager: all sales forces with cabang_id
    $result = $conn->query("SELECT reseller_id, nama_reseller, cabang_id FROM reseller WHERE status='active' ORDER BY nama_reseller");
    while ($row = $result->fetch_assoc()) {
        $sales_forces[] = $row;
    }
} else {
    // Staff/Supervisor/Finance/etc: Filter by user's cabang
    if (!empty($user['cabang_id'])) {
        $stmt = $conn->prepare("SELECT reseller_id, nama_reseller, cabang_id FROM reseller WHERE cabang_id = ? AND status='active' ORDER BY nama_reseller");
        $stmt->bind_param("i", $user['cabang_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sales_forces[] = $row;
        }
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
$result = $conn->query("SELECT outlet_id, nama_outlet, nomor_rs, id_digipos, sales_force_id FROM outlet ORDER BY nama_outlet");
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
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
            margin-top: 0;
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

        /* Icon/Button polish */
        .admin-main i.fa-solid {
            filter: drop-shadow(0 1px 0 rgba(0,0,0,0.25));
        }
        .btn-primary, .btn-add-row, .btn-add-djp, .btn-submit {
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            transition: transform .08s ease, box-shadow .2s ease;
        }
        .btn-primary:hover, .btn-add-row:hover, .btn-add-djp:hover, .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.18);
        }

        /* Generic Popup Modal */
        .popup-modal .modal-content {
            max-width: 520px;
        }
        /* Popup fade */
        #popupModal { transition: opacity .18s ease; opacity:0; }
        #popupModal.active { opacity:1; }
        .modal-header .modal-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 50%; margin-right: 10px;
        }
        .modal-header.warning .modal-icon { background:#fff3cd; color:#856404; }
        .modal-header.danger .modal-icon { background:#fde2e1; color:#c0392b; }
        .modal-header.success .modal-icon { background:#eafaf1; color:#27ae60; }
        .modal-header.info .modal-icon { background:#e8f4fd; color:#2980b9; }
        .modal-actions { margin-top: 16px; display:flex; gap:10px; justify-content:flex-end; }
        .btn { padding:8px 14px; border-radius:6px; border:0; cursor:pointer; font-size:12px; }
        .btn-outline { background:#fff; border:1px solid #ccd1d1; }
        .btn-danger { background:#e74c3c; color:#fff; }
        .btn-success { background:#27ae60; color:#fff; }
        .btn-info { background:#3498db; color:#fff; }
        /* Form actions alignment */
        .form-actions { display:flex; justify-content:flex-end; margin: 20px 0 50px; }
        @media (max-width:700px){ .form-actions { justify-content:center; } }
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
                <i class="fa-solid fa-check-circle"></i> <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <h3><i class="fa-solid fa-filter"></i> Filter Data</h3>
                <div class="filter-row">
                    <div class="form-group">
                        <label for="filter_tanggal">Tanggal *</label>
                        <input type="date" id="filter_tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="filter_tanggal_end">Tanggal Akhir (opsional)</label>
                        <input type="date" id="filter_tanggal_end" value="">
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
                        <input type="text" value="<?php echo htmlspecialchars($user_cabang_name); ?>" disabled style="background: #f8f9fa; font-weight: 600;">
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
                            <i class="fa-solid fa-search"></i> Tampilkan Data
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Table Penjualan Sales Force -->
            <div class="table-section" id="sales-summary-section" style="display: none;">
                <h3><i class="fa-solid fa-chart-line"></i> Penjualan Sales Force</h3>
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
                    <h3><i class="fa-solid fa-store"></i> Input Penjualan Per Outlet</h3>
                    <table class="input-table" id="outlet-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Produk *</th>
                                <th style="width: 10%;">Qty *</th>
                                <th style="width: 15%;">Nominal</th>
                                <th style="width: 20%;">Outlet *</th>
                                <th style="width: 10%;">ID Digipos</th>
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
                            <i class="fa-solid fa-plus"></i> Tambah Baris
                        </button>
                        <button type="button" class="btn-add-djp" onclick="openDJPModal()">
                            <i class="fa-solid fa-search"></i> Add DJP Outlet (Cross Selling)
                        </button>
                    </div>
                </div>
                
                <div class="alert-warning" id="validation-message" style="display: none;"></div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit" onclick="return validateSubmit()">
                        <i class="fa-solid fa-save"></i> Submit Penjualan
                    </button>
                </div>
            </form>
            
            <!-- Report Penjualan Per Outlet -->
            <div class="table-section" id="history-section" style="margin-top: 40px;">
                <h3><i class="fa-solid fa-file-alt"></i> History Input Penjualan Outlet<?php if (!in_array($user['role'], ['administrator', 'manager'])): ?> - <?php echo htmlspecialchars($user_cabang_name); ?><?php endif; ?></h3>
                
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin:0 0 12px 0;">
                    <button type="button" class="btn-primary" onclick="exportFilteredCSV()" style="display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-download"></i> Export Filter (CSV)
                    </button>
                    <button type="button" class="btn-primary" onclick="exportAllCSV()" style="display:flex; align-items:center; gap:6px; background:#34495e;">
                        <i class="fa-solid fa-file-export"></i> Export Semua (CSV)
                    </button>
                    <button type="button" class="btn-primary" onclick="exportFilteredXLSX()" style="display:flex; align-items:center; gap:6px; background:#1abc9c;">
                        <i class="fa-solid fa-file-excel"></i> Export Filter (Excel)
                    </button>
                    <button type="button" class="btn-primary" onclick="exportAllXLSX()" style="display:flex; align-items:center; gap:6px; background:#16a085;">
                        <i class="fa-solid fa-file-excel"></i> Export Semua (Excel)
                    </button>
                </div>
                <div id="report-content">
                    <p style="color: #7f8c8d; font-style: italic; text-align: center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</p>
                </div>
            </div>
            </div>
        </main>
    </div>
    
    <!-- Modal Search Outlet -->
    <div class="modal-overlay" id="djpModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-search"></i> Search DJP Outlet</h3>
                <button class="modal-close" onclick="closeDJPModal()">&times;</button>
            </div>
            <div class="search-box">
                <input type="text" id="outlet-search" placeholder="Cari nama outlet, nomor RS, atau kota..." onkeyup="searchOutlets()">
            </div>
            <div class="outlet-list" id="outlet-results">
                <div class="no-results">
                    <i class="fa-solid fa-info-circle" style="font-size: 40px; color: #bdc3c7; margin-bottom: 10px;"></i>
                    <p>Ketik minimal 2 karakter untuk mulai search</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Generic Popup Modal -->
    <div class="modal-overlay popup-modal" id="popupModal">
        <div class="modal-content">
            <div class="modal-header info" id="popupHeader">
                <div class="modal-icon"><i class="fa-solid fa-circle-info"></i></div>
                <h3 id="popupTitle" style="margin:0">Informasi</h3>
                <button class="modal-close" onclick="closePopup()">&times;</button>
            </div>
            <div id="popupBody" style="font-size:13px; line-height:1.5;"></div>
            <div class="modal-actions" id="popupActions">
                <button class="btn btn-info" onclick="closePopup()">OK</button>
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
        console.log('User role: <?php echo $user["role"]; ?>', 'Cabang ID: <?php echo $user["cabang_id"] ?? "none"; ?>');
        console.log('Sales forces data:', salesForcesData);
        
        <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
        // Administrator/Manager: Dynamic filter by cabang
        document.getElementById('filter_cabang').addEventListener('change', function() {
            const cabangId = this.value;
            const salesSelect = document.getElementById('filter_sales_force');
            salesSelect.innerHTML = '<option value="">Pilih Sales Force</option>';
            
            if (cabangId) {
                const filtered = salesForcesData.filter(sf => sf.cabang_id == cabangId);
                console.log('Filtered sales forces for cabang ' + cabangId + ':', filtered);
                filtered.forEach(sf => {
                    const option = document.createElement('option');
                    option.value = sf.reseller_id;
                    option.textContent = sf.nama_reseller;
                    salesSelect.appendChild(option);
                });
            } else {
                // Show all if no cabang selected
                salesForcesData.forEach(sf => {
                    const option = document.createElement('option');
                    option.value = sf.reseller_id;
                    option.textContent = sf.nama_reseller;
                    salesSelect.appendChild(option);
                });
            }
        });
        <?php else: ?>
        // Non-Admin: Load sales forces already filtered by cabang from PHP
        const salesSelect = document.getElementById('filter_sales_force');
        salesSelect.innerHTML = '<option value="">Pilih Sales Force</option>';
        
        if (salesForcesData.length === 0) {
            console.warn('WARNING: No sales forces found for this cabang!');
            salesSelect.innerHTML = '<option value="">Tidak ada sales force di cabang ini</option>';
        } else {
            salesForcesData.forEach(sf => {
                const option = document.createElement('option');
                option.value = sf.reseller_id;
                option.textContent = sf.nama_reseller;
                salesSelect.appendChild(option);
            });
            console.log('Loaded ' + salesForcesData.length + ' sales forces for cabang <?php echo $user["cabang_id"] ?? ""; ?>');
        }
        <?php endif; ?>
        
        // Load sales summary from inventory penjualan
        async function loadSalesSummary() {
            const tanggal = document.getElementById('filter_tanggal').value;
            const salesForceId = document.getElementById('filter_sales_force').value;
            
            if (!tanggal || !salesForceId) {
                showPopup({title:'Validasi', message:'Harap pilih tanggal dan sales force!', type:'warning'});
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
                    showPopup({title:'Error', message:'Response bukan JSON valid. Mohon cek console untuk detail.', type:'danger'});
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
                    
                    // Show history section and load full report
                    document.getElementById('history-section').style.display = 'block';
                    loadFullReport();
                } else {
                    showPopup({title:'Tidak Ada Data', message:(data.message || 'Tidak ada data penjualan untuk sales force ini pada tanggal tersebut'), type:'info'});
                    document.getElementById('sales-summary-section').style.display = 'none';
                    document.getElementById('outlet-tbody').innerHTML = '';
                }
            } catch (error) {
                console.error('Error:', error);
                showPopup({title:'Error', message:'Terjadi kesalahan saat mengambil data', type:'danger'});
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
                html += '<td><input type="text" class="outlet-id-display" value="' + (outletData.id_digipos || '-') + '" readonly></td>';
            } else {
                html += '<td><input type="text" class="outlet-id-display" readonly></td>';
            }
            
            html += '<td><input type="text" name="keterangan[]" placeholder="' + (isDJP ? 'DJP - Cross Selling' : 'Opsional') + '"></td>';
            html += '<td><button type="button" class="btn-delete-row" onclick="deleteRow(' + rowCounter + ')"><i class="fa-solid fa-trash"></i> Hapus</button></td>';
            
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
            const selectedOutletId = element.value;
            const outlet = outletsData.find(o => String(o.outlet_id) === String(selectedOutletId));
            outletIdDisplay.value = outlet && outlet.id_digipos ? outlet.id_digipos : '-';
        }
        
        function validateSubmit() {
            const rows = document.querySelectorAll('#outlet-tbody tr');
            
            if (rows.length === 0) {
                showPopup({title:'Validasi', message:'Harap tambahkan minimal 1 data outlet!', type:'warning'});
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
                const msg = '<div>Total qty per produk tidak cocok!</div><div style="margin-top:8px; font-family:monospace; white-space:pre-line;">' + errors.join('\n') + '</div>';
                showPopup({title:'Validasi Gagal', message:msg, type:'danger'});
                document.getElementById('validation-message').style.display = 'none';
                return false;
            }
            
            document.getElementById('validation-message').style.display = 'none';
            // Use popup confirm instead of native confirm
            showConfirm('Konfirmasi', 'Apakah data sudah benar?', function(){
                document.getElementById('form-penjualan-outlet').submit();
            });
            return false;
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
                    html += '<th>Tanggal</th><th>Sales Force</th><th>Produk</th><th>Outlet</th><th>ID Digipos</th><th>NIK KTP</th><th>Qty</th><th>Nominal</th><th>Keterangan</th>';
                    html += '</tr></thead><tbody>';
                    
                    let totalQty = 0;
                    let totalNominal = 0;
                    
                    data.data.forEach(item => {
                        html += '<tr>';
                        html += '<td>' + item.tanggal + '</td>';
                        html += '<td>' + item.nama_reseller + '</td>';
                        html += '<td>' + item.nama_produk + '</td>';
                        html += '<td>' + item.nama_outlet + '</td>';
                        html += '<td>' + (item.id_digipos || '-') + '</td>';
                        html += '<td>' + (item.nik_ktp || '-') + '</td>';
                        html += '<td>' + item.qty + '</td>';
                        html += '<td>Rp ' + parseFloat(item.nominal).toLocaleString('id-ID') + '</td>';
                        html += '<td>' + (item.keterangan || '-') + '</td>';
                        html += '</tr>';
                        
                        totalQty += parseInt(item.qty);
                        totalNominal += parseFloat(item.nominal);
                    });
                    
                    html += '<tr style="background: #f8f9fa; font-weight: bold;">';
                    html += '<td colspan="6">TOTAL</td>';
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
                showPopup({title:'Validasi', message:'Harap pilih Sales Force terlebih dahulu!', type:'warning'});
                return;
            }
            
            if (Object.keys(salesProductsData).length === 0) {
                showPopup({title:'Validasi', message:'Harap klik "Tampilkan Data" terlebih dahulu untuk memuat data penjualan!', type:'warning'});
                return;
            }
            
            document.getElementById('djpModal').style.display = 'flex';
            document.getElementById('outlet-search').value = '';
            document.getElementById('outlet-results').innerHTML = '<div class="no-results"><i class="fa-solid fa-info-circle" style="font-size: 40px; color: #bdc3c7; margin-bottom: 10px;"></i><p>Ketik minimal 2 karakter untuk mulai search</p></div>';
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
                document.getElementById('outlet-results').innerHTML = '<div class="no-results"><i class="fa-solid fa-info-circle" style="font-size: 40px; color: #bdc3c7; margin-bottom: 10px;"></i><p>Ketik minimal 2 karakter untuk mulai search</p></div>';
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
            resultsDiv.innerHTML = '<div class="no-results"><i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: #3498db; margin-bottom: 10px;"></i><p>Searching...</p></div>';
            
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
                        html += '<div class="outlet-item" onclick="selectDJPOutlet(' + outlet.outlet_id + ', \'' + outlet.nama_outlet.replace(/'/g, "\\'") + '\', \'' + outlet.nomor_rs + '\', \'' + (outlet.id_digipos || '') + '\')">';
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
        
        function selectDJPOutlet(outletId, outletName, nomorRs, idDigipos) {
            const outletData = {
                outlet_id: outletId,
                nama_outlet: outletName,
                nomor_rs: nomorRs,
                id_digipos: idDigipos
            };
            addOutletRow(true, outletData);
        }

        // Export Functions
        function exportFilteredCSV() {
            const tanggal = document.getElementById('filter_tanggal').value;
            const tanggalEnd = document.getElementById('filter_tanggal_end').value;
            const salesForceId = document.getElementById('filter_sales_force').value;
            if (!tanggal || !salesForceId) {
                showPopup({title:'Validasi', message:'Pilih tanggal dan sales force dulu untuk export filter', type:'warning'});
                return;
            }
            let url = 'input_penjualan_outlet.php?export=csv&sales_force_id=' + encodeURIComponent(salesForceId);
            if (tanggalEnd) {
                url += '&start_date=' + encodeURIComponent(tanggal) + '&end_date=' + encodeURIComponent(tanggalEnd);
            } else {
                url += '&tanggal=' + encodeURIComponent(tanggal);
            }
            window.location = url;
        }

        function exportAllCSV() {
            window.location = 'input_penjualan_outlet.php?export=csv';
        }

        // Popup helpers (global)
        function showPopup({title='Informasi', message='', type='info', buttons}) {
            let overlay = document.getElementById('popupModal');
            if (!overlay) return console.error('popupModal element not found');
            const header = document.getElementById('popupHeader');
            const titleEl = document.getElementById('popupTitle');
            const bodyEl = document.getElementById('popupBody');
            const actions = document.getElementById('popupActions');
            header.classList.remove('info','warning','danger','success');
            header.classList.add(type);
            const iconDiv = header.querySelector('.modal-icon');
            const iconClass = {
                info:'fa-solid fa-circle-info',
                warning:'fa-solid fa-triangle-exclamation',
                danger:'fa-solid fa-circle-xmark',
                success:'fa-solid fa-circle-check'
            }[type] || 'fa-solid fa-circle-info';
            iconDiv.innerHTML = '<i class="'+iconClass+'"></i>';
            titleEl.textContent = title;
            bodyEl.innerHTML = message;
            actions.innerHTML = '';
            if (buttons && buttons.length) {
                buttons.forEach(btn => {
                    const b = document.createElement('button');
                    b.className = 'btn ' + (btn.className || 'btn-info');
                    b.textContent = btn.text || 'OK';
                    b.onclick = () => { try { btn.onClick && btn.onClick(); } finally { closePopup(); } };
                    actions.appendChild(b);
                });
            } else {
                const ok = document.createElement('button');
                ok.className = 'btn btn-info'; ok.textContent = 'OK'; ok.onclick = closePopup; actions.appendChild(ok);
            }
            overlay.style.display = 'flex';
            overlay.classList.add('active');
        }

        function showConfirm(title, message, onYes, onNo) {
            showPopup({
                title, message, type:'warning',
                buttons:[
                    {text:'Batal', className:'btn-outline', onClick:() => { onNo && onNo(); }},
                    {text:'Ya, Lanjut', className:'btn-success', onClick:() => { onYes && onYes(); }}
                ]
            });
        }

        function closePopup() {
            const overlay = document.getElementById('popupModal');
            if(!overlay) return; overlay.classList.remove('active'); setTimeout(()=>{ overlay.style.display='none'; },150);
        }

        function exportFilteredXLSX() {
            const tanggal = document.getElementById('filter_tanggal').value;
            const tanggalEnd = document.getElementById('filter_tanggal_end').value;
            const salesForceId = document.getElementById('filter_sales_force').value;
            if (!tanggal || !salesForceId) {
                showPopup({title:'Validasi', message:'Pilih tanggal dan sales force dulu untuk export Excel', type:'warning'});
                return;
            }
            let url = 'input_penjualan_outlet.php?export=xlsx&sales_force_id=' + encodeURIComponent(salesForceId);
            if (tanggalEnd) {
                url += '&start_date=' + encodeURIComponent(tanggal) + '&end_date=' + encodeURIComponent(tanggalEnd);
            } else {
                url += '&tanggal=' + encodeURIComponent(tanggal);
            }
            window.location = url;
        }

        function exportAllXLSX() {
            window.location = 'input_penjualan_outlet.php?export=xlsx';
        }
        
        // Full Report Function - Always Load User's Cabang Data
        async function loadFullReport() {
            console.log('=== LOADING FULL REPORT ===');
            console.log('User role:', '<?php echo $user['role']; ?>');
            console.log('User cabang_id:', '<?php echo $user['cabang_id'] ?? 'NULL'; ?>');
            
            const formData = new FormData();
            formData.append('action', 'get_full_report');
            
            <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
            // Admin/Manager: Show all cabang data
            console.log('Admin/Manager - Loading all cabang data (no cabang filter)');
            <?php else: ?>
            // Non-Admin: Filter by user's cabang_id
            const cabangId = <?php echo $user['cabang_id'] ?? 0; ?>;
            formData.append('cabang_id', cabangId);
            console.log('Non-Admin - Filtering by cabang_id:', cabangId);
            <?php endif; ?>
            
            // Debug FormData
            console.log('FormData contents:');
            for (let pair of formData.entries()) {
                console.log(' -', pair[0], '=', pair[1]);
            }
            
            const reportDiv = document.getElementById('report-content');
            reportDiv.innerHTML = '<p style="color: #7f8c8d; font-style: italic; text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
            
            try {
                const response = await fetch('ajax_penjualan_outlet.php', {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                console.log('Report raw response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', responseText);
                    reportDiv.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;"><i class="fas fa-exclamation-triangle"></i> Error: Response bukan JSON valid. Check console.</p>';
                    return;
                }
                
                console.log('Report parsed data:', data);
                
                if (data.success && data.data.length > 0) {
                    let html = '<table class="input-table"><thead><tr>';
                    html += '<th>Tanggal</th><th>Cabang</th><th>Sales Force</th><th>Produk</th><th>Outlet</th><th>ID Digipos</th><th>NIK KTP</th><th>Qty</th><th>Nominal</th><th>Keterangan</th><th>Author</th>';
                    html += '</tr></thead><tbody>';
                    
                    let totalQty = 0;
                    let totalNominal = 0;
                    
                    data.data.forEach(item => {
                        html += '<tr>';
                        html += '<td>' + item.tanggal + '</td>';
                        html += '<td>' + (item.nama_cabang || '-') + '</td>';
                        html += '<td>' + item.nama_reseller + '</td>';
                        html += '<td>' + item.nama_produk + '</td>';
                        html += '<td>' + item.nama_outlet + '</td>';
                        html += '<td>' + (item.id_digipos || '-') + '</td>';
                        html += '<td>' + (item.nik_ktp || '-') + '</td>';
                        html += '<td>' + item.qty + '</td>';
                        html += '<td>Rp ' + parseFloat(item.nominal).toLocaleString('id-ID') + '</td>';
                        html += '<td>' + (item.keterangan || '-') + '</td>';
                        html += '<td>' + (item.author || '-') + '</td>';
                        html += '</tr>';
                        
                        totalQty += parseInt(item.qty);
                        totalNominal += parseFloat(item.nominal);
                    });
                    
                    html += '<tr style="background: #f8f9fa; font-weight: bold;">';
                    html += '<td colspan="7">TOTAL</td>';
                    html += '<td>' + totalQty + '</td>';
                    html += '<td>Rp ' + totalNominal.toLocaleString('id-ID') + '</td>';
                    html += '<td colspan="2"></td>';
                    html += '</tr>';
                    html += '</tbody></table>';
                    
                    html += '<p style="margin-top: 15px; font-size: 11px; color: #7f8c8d;"><strong>Total Records:</strong> ' + data.data.length + '</p>';
                    
                    reportDiv.innerHTML = html;
                    console.log('Report displayed successfully - ' + data.data.length + ' records');
                } else {
                    console.warn('No data or data.success is false:', data);
                    reportDiv.innerHTML = '<p style="color: #7f8c8d; font-style: italic; text-align: center; padding: 20px;">Belum ada history input penjualan outlet<?php if (!in_array($user["role"], ["administrator", "manager"])): ?> untuk cabang ini<?php endif; ?></p>';
                }
            } catch (error) {
                console.error('Error loading report:', error);
                reportDiv.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;"><i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan saat mengambil data</p>';
            }
        }
        
        // Auto-load history report on page load
        window.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded - loading history report...');
            loadFullReport();
        });
    </script>
    
    <script src="../../assets/js/force-lexend.js"></script>
</body>
</html>
