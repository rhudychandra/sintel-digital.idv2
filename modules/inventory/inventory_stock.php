 <?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Detect optional columns in inventory (cabang_id, status_approval) to build flexible queries
$inventory_columns = [];
try {
    $colRes = $conn->query("SHOW COLUMNS FROM inventory");
    while ($colRes && $c = $colRes->fetch_assoc()) { $inventory_columns[$c['Field']] = true; }
} catch (Exception $e) { /* ignore */ }
$has_cabang_id = isset($inventory_columns['cabang_id']);
$has_status_approval = isset($inventory_columns['status_approval']);

// Get filter parameters
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today
$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';
$filter_produk = isset($_GET['produk_id']) ? $_GET['produk_id'] : '';
$filter_tipe = isset($_GET['tipe_transaksi']) ? $_GET['tipe_transaksi'] : '';

// Pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get statistics for current month
$stats = [
    'total_transaksi' => 0,
    'total_masuk' => 0,
    'total_keluar' => 0,
    'nilai_inventory' => 0
];

try {
    $stats_query = "SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN i.tipe_transaksi='masuk' THEN i.jumlah ELSE 0 END) as total_masuk,
        SUM(CASE WHEN i.tipe_transaksi='keluar' THEN i.jumlah ELSE 0 END) as total_keluar,
        SUM(CASE WHEN i.tipe_transaksi='masuk' THEN i.jumlah * p.harga ELSE -i.jumlah * p.harga END) as nilai_inventory
    FROM inventory i
    LEFT JOIN produk p ON i.produk_id = p.produk_id
    WHERE MONTH(i.tanggal) = MONTH(CURRENT_DATE())
    AND YEAR(i.tanggal) = YEAR(CURRENT_DATE())";
    
    // Add role-based filter for non-administrator/manager
    if (!in_array($user['role'], ['administrator', 'manager'])) {
        $stats_query .= " AND (i.cabang_id = " . intval($user['cabang_id']) . " OR i.cabang_id IS NULL)";
    }
    
    $result = $conn->query($stats_query);
    if ($result && $result->num_rows > 0) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    $error_message = "Error loading statistics: " . $e->getMessage();
}

// Get low stock products
$low_stock_products = [];
try {
    $low_stock_query = "SELECT 
        p.produk_id,
        p.nama_produk,
        p.stok,
        p.kategori,
        COALESCE(c.nama_cabang, '-') as nama_cabang
    FROM produk p
    LEFT JOIN inventory i ON p.produk_id = i.produk_id
    LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
    WHERE p.stok < 10
    AND p.status = 'active'";
    
    // Add role-based filter
    if (!in_array($user['role'], ['administrator', 'manager'])) {
        $low_stock_query .= " AND (i.cabang_id = " . intval($user['cabang_id']) . " OR i.cabang_id IS NULL)";
    }
    
    $low_stock_query .= " GROUP BY p.produk_id, i.cabang_id
    ORDER BY p.stok ASC
    LIMIT 5";
    
    $result = $conn->query($low_stock_query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $low_stock_products[] = $row;
        }
    }
} catch (Exception $e) {
    // Silent fail for low stock
}

// Build query for inventory history with filters
$where_conditions = ["1=1"];
$params = [];
$types = "";

// Date filter
$where_conditions[] = "i.tanggal BETWEEN ? AND ?";
$params[] = $filter_start_date;
$params[] = $filter_end_date;
$types .= "ss";

// Role-based cabang filter (non-admin/manager): restrict to effective cabang
// Effective cabang resolution priority: inventory.cabang_id -> reseller.cabang_id -> user.cabang_id
if (!in_array($user['role'], ['administrator', 'manager'])) {
    $where_conditions[] = "((i.cabang_id IS NOT NULL AND i.cabang_id = ?) OR (i.cabang_id IS NULL AND ((r.cabang_id IS NOT NULL AND r.cabang_id = ?) OR (r.cabang_id IS NULL AND u.cabang_id = ?))))";
    $params[] = $user['cabang_id'];
    $params[] = $user['cabang_id'];
    $params[] = $user['cabang_id'];
    $types .= "iii";
}

// Cabang filter (admin/manager selectable): include lines where effective cabang matches selection
if (!empty($filter_cabang) && in_array($user['role'], ['administrator', 'manager'])) {
    $where_conditions[] = "((i.cabang_id IS NOT NULL AND i.cabang_id = ?) OR (i.cabang_id IS NULL AND ((r.cabang_id IS NOT NULL AND r.cabang_id = ?) OR (r.cabang_id IS NULL AND u.cabang_id = ?))))";
    $params[] = $filter_cabang;
    $params[] = $filter_cabang;
    $params[] = $filter_cabang;
    $types .= "iii";
}

