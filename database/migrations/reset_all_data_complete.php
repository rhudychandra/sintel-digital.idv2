<?php
require_once __DIR__ . '/../../config/config.php';

$conn = getDBConnection();

echo "Starting complete data reset...\n\n";

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
echo "✓ Foreign key checks disabled\n";

// 1. Delete all detail_penjualan
$result = $conn->query("DELETE FROM detail_penjualan");
echo "✓ All detail_penjualan deleted\n";

// 2. Delete all penjualan
$result = $conn->query("DELETE FROM penjualan");
echo "✓ All penjualan deleted\n";

// 3. Delete all inventory records
$result = $conn->query("DELETE FROM inventory");
echo "✓ All inventory records deleted\n";

// 4. Reset all product stock to 0
$result = $conn->query("UPDATE produk SET stok = 0");
echo "✓ All product stock reset to 0\n";

// 5. Delete all pelanggan (optional, if you want to reset customers too)
$result = $conn->query("DELETE FROM pelanggan");
echo "✓ All pelanggan deleted\n";

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "✓ Foreign key checks re-enabled\n";

echo "\n=== VERIFICATION ===\n";

$result = $conn->query("SELECT COUNT(*) as total FROM penjualan");
$row = $result->fetch_assoc();
echo "Total penjualan: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM detail_penjualan");
$row = $result->fetch_assoc();
echo "Total detail_penjualan: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM inventory");
$row = $result->fetch_assoc();
echo "Total inventory: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM pelanggan");
$row = $result->fetch_assoc();
echo "Total pelanggan: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM produk WHERE stok > 0");
$row = $result->fetch_assoc();
echo "Products with stock > 0: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM produk WHERE status = 'active'");
$row = $result->fetch_assoc();
echo "Total active products: " . $row['total'] . "\n";

echo "\n✅ Complete data reset successful!\n";
echo "✅ All transactions deleted\n";
echo "✅ All product stock reset to 0\n";
echo "✅ All pelanggan deleted\n";
echo "✅ Master data (produk, cabang, reseller, users) preserved\n";
echo "\n🚀 System ready to start from 0!\n";

$conn->close();
?>
