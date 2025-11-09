<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Script started -->\n";

try {
    require_once 'config.php';
    echo "<!-- Debug: Config loaded -->\n";
    
    requireLogin();
    echo "<!-- Debug: Login checked -->\n";
    
    $user = getCurrentUser();
    echo "<!-- Debug: User loaded -->\n";
    
    $conn = getDBConnection();
    echo "<!-- Debug: DB connected -->\n";
    
    // Get current page/menu
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    echo "<!-- Debug: Page = $page -->\n";
    
    // Get products for dropdown
    $products = $conn->query("SELECT produk_id, kode_produk, nama_produk, harga, stok FROM produk WHERE status = 'active' ORDER BY nama_produk");
    echo "<!-- Debug: Products loaded: " . ($products ? $products->num_rows : 0) . " rows -->\n";
    
    // Get resellers for dropdown
    $resellers = $conn->query("SELECT reseller_id, kode_reseller, nama_reseller FROM reseller WHERE status = 'active' ORDER BY nama_reseller");
    echo "<!-- Debug: Resellers loaded: " . ($resellers ? $resellers->num_rows : 0) . " rows -->\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Sinar Telkom Dashboard System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .header h1 { color: #667eea; }
        .menu { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .menu a { display: inline-block; padding: 10px 20px; margin-right: 10px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; }
        .menu a:hover { background: #5568d3; }
        .content { background: white; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Inventory System</h1>
            <p>User: <?php echo htmlspecialchars($user['full_name']); ?></p>
            <a href="dashboard.php" style="color: #667eea;">← Back to Dashboard</a>
        </div>
        
        <div class="menu">
            <a href="?page=dashboard">📊 Dashboard</a>
            <a href="?page=input_barang">📥 Input Barang</a>
            <a href="?page=input_penjualan">💰 Input Penjualan</a>
        </div>
        
        <div class="content">
            <?php if ($page === 'dashboard'): ?>
                <h2>Dashboard Inventory</h2>
                <p>Dashboard content will be here...</p>
                <p>Products: <?php echo $products ? $products->num_rows : 0; ?></p>
                <p>Resellers: <?php echo $resellers ? $resellers->num_rows : 0; ?></p>
                
            <?php elseif ($page === 'input_barang'): ?>
                <h2>Input Barang</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="input_barang">
                    <p><label>Tanggal: <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required></label></p>
                    <p><label>Produk: 
                        <select name="produk_id" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php 
                            if ($products) {
                                $products->data_seek(0);
                                while ($p = $products->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $p['produk_id']; ?>">
                                    <?php echo htmlspecialchars($p['nama_produk']); ?> (Stok: <?php echo $p['stok']; ?>)
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </label></p>
                    <p><label>Quantity: <input type="number" name="qty" min="1" required></label></p>
                    <p><label>Keterangan: <textarea name="keterangan"></textarea></label></p>
                    <p><button type="submit">Simpan</button></p>
                </form>
                
            <?php elseif ($page === 'input_penjualan'): ?>
                <h2>Input Penjualan</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="input_penjualan">
                    <p><label>Tanggal: <input type="date" name="tanggal_penjualan" value="<?php echo date('Y-m-d'); ?>" required></label></p>
                    <p><label>Reseller: 
                        <select name="reseller_id" required>
                            <option value="">-- Pilih Reseller --</option>
                            <?php 
                            if ($resellers) {
                                $resellers->data_seek(0);
                                while ($r = $resellers->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $r['reseller_id']; ?>">
                                    <?php echo htmlspecialchars($r['nama_reseller']); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </label></p>
                    <p><label>Produk: 
                        <select name="produk_id_penjualan" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php 
                            if ($products) {
                                $products->data_seek(0);
                                while ($p = $products->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $p['produk_id']; ?>">
                                    <?php echo htmlspecialchars($p['nama_produk']); ?> - Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </label></p>
                    <p><label>Quantity: <input type="number" name="qty_penjualan" min="1" required></label></p>
                    <p><button type="submit">Proses Penjualan</button></p>
                </form>
                
            <?php endif; ?>
        </div>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