// Produk filter
if (!empty($filter_produk)) {
    $where_conditions[] = "i.produk_id = ?";
    $params[] = $filter_produk;
    $types .= "i";
}

// Tipe transaksi filter
if (!empty($filter_tipe)) {
    $where_conditions[] = "i.tipe_transaksi = ?";
    $params[] = $filter_tipe;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Build optional approval filter for subqueries (branch stock computation)
$approval_filter_sub = $has_status_approval ? " AND ii.status_approval = 'approved'" : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
    FROM inventory i 
    LEFT JOIN penjualan pj ON pj.no_invoice = i.referensi
    LEFT JOIN reseller r ON pj.reseller_id = r.reseller_id
    LEFT JOIN users u ON i.user_id = u.user_id
    WHERE " . $where_clause;

$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get inventory history with filters
$inventory_query = "SELECT 
    i.inventory_id,
    i.tanggal,
    i.tipe_transaksi,
    i.jumlah,
    i.stok_sebelum,
    i.stok_sesudah,
    i.referensi,
    i.keterangan,
    i.cabang_id,
    p.nama_produk,
    p.kategori,
    p.harga,
    COALESCE(c.nama_cabang, cr.nama_cabang, uc.nama_cabang, 'Pusat/Global') as nama_cabang,
    CASE 
        WHEN i.cabang_id IS NOT NULL THEN i.cabang_id
        WHEN r.cabang_id IS NOT NULL THEN r.cabang_id
        WHEN u.cabang_id IS NOT NULL THEN u.cabang_id
        ELSE NULL
    END AS cabang_effective_id,
    (
        SELECT COALESCE(SUM(CASE WHEN ii.tipe_transaksi='masuk' THEN ii.jumlah ELSE -ii.jumlah END),0)
        FROM inventory ii
        LEFT JOIN penjualan p2 ON p2.no_invoice = ii.referensi
        LEFT JOIN reseller r2 ON p2.reseller_id = r2.reseller_id
        LEFT JOIN users u2 ON ii.user_id = u2.user_id
        WHERE ii.produk_id = i.produk_id
          AND (
                (ii.cabang_id IS NOT NULL AND ii.cabang_id = 
                    (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                )
                OR (
                    ii.cabang_id IS NULL AND (
                        r2.cabang_id = (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                        OR (
                            r2.cabang_id IS NULL AND u2.cabang_id = (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                        )
                    )
                )
            )
          AND (ii.tanggal < i.tanggal OR (ii.tanggal = i.tanggal AND ii.inventory_id < i.inventory_id))" 
          . $approval_filter_sub . 
    ") AS stok_sebelum_cabang,
    CASE WHEN i.tipe_transaksi='masuk' THEN 
        (
            (
                SELECT COALESCE(SUM(CASE WHEN ii.tipe_transaksi='masuk' THEN ii.jumlah ELSE -ii.jumlah END),0)
                FROM inventory ii
                LEFT JOIN penjualan p2 ON p2.no_invoice = ii.referensi
                LEFT JOIN reseller r2 ON p2.reseller_id = r2.reseller_id
                LEFT JOIN users u2 ON ii.user_id = u2.user_id
                WHERE ii.produk_id = i.produk_id
                  AND (
                        (ii.cabang_id IS NOT NULL AND ii.cabang_id = 
                            (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                        )
                        OR (
                            ii.cabang_id IS NULL AND (
                                r2.cabang_id = (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                                OR (
                                    r2.cabang_id IS NULL AND u2.cabang_id = (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                                )
                            )
                        )
                    )
                  AND (ii.tanggal < i.tanggal OR (ii.tanggal = i.tanggal AND ii.inventory_id < i.inventory_id))" 
                  . $approval_filter_sub . 
            ") + i.jumlah
        )
        ELSE 
        (
            (
                SELECT COALESCE(SUM(CASE WHEN ii.tipe_transaksi='masuk' THEN ii.jumlah ELSE -ii.jumlah END),0)
                FROM inventory ii
                LEFT JOIN penjualan p2 ON p2.no_invoice = ii.referensi
                LEFT JOIN reseller r2 ON p2.reseller_id = r2.reseller_id
                LEFT JOIN users u2 ON ii.user_id = u2.user_id
                WHERE ii.produk_id = i.produk_id
                  AND (
                        (ii.cabang_id IS NOT NULL AND ii.cabang_id = 
                            (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                        )
                        OR (
                            ii.cabang_id IS NULL AND (
                                r2.cabang_id = (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                                OR (
                                    r2.cabang_id IS NULL AND u2.cabang_id = (CASE WHEN i.cabang_id IS NOT NULL THEN i.cabang_id WHEN r.cabang_id IS NOT NULL THEN r.cabang_id WHEN u.cabang_id IS NOT NULL THEN u.cabang_id ELSE NULL END)
                                )
                            )
                        )
                    )
                  AND (ii.tanggal < i.tanggal OR (ii.tanggal = i.tanggal AND ii.inventory_id < i.inventory_id))" 
                  . $approval_filter_sub . 
            ") - i.jumlah
        )
    END AS stok_sesudah_cabang,
    u.full_name as user_name,
    uc.nama_cabang as user_cabang
FROM inventory i
LEFT JOIN produk p ON i.produk_id = p.produk_id
LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
LEFT JOIN users u ON i.user_id = u.user_id
LEFT JOIN cabang uc ON u.cabang_id = uc.cabang_id
LEFT JOIN penjualan pj ON pj.no_invoice = i.referensi
LEFT JOIN reseller r ON pj.reseller_id = r.reseller_id
LEFT JOIN cabang cr ON r.cabang_id = cr.cabang_id
WHERE " . $where_clause . "
ORDER BY i.tanggal DESC, i.inventory_id DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($inventory_query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$inventory_result = $stmt->get_result();

$inventory_data = [];
while ($row = $inventory_result->fetch_assoc()) {
    $inventory_data[] = $row;
}

// Get cabang list for filter dropdown
if (in_array($user['role'], ['administrator', 'manager'])) {
    $cabang_list = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");
} else {
    $stmt = $conn->prepare("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' AND cabang_id = ? ORDER BY nama_cabang");
    $stmt->bind_param("i", $user['cabang_id']);
    $stmt->execute();
    $cabang_list = $stmt->get_result();
}

// Get produk list for filter dropdown
$produk_list = $conn->query("SELECT produk_id, nama_produk FROM produk WHERE status = 'active' ORDER BY nama_produk");

// Get stock data based on role
if (in_array($user['role'], ['administrator', 'manager'])) {
    // For Administrator & Manager: Get all cabang with custom sorting
    $cabang_data = [];
    $cabang_result = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");
    
    // Define custom order with exact names
    $custom_order = [
        'warehouse lubuklinggau',
        'warehouse lahat',
        'tap kota lubuklinggau',
        'tap lahat',
        'tap musi rawas',
        'tap kota pagar alam',
        'tap musi rawas utara',
        'tap empat lawang (tebing tinggi)',
        'tap empat lawang (pendopo)',
        'player inner',
        'out cluster'
    ];
    
    // Collect all cabang
    $all_cabang = [];
    while ($row = $cabang_result->fetch_assoc()) {
        $all_cabang[] = $row;
    }
    
    // Sort cabang based on custom order
    usort($all_cabang, function($a, $b) use ($custom_order) {
        $nama_a = strtolower(trim($a['nama_cabang']));
        $nama_b = strtolower(trim($b['nama_cabang']));
        
        // Find position in custom order
        $pos_a = array_search($nama_a, $custom_order);
        $pos_b = array_search($nama_b, $custom_order);
        
        // If both found in custom order, sort by position
        if ($pos_a !== false && $pos_b !== false) {
            return $pos_a - $pos_b;
        }
        
        // If only A found, A comes first
        if ($pos_a !== false) return -1;
        
        // If only B found, B comes first
        if ($pos_b !== false) return 1;
        
        // If neither found, sort alphabetically
        return strcmp($nama_a, $nama_b);
    });
    
    $cabang_data = $all_cabang;
    
    // Group by category with products and stock per cabang
    $stock_by_category_admin = [];
    $all_products = $conn->query("SELECT produk_id, nama_produk, kategori, harga FROM produk WHERE status = 'active' ORDER BY kategori, nama_produk");
    
    while ($product = $all_products->fetch_assoc()) {
        $product_id = $product['produk_id'];
        $kategori = $product['kategori'];
        
        // Initialize category if not exists
        if (!isset($stock_by_category_admin[$kategori])) {
            $stock_by_category_admin[$kategori] = [
                'products' => [],
                'total_cabang_stocks' => []
            ];
            
            // Initialize total for each cabang
            foreach ($cabang_data as $cabang) {
                $stock_by_category_admin[$kategori]['total_cabang_stocks'][$cabang['cabang_id']] = 0;
            }
        }
        
        // Get stock for each cabang for this product
        $product_cabang_stocks = [];
        $product_total = 0;
        
        foreach ($cabang_data as $cabang) {
            $cabang_id = $cabang['cabang_id'];
            
            // Calculate stock based on approved transactions only
            // Build dynamic stock query depending on available columns
            $stock_query = "SELECT COALESCE(SUM(CASE WHEN tipe_transaksi='masuk' THEN jumlah ELSE -jumlah END),0) AS total_stock FROM inventory WHERE produk_id = ?";
            if ($has_cabang_id) {
                $stock_query .= " AND cabang_id = ?";
            }
            // Only filter approval if column exists
            if ($has_status_approval) {
                $stock_query .= " AND status_approval = 'approved'";
            }
            $stmt = $conn->prepare($stock_query);
            if ($has_cabang_id) {
                $stmt->bind_param("ii", $product_id, $cabang_id);
            } else {
                $stmt->bind_param("i", $product_id);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stock = 0;
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stock = $row['total_stock'];
            }
            
            $product_cabang_stocks[$cabang_id] = $stock;
            $product_total += $stock;
            
            // Add to category total
            $stock_by_category_admin[$kategori]['total_cabang_stocks'][$cabang_id] += $stock;
        }
        
        // Add product to category
        $stock_by_category_admin[$kategori]['products'][] = [
            'nama_produk' => $product['nama_produk'],
            'harga' => $product['harga'],
            'cabang_stocks' => $product_cabang_stocks,
            'total_stock' => $product_total,
            'nilai' => $product_total * $product['harga']
        ];
    }
} else {
    // For other roles: Group by category with expandable products
    $user_cabang_id = $user['cabang_id'];
    
    // Get cabang name
    // Try to get cabang name from session cabang_id first
    $user_cabang_name = 'Unknown';
    if (!empty($user_cabang_id)) {
        $stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
        $stmt->bind_param("i", $user_cabang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $user_cabang_name = $result->fetch_assoc()['nama_cabang'];
        }
        $stmt->close();
    } else {
        // If session doesn't have cabang_id, try to fetch from users table and update session
        if (!empty($user['user_id'])) {
            $stmt = $conn->prepare("SELECT u.cabang_id, c.nama_cabang FROM users u LEFT JOIN cabang c ON u.cabang_id = c.cabang_id WHERE u.user_id = ?");
            $stmt->bind_param("i", $user['user_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                if (!empty($row['cabang_id'])) {
                    $user_cabang_id = $row['cabang_id'];
                    $user_cabang_name = $row['nama_cabang'] ?? 'Unknown';
                    // update session so subsequent pages have cabang_id
                    $_SESSION['cabang_id'] = $user_cabang_id;
                }
            }
            $stmt->close();
        }
    }

    // Ensure $cabang_data is defined for non-admin users so templates using it won't error
    $cabang_data = [
        ['cabang_id' => $user_cabang_id, 'nama_cabang' => $user_cabang_name]
    ];
    
    // Get all products grouped by category
    $stock_by_category = [];
    $all_products = $conn->query("SELECT produk_id, nama_produk, kategori, harga FROM produk WHERE status = 'active' ORDER BY kategori, nama_produk");
    
    while ($product = $all_products->fetch_assoc()) {
        $product_id = $product['produk_id'];
        $kategori = $product['kategori'];
        
        // Calculate stock based on approved transactions only
        $stock_query = "SELECT COALESCE(SUM(CASE WHEN tipe_transaksi='masuk' THEN jumlah ELSE -jumlah END),0) AS total_stock FROM inventory WHERE produk_id = ?";
        if ($has_cabang_id) {
            $stock_query .= " AND cabang_id = ?";
        }
        if ($has_status_approval) {
            $stock_query .= " AND status_approval = 'approved'";
        }
        $stmt = $conn->prepare($stock_query);
        if ($has_cabang_id) {
            $stmt->bind_param("ii", $product_id, $user_cabang_id);
        } else {
            $stmt->bind_param("i", $product_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stock = 0;
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stock = $row['total_stock'];
        }
        
        // Initialize category if not exists
        if (!isset($stock_by_category[$kategori])) {
            $stock_by_category[$kategori] = [
                'products' => [],
                'total_qty' => 0,
                'total_nilai' => 0
            ];
        }
        
        // Add product to category
        $stock_by_category[$kategori]['products'][] = [
            'nama_produk' => $product['nama_produk'],
            'harga' => $product['harga'],
            'stock' => $stock,
            'nilai' => $stock * $product['harga']
        ];
        
        // Update category totals
        $stock_by_category[$kategori]['total_qty'] += $stock;
        $stock_by_category[$kategori]['total_nilai'] += ($stock * $product['harga']);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Stock - Inventory System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
    <style>
        .filter-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-filter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
        }
        
        .btn-reset {
            background: #e9ecef;
            color: #495057;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-reset:hover {
            background: #dee2e6;
        }
        
        .badge-masuk {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-keluar {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .low-stock-alert {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .low-stock-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ffe69c;
        }
        
        .low-stock-item:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Inventory</h2>
                <p><?php echo htmlspecialchars($user['full_name']); ?></p>
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
                <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory_stock.php" class="nav-item active">
                    <span class="nav-icon">📦</span>
                    <span>Stock Information</span>
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
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>📋 Riwayat Transaksi Stock</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">📊</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_transaksi'] ?? 0); ?></h3>
                            <p>Total Transaksi (Bulan Ini)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #27ae60;">📥</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_masuk'] ?? 0); ?></h3>
                            <p>Total Stok Masuk</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">📤</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_keluar'] ?? 0); ?></h3>
                            <p>Total Stok Keluar</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">💰</div>
                        <div class="stat-info">
                            <h3>Rp <?php echo number_format($stats['nilai_inventory'] ?? 0, 0, ',', '.'); ?></h3>
                            <p>Nilai Inventory</p>
                        </div>
                    </div>
                </div>
                
                <!-- Low Stock Alert -->
                <?php if (!empty($low_stock_products)): ?>
                <div class="low-stock-alert">
                    <h3 style="margin: 0 0 15px 0; color: #856404;">⚠️ Produk dengan Stok Rendah</h3>
                    <?php foreach ($low_stock_products as $product): ?>
                    <div class="low-stock-item">
                        <div>
                            <strong><?php echo htmlspecialchars($product['nama_produk']); ?></strong>
                            <small style="color: #856404; margin-left: 10px;"><?php echo htmlspecialchars($product['nama_cabang']); ?></small>
                        </div>
                        <span style="color: #dc3545; font-weight: 600;">Stok: <?php echo $product['stok']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Stock Per Cabang Table -->
                <div class="table-container" style="margin-bottom: 30px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid #dee2e6;">
                    <div class="table-header" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 15px 20px; border-radius: 12px 12px 0 0;">
                        <div>
                            <h2 style="color: white; margin: 0 0 8px 0;">📦 Stock Terupdate Per Cabang</h2>
                            <div style="color: rgba(255, 255, 255, 0.9); font-size: 14px;">
                                <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                                    Menampilkan stock semua produk di semua cabang (Pivot Table)
                                <?php else: ?>
                                    Menampilkan stock semua produk di cabang: <strong><?php echo htmlspecialchars($user_cabang_name); ?></strong>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="export_stock_excel.php" class="btn-export" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                                <span>📊</span>
                                <span>Export Excel</span>
                            </a>
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                            <!-- CATEGORY GROUPED TABLE for Administrator & Manager -->
                            <table class="data-table" style="font-size: 13px;">
                                <thead>
                                    <tr>
                                        <th style="padding: 8px; width: 40px;">No</th>
                                        <th style="padding: 8px 10px;">Kategori</th>
                                        <?php foreach ($cabang_data as $cabang): ?>
                                            <th style="padding: 8px; width: 80px;"><?php echo htmlspecialchars($cabang['nama_cabang']); ?></th>
                                        <?php endforeach; ?>
                                        <th style="padding: 8px; width: 70px;">Total Qty</th>
                                        <th style="padding: 8px; width: 100px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    if (!empty($stock_by_category_admin)) {
                                        foreach ($stock_by_category_admin as $kategori => $data) {
                                            $kategori_id = 'kategori-admin-' . preg_replace('/[^a-z0-9]/i', '-', strtolower($kategori));
                                            $total_qty_all = array_sum($data['total_cabang_stocks']);
                                            ?>
                                            <tr style="background: #ffffff; border-bottom: 2px solid #e9ecef;">
                                                <td style="padding: 12px; text-align: center; font-weight: 600;"><?php echo $no++; ?></td>
                                                <td style="padding: 12px;">
                                                    <div style="display: flex; align-items: center; gap: 12px;">
                                                        <span style="color: #f39c12; font-size: 16px;">📁</span>
                                                        <div style="display: flex; align-items: center; gap: 10px;">
                                                            <div>
                                                                <strong style="color: #8B1538; font-size: 14px;"><?php echo htmlspecialchars($kategori); ?></strong>
                                                                <small style="color: #7f8c8d; margin-left: 8px; font-weight: 400;">(<?php echo count($data['products']); ?> produk)</small>
                                                            </div>
                                                            <button onclick="toggleCategory('<?php echo $kategori_id; ?>')" style="background: linear-gradient(135deg, #8B1538 0%, #C84B31 100%); border: none; cursor: pointer; padding: 6px 12px; border-radius: 6px; transition: all 0.3s; display: flex; align-items: center; gap: 6px; font-size: 12px; color: white; font-weight: 500; font-family: 'Lexend', sans-serif; box-shadow: 0 2px 8px rgba(139, 21, 56, 0.3);">
                                                                <span id="icon-<?php echo $kategori_id; ?>" style="font-size: 14px; display: inline-block; transition: transform 0.3s;">▼</span>
                                                                <span>Detail</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php foreach ($cabang_data as $cabang) {
                                                    $stock = $data['total_cabang_stocks'][$cabang['cabang_id']] ?? 0;
                                                    $bg_color = '';
                                                    if ($stock == 0) $bg_color = '#fee';
                                                    elseif ($stock < 50) $bg_color = '#fff3cd';
                                                    elseif ($stock < 200) $bg_color = '#d1ecf1';
                                                    else $bg_color = '#d4edda';
                                                    ?>
                                                    <td style="background: <?php echo $bg_color; ?>; text-align: center; padding: 12px;">
                                                        <strong style="font-size: 14px;"><?php echo number_format($stock); ?></strong>
                                                    </td>
                                                <?php } ?>
                                                <td style="padding: 12px; text-align: center; background: #f8f9fa;"><strong style="font-size: 15px; color: #2c3e50;"><?php echo number_format($total_qty_all); ?></strong></td>
                                                <td style="padding: 12px;">
                                                    <?php
                                                    if ($total_qty_all == 0) {
                                                        echo '<span style="background: #fee; color: #dc3545; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">❌ Out</span>';
                                                    } elseif ($total_qty_all < 50) {
                                                        echo '<span style="background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">⚠️ Low</span>';
                                                    } elseif ($total_qty_all < 200) {
                                                        echo '<span style="background: #d1ecf1; color: #0c5460; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">📊 Medium</span>';
                                                    } else {
                                                        echo '<span style="background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">✅ Good</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php foreach ($data['products'] as $product) { ?>
                                                <tr class="product-detail <?php echo $kategori_id; ?>" style="display: none; background: #fafbfc; border-bottom: 1px solid #f0f0f0;">
                                                    <td style="padding: 8px;"></td>
                                                    <td style="padding: 8px; padding-left: 50px;">
                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                            <span style="color: #adb5bd; font-size: 14px;">└─</span>
                                                            <span style="color: #495057; font-size: 13px;"><?php echo htmlspecialchars($product['nama_produk']); ?></span>
                                                        </div>
                                                    </td>
                                                    <?php foreach ($cabang_data as $cabang) {
                                                        $stock = $product['cabang_stocks'][$cabang['cabang_id']] ?? 0;
                                                        ?>
                                                        <td style="text-align: center; padding: 8px; font-size: 13px;">
                                                            <strong><?php echo number_format($stock); ?></strong>
                                                        </td>
                                                    <?php } ?>
                                                    <td style="padding: 8px; text-align: center; background: #f8f9fa;"><strong style="font-size: 13px;"><?php echo number_format($product['total_stock']); ?></strong></td>
                                                    <td style="padding: 8px;">
                                                        <?php
                                                        $ts = $product['total_stock'];
                                                        if ($ts == 0) {
                                                            echo '<span style="background: #fee; color: #dc3545; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">❌ Out</span>';
                                                        } elseif ($ts < 10) {
                                                            echo '<span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">⚠️ Low</span>';
                                                        } elseif ($ts < 50) {
                                                            echo '<span style="background: #d1ecf1; color: #0c5460; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">📊 Medium</span>';
                                                        } else {
                                                            echo '<span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">✅ Good</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php } // end products foreach
                                        } // end kategori foreach
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="<?php echo 4 + count($cabang_data); ?>" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                                <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                                <strong>Belum ada data produk</strong>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                            
                            <script>
                            function toggleCategory(categoryId) {
                                const products = document.querySelectorAll('.product-detail.' + categoryId);
                                const icon = document.getElementById('icon-' + categoryId);
                                
                                products.forEach(product => {
                                    if (product.style.display === 'none' || product.style.display === '') {
                                        product.style.display = 'table-row';
                                        icon.style.transform = 'rotate(180deg)';
                                    } else {
                                        product.style.display = 'none';
                                        icon.style.transform = 'rotate(0deg)';
                                    }
                                });
                            }
                            </script>
                        <?php else: ?>
                            <!-- CATEGORY GROUPED TABLE for other roles -->
                            <table class="data-table" style="font-size: 13px;">
                                <thead>
                                    <tr>
                                        <th style="padding: 8px; width: 40px;">No</th>
                                        <th style="padding: 8px 10px;">Cabang</th>
                                        <th style="padding: 8px 10px;">Kategori</th>
                                        <th style="padding: 8px; width: 70px;">Qty</th>
                                        <th style="padding: 8px; width: 110px;">Nilai Stock</th>
                                        <th style="padding: 8px; width: 100px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    if (!empty($stock_by_category)):
                                        foreach ($stock_by_category as $kategori => $data): 
                                            $total_qty = $data['total_qty'];
                                            $total_nilai = $data['total_nilai'];
                                            $kategori_id = 'kategori-' . preg_replace('/[^a-z0-9]/i', '-', strtolower($kategori));
                                    ?>
                                    <!-- Category Row -->
                                    <tr style="background: #ffffff; border-bottom: 2px solid #e9ecef;">
                                        <td style="padding: 12px; text-align: center; font-weight: 600;"><?php echo $no++; ?></td>
                                        <td style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($user_cabang_name); ?></td>
                                        <td style="padding: 12px;">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <span style="color: #f39c12; font-size: 16px;">📁</span>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div>
                                                        <strong style="color: #8B1538; font-size: 14px;"><?php echo htmlspecialchars($kategori); ?></strong>
                                                        <small style="color: #7f8c8d; margin-left: 8px; font-weight: 400;">(<?php echo count($data['products']); ?> produk)</small>
                                                    </div>
                                                    <button onclick="toggleCategory('<?php echo $kategori_id; ?>')" style="background: linear-gradient(135deg, #8B1538 0%, #C84B31 100%); border: none; cursor: pointer; padding: 6px 12px; border-radius: 6px; transition: all 0.3s; display: flex; align-items: center; gap: 6px; font-size: 12px; color: white; font-weight: 500; font-family: 'Lexend', sans-serif; box-shadow: 0 2px 8px rgba(139, 21, 56, 0.3);">
                                                        <span id="icon-<?php echo $kategori_id; ?>" style="font-size: 14px; display: inline-block; transition: transform 0.3s;">▼</span>
                                                        <span>Detail</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 12px; text-align: center;"><strong style="font-size: 15px; color: #2c3e50;"><?php echo number_format($total_qty); ?></strong></td>
                                        <td style="padding: 12px;"><strong style="color: #27ae60; font-size: 14px;">Rp <?php echo number_format($total_nilai, 0, ',', '.'); ?></strong></td>
                                        <td style="padding: 12px;">
                                            <?php if ($total_qty == 0): ?>
                                                <span style="background: #fee; color: #dc3545; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">❌ Out</span>
                                            <?php elseif ($total_qty < 50): ?>
                                                <span style="background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">⚠️ Low</span>
                                            <?php elseif ($total_qty < 200): ?>
                                                <span style="background: #d1ecf1; color: #0c5460; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">📊 Medium</span>
                                            <?php else: ?>
                                                <span style="background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">✅ Good</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <?php 
                                    // Render product detail rows for this category (non-admin view)
                                    foreach ($data['products'] as $product): 
                                        $qty = (int)($product['stock'] ?? 0);
                                        $nilai = (float)($product['nilai'] ?? 0);
                                    ?>
                                    <tr class="product-detail <?php echo $kategori_id; ?>" style="display: none; background: #fafbfc; border-bottom: 1px solid #f0f0f0;">
                                        <td style="padding: 8px;"></td>
                                        <td style="padding: 8px;"></td>
                                        <td style="padding: 8px; padding-left: 50px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="color: #adb5bd; font-size: 14px;">└─</span>
                                                <span style="color: #495057; font-size: 13px;"><?php echo htmlspecialchars($product['nama_produk']); ?></span>
                                            </div>
                                        </td>
                                        <td style="text-align: center; padding: 8px; font-size: 13px;">
                                            <strong><?php echo number_format($qty); ?></strong>
                                        </td>
                                        <td style="padding: 8px;"><strong style="color: #27ae60; font-size: 13px;">Rp <?php echo number_format($nilai, 0, ',', '.'); ?></strong></td>
                                        <td style="padding: 8px;">
                                            <?php
                                                $status_badge = '';
                                                if ($qty === 0) {
                                                    $status_badge = '<span style="background: #fee; color: #dc3545; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">❌ Out</span>';
                                                } elseif ($qty < 10) {
                                                    $status_badge = '<span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">⚠️ Low</span>';
                                                } elseif ($qty < 50) {
                                                    $status_badge = '<span style="background: #d1ecf1; color: #0c5460; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">📊 Medium</span>';
                                                } else {
                                                    $status_badge = '<span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">✅ Good</span>';
                                                }
                                                echo $status_badge;
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; // end products foreach ?>
                                    <?php endforeach; // end categories foreach ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                            <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                            <strong>Belum ada data produk</strong>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <script>
                            function toggleCategory(categoryId) {
                                const products = document.querySelectorAll('.product-detail.' + categoryId);
                                const icon = document.getElementById('icon-' + categoryId);
                                
                                products.forEach(product => {
                                    if (product.style.display === 'none' || product.style.display === '') {
                                        product.style.display = 'table-row';
                                        icon.style.transform = 'rotate(180deg)';
                                    } else {
                                        product.style.display = 'none';
                                        icon.style.transform = 'rotate(0deg)';
                                    }
                                });
                            }
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Filter Box -->
                <div class="filter-box">
                    <h2 style="margin: 0 0 20px 0; color: #2c3e50;">🔍 Filter Riwayat Transaksi</h2>
                    <form method="GET" action="inventory_stock.php">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label>Tanggal Mulai</label>
                                <input type="date" name="start_date" value="<?php echo $filter_start_date; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label>Tanggal Akhir</label>
                                <input type="date" name="end_date" value="<?php echo $filter_end_date; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label>Cabang</label>
                                <select name="cabang_id">
                                    <option value="">-- Semua Cabang --</option>
                                    <?php 
                                    if ($cabang_list) {
                                        while ($cabang = $cabang_list->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $cabang['cabang_id']; ?>" <?php echo $filter_cabang == $cabang['cabang_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cabang['nama_cabang']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Produk</label>
                                <select name="produk_id">
                                    <option value="">-- Semua Produk --</option>
                                    <?php 
                                    if ($produk_list) {
                                        while ($produk = $produk_list->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $produk['produk_id']; ?>" <?php echo $filter_produk == $produk['produk_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($produk['nama_produk']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Tipe Transaksi</label>
                                <select name="tipe_transaksi">
                                    <option value="">-- Semua Tipe --</option>
                                    <option value="masuk" <?php echo $filter_tipe == 'masuk' ? 'selected' : ''; ?>>Stok Masuk</option>
                                    <option value="keluar" <?php echo $filter_tipe == 'keluar' ? 'selected' : ''; ?>>Stok Keluar</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">🔍 Terapkan Filter</button>
                            <a href="inventory_stock.php" class="btn-reset">🔄 Reset Filter</a>
                        </div>
                    </form>
                </div>
                
                <!-- Inventory History Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>📋 Riwayat Transaksi Inventory</h2>
                        <div style="color: #7f8c8d; font-size: 14px;">
                            Menampilkan <?php echo count($inventory_data); ?> dari <?php echo number_format($total_records); ?> transaksi
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Cabang</th>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Tipe</th>
                                    <th>Jumlah</th>
                                    <th>Stok Cabang Sebelum</th>
                                    <th>Stok Cabang Setelah</th>
                                    <th>Nilai</th>
                                    <th>Referensi</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventory_data)): ?>
                                    <?php foreach ($inventory_data as $item): ?>
                                    <tr>
                                        <td><?php echo $item['inventory_id']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($item['tanggal'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['nama_cabang']); ?></strong>
                                            <?php if ($item['cabang_id'] === null && $item['user_cabang']): ?>
                                                <br><small style="color: #7f8c8d;">User: <?php echo htmlspecialchars($item['user_cabang']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                                        <td><?php echo htmlspecialchars($item['kategori']); ?></td>
                                        <td>
                                            <span class="badge-<?php echo $item['tipe_transaksi']; ?>">
                                                <?php echo $item['tipe_transaksi'] == 'masuk' ? '📥 Masuk' : '📤 Keluar'; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo number_format($item['jumlah']); ?></strong></td>
                                        <td><?php echo number_format($item['stok_sebelum_cabang']); ?></td>
                                        <td><?php echo number_format($item['stok_sesudah_cabang']); ?></td>
                                        <td>Rp <?php echo number_format($item['jumlah'] * $item['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($item['referensi']): ?>
                                                <small style="color: #667eea; font-weight: 500;"><?php echo htmlspecialchars($item['referensi']); ?></small>
                                            <?php else: ?>
                                                <small style="color: #adb5bd;">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($item['user_name']); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                            <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                            <strong>Tidak ada data transaksi</strong><br>
                                            <small>Coba ubah filter atau range tanggal</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Build query string for pagination
                        $query_params = [
                            'start_date' => $filter_start_date,
                            'end_date' => $filter_end_date,
                            'cabang_id' => $filter_cabang,
                            'produk_id' => $filter_produk,
                            'tipe_transaksi' => $filter_tipe
                        ];
                        $query_string = http_build_query(array_filter($query_params));
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo $query_string; ?>&page_num=<?php echo $page - 1; ?>">← Previous</a>
                        <?php else: ?>
                            <span class="disabled">← Previous</span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo $query_string; ?>&page_num=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo $query_string; ?>&page_num=<?php echo $page + 1; ?>">Next →</a>
                        <?php else: ?>
                            <span class="disabled">Next →</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info Box -->
                <div style="background: #e7f3ff; padding: 20px; border-radius: 12px; margin-top: 30px; border-left: 4px solid #3498db;">
                    <h3 style="margin: 0 0 10px 0; color: #2c3e50;">ℹ️ Informasi</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #495057;">
                        <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
                        <li>Data menampilkan semua transaksi inventory dari <strong>semua cabang</strong></li>
                        <?php else: ?>
                        <li>Data menampilkan transaksi inventory dari <strong>cabang Anda</strong></li>
                        <?php endif; ?>
                        <li><strong>Stok Masuk (📥)</strong>: Penambahan stok dari supplier/pembelian</li>
                        <li><strong>Stok Keluar (📤)</strong>: Pengurangan stok dari penjualan</li>
                        <li>Referensi menunjukkan nomor invoice untuk transaksi penjualan</li>
                        <li>Gunakan filter untuk mempersempit pencarian data</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
