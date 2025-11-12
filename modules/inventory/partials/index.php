<?php
// Direct entry point for input_barang partial
require_once '../../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Get necessary data for input_barang partial
$products = $conn->query("SELECT produk_id, kode_produk, nama_produk, kategori, harga, stok FROM produk WHERE status = 'active' ORDER BY nama_produk");

$cabang_list = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");

// Get input barang history data
$input_barang_start_date = isset($_GET['input_start']) ? $_GET['input_start'] : date('Y-m-d', strtotime('-7 days'));
$input_barang_end_date = isset($_GET['input_end']) ? $_GET['input_end'] : date('Y-m-d');

$input_barang_data = null;

if (in_array($user['role'], ['administrator', 'manager'])) {
    $input_query = "
        SELECT 
            i.inventory_id,
            i.tanggal,
            i.jumlah,
            i.referensi,
            i.keterangan,
            i.status_approval,
            i.created_at,
            p.nama_produk,
            p.kategori,
            p.harga,
            c.nama_cabang,
            u.full_name as user_name
        FROM inventory i
        JOIN produk p ON i.produk_id = p.produk_id
        LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
        LEFT JOIN users u ON i.user_id = u.user_id
        WHERE i.tipe_transaksi = 'masuk'
        AND i.tanggal BETWEEN ? AND ?
        ORDER BY i.tanggal DESC, i.inventory_id DESC
    ";
    
    $stmt = $conn->prepare($input_query);
    $stmt->bind_param("ss", $input_barang_start_date, $input_barang_end_date);
} else {
    $input_query = "
        SELECT 
            i.inventory_id,
            i.tanggal,
            i.jumlah,
            i.referensi,
            i.keterangan,
            i.status_approval,
            i.created_at,
            p.nama_produk,
            p.kategori,
            p.harga,
            c.nama_cabang,
            u.full_name as user_name
        FROM inventory i
        JOIN produk p ON i.produk_id = p.produk_id
        LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
        LEFT JOIN users u ON i.user_id = u.user_id
        WHERE i.tipe_transaksi = 'masuk'
        AND i.tanggal BETWEEN ? AND ?
        AND i.cabang_id = ?
        ORDER BY i.tanggal DESC, i.inventory_id DESC
    ";
    
    $stmt = $conn->prepare($input_query);
    $stmt->bind_param("ssi", $input_barang_start_date, $input_barang_end_date, $user['cabang_id']);
}

$stmt->execute();
$input_barang_data = $stmt->get_result();

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'input_barang') {
    $tanggal = $_POST['tanggal'];
    $produk_id = $_POST['produk_id'];
    $qty = $_POST['qty'];
    $alasan_masuk = $_POST['alasan_masuk'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    
    if (in_array($user['role'], ['admin', 'staff', 'supervisor', 'finance'])) {
        $cabang_id = $user['cabang_id'];
    } else {
        $cabang_id = $_POST['cabang_id'] ?? null;
    }
    
    $stmt = $conn->prepare("SELECT nama_produk, stok FROM produk WHERE produk_id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();
    $stok_sebelum = $produk['stok'];
    $stok_sesudah = $stok_sebelum + $qty;
    
    $referensi = 'MASUK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $full_keterangan = "Alasan: " . $alasan_masuk;
    if (!empty($keterangan)) {
        $full_keterangan .= " | " . $keterangan;
    }
    
    if ($cabang_id) {
        $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id, cabang_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->bind_param("isiissii", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id'], $cabang_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, status_approval, user_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("isiisssi", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $referensi, $full_keterangan, $user['user_id']);
    }
    
    if ($stmt->execute()) {
        $message = "Input barang berhasil! Ref: " . $referensi . " | Produk: " . $produk['nama_produk'] . " | Qty: " . $qty . " | Status: PENDING (Menunggu Approval)";
        // Refresh data
        if (in_array($user['role'], ['administrator', 'manager'])) {
            $stmt = $conn->prepare("SELECT * FROM inventory WHERE tipe_transaksi = 'masuk' AND tanggal BETWEEN ? AND ? ORDER BY tanggal DESC, inventory_id DESC");
            $stmt->bind_param("ss", $input_barang_start_date, $input_barang_end_date);
        } else {
            $stmt = $conn->prepare("SELECT * FROM inventory WHERE tipe_transaksi = 'masuk' AND tanggal BETWEEN ? AND ? AND cabang_id = ? ORDER BY tanggal DESC, inventory_id DESC");
            $stmt->bind_param("ssi", $input_barang_start_date, $input_barang_end_date, $user['cabang_id']);
        }
        $stmt->execute();
        $input_barang_data = $stmt->get_result();
    } else {
        $error = "Gagal input barang: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Barang - Inventory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../../assets/css/admin-styles.css?v=<?php echo time(); ?>">
</head>
<body class="admin-page">
    <div class="admin-container">
        <aside class="admin-sidebar" style="width: 340px;">
            <div class="sidebar-header">
                <div style="display: flex; flex-direction: column; gap: 15px; align-items: stretch;">
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div style="flex-shrink: 0;">
                            <img src="../../../assets/images/logo_icon.png" alt="Logo" style="width: 60px; height: 60px; border-radius: 10px; object-fit: contain; background: rgba(255,255,255,0.1); padding: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        </div>
                        <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: white; letter-spacing: 0.5px;">INPUT BARANG</h2>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.15) 100%); padding: 12px 14px; border-radius: 10px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                        <p style="margin: 0 0 4px 0; font-weight: 600; font-size: 13px; color: white; line-height: 1.4; word-wrap: break-word; word-break: break-word;"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p style="margin: 0; font-size: 11px; color: rgba(255,255,255,0.85); font-weight: 400; text-transform: capitalize; line-height: 1.3;"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="admin-main" style="margin-left: 340px;">
            <div class="admin-header">
                <h1>📥 Input Barang</h1>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <a href="<?php echo BASE_PATH; ?>/modules/inventory/inventory.php?page=dashboard" class="btn-add" style="text-decoration: none;">← Kembali ke Inventory</a>
                </div>
            </div>

            <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php include __DIR__ . '/input_barang.php'; ?>
        </main>
    </div>
</body>
</html>
