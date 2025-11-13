<?php
require_once '../../config/config.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser();
$conn = getDBConnection();

$action = $_POST['action'] ?? '';

if ($action === 'get_sales_summary') {
    try {
        $tanggal = $_POST['tanggal'] ?? '';
        $reseller_id = $_POST['reseller_id'] ?? '';
        
        // Debug logging
        error_log("AJAX get_sales_summary - Tanggal: " . $tanggal . ", Reseller ID: " . $reseller_id);
        
        if (empty($tanggal) || empty($reseller_id)) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap', 'debug' => ['tanggal' => $tanggal, 'reseller_id' => $reseller_id]]);
            exit;
        }
        
        // Query penjualan data from detail_penjualan table
        $stmt = $conn->prepare("
            SELECT 
                p.tanggal_penjualan as tanggal,
                r.nama_reseller,
                COALESCE(kp.nama_kategori, pr.kategori, 'Tanpa Kategori') as kategori,
                dp.nama_produk,
                dp.produk_id,
                SUM(dp.jumlah) as qty,
                SUM(dp.subtotal) as nominal,
                MAX(dp.harga_satuan) as harga
            FROM penjualan p
            JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
            JOIN reseller r ON p.reseller_id = r.reseller_id
            JOIN produk pr ON dp.produk_id = pr.produk_id
            LEFT JOIN kategori_produk kp ON pr.kategori_id = kp.kategori_id
            WHERE p.tanggal_penjualan = ? AND p.reseller_id = ?
            GROUP BY dp.produk_id, dp.nama_produk, r.nama_reseller, p.tanggal_penjualan
            ORDER BY dp.nama_produk
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("si", $tanggal, $reseller_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        if (count($data) > 0) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            // Check if penjualan exists but no detail_penjualan
            $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM penjualan WHERE tanggal_penjualan = ? AND reseller_id = ?");
            $check_stmt->bind_param("si", $tanggal, $reseller_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_row = $check_result->fetch_assoc();
            
            if ($check_row['total'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Penjualan ditemukan tapi tidak ada detail produk. Silakan cek data di menu Input Penjualan.', 'debug' => ['penjualan_found' => true, 'tanggal' => $tanggal, 'reseller_id' => $reseller_id]]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Tidak ada data penjualan untuk tanggal ' . $tanggal . ' dan reseller ID ' . $reseller_id . '. Silakan input penjualan terlebih dahulu di menu Input Penjualan.', 'debug' => ['penjualan_found' => false, 'tanggal' => $tanggal, 'reseller_id' => $reseller_id]]);
            }
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
} elseif ($action === 'get_report') {
    $tanggal = $_POST['tanggal'] ?? '';
    $sales_force_id = $_POST['sales_force_id'] ?? '';
    
    if (empty($tanggal) || empty($sales_force_id)) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
        exit;
    }
    
    // Query penjualan_outlet data
    $stmt = $conn->prepare("
        SELECT 
            po.tanggal,
            r.nama_reseller,
            pr.nama_produk,
            o.nama_outlet,
            o.id_digipos,
            o.nik_ktp,
            po.qty,
            po.nominal,
            po.keterangan
        FROM penjualan_outlet po
        JOIN reseller r ON po.sales_force_id = r.reseller_id
        JOIN produk pr ON po.produk_id = pr.produk_id
        JOIN outlet o ON po.outlet_id = o.outlet_id
        WHERE po.tanggal = ? AND po.sales_force_id = ?
        ORDER BY pr.nama_produk, o.nama_outlet
    ");
    
    $stmt->bind_param("si", $tanggal, $sales_force_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    
} elseif ($action === 'search_outlets') {
    $search = $_POST['search'] ?? '';
    
    if (strlen($search) < 2) {
        echo json_encode(['success' => false, 'message' => 'Minimal 2 karakter untuk search']);
        exit;
    }
    
    // Search outlets by name or nomor_rs (show all outlets - PJP and non-PJP)
        $search_param = '%' . $search . '%';
        $stmt = $conn->prepare("
            SELECT 
                outlet_id,
                nama_outlet,
                nomor_rs,
                id_digipos,
                city,
                type_outlet,
                sales_force_id,
                r.nama_reseller as sales_force_name
            FROM outlet
            LEFT JOIN reseller r ON outlet.sales_force_id = r.reseller_id
            WHERE (nama_outlet LIKE ? OR nomor_rs LIKE ? OR city LIKE ?)
            ORDER BY nama_outlet
            LIMIT 50
        ");
    
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    
} elseif ($action === 'get_full_report') {
    $cabang_id = isset($_POST['cabang_id']) && $_POST['cabang_id'] !== '' ? intval($_POST['cabang_id']) : null;
    
    error_log("get_full_report - cabang_id: " . ($cabang_id ?? 'NULL'));
    
    // Build query with optional cabang filter
    $query = "
        SELECT 
            po.tanggal,
            COALESCE(c.nama_cabang, cr.nama_cabang) AS nama_cabang,
            r.nama_reseller,
            pr.nama_produk,
            o.nama_outlet,
            o.id_digipos,
            o.nik_ktp,
            po.qty,
            po.nominal,
            po.keterangan,
            u.username as author
        FROM penjualan_outlet po
        JOIN reseller r ON po.sales_force_id = r.reseller_id
        JOIN produk pr ON po.produk_id = pr.produk_id
        JOIN outlet o ON po.outlet_id = o.outlet_id
        LEFT JOIN cabang c ON po.cabang_id = c.cabang_id
        LEFT JOIN cabang cr ON r.cabang_id = cr.cabang_id
        LEFT JOIN users u ON po.created_by = u.user_id
        WHERE 1=1
    ";
    
    if ($cabang_id !== null && $cabang_id > 0) {
        // Include records explicitly tagged with cabang_id, or legacy records where po.cabang_id is NULL
        // but reseller's cabang matches the requested cabang
        $query .= " AND (po.cabang_id = ? OR (po.cabang_id IS NULL AND r.cabang_id = ?))";
    }
    
    $query .= " ORDER BY po.tanggal DESC, r.nama_reseller, pr.nama_produk, o.nama_outlet";
    
    $stmt = $conn->prepare($query);
    
    if ($cabang_id !== null && $cabang_id > 0) {
        $stmt->bind_param("ii", $cabang_id, $cabang_id);
    }
    
    if (!$stmt->execute()) {
        error_log("get_full_report - Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Query execution failed']);
        exit;
    }
    
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    error_log("get_full_report - SUCCESS: " . count($data) . " records found");
    echo json_encode(['success' => true, 'data' => $data]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
