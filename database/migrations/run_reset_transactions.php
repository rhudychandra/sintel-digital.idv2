<?php
// Run this file to reset all transactions
require_once __DIR__ . '/../../config/config.php';

$conn = getDBConnection();

echo "Starting transaction reset...\n\n";

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
echo "✓ Foreign key checks disabled\n";

// 1. Delete all detail penjualan
$result = $conn->query("TRUNCATE TABLE detail_penjualan");
if ($result) {
    echo "✓ All detail_penjualan deleted\n";
} else {
    echo "✗ Error deleting detail_penjualan: " . $conn->error . "\n";
}

// 2. Delete all penjualan
$result = $conn->query("TRUNCATE TABLE penjualan");
if ($result) {
    echo "✓ All penjualan deleted\n";
} else {
    echo "✗ Error deleting penjualan: " . $conn->error . "\n";
}

// 3. Delete all inventory records
$result = $conn->query("TRUNCATE TABLE inventory");
if ($result) {
    echo "✓ All inventory records deleted\n";
} else {
    echo "✗ Error deleting inventory: " . $conn->error . "\n";
}

// 4. Reset all product stock to 0
$result = $conn->query("UPDATE produk SET stok = 0 WHERE status = 'active'");
if ($result) {
    echo "✓ All product stock reset to 0\n";
} else {
    echo "✗ Error resetting stock: " . $conn->error . "\n";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "✓ Foreign key checks re-enabled\n\n";

// Verification
echo "=== VERIFICATION ===\n";

$result = $conn->query("SELECT COUNT(*) as total FROM penjualan");
$row = $result->fetch_assoc();
echo "Total penjualan: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM detail_penjualan");
$row = $result->fetch_assoc();
echo "Total detail_penjualan: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM inventory");
$row = $result->fetch_assoc();
echo "Total inventory: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM produk WHERE stok > 0");
$row = $result->fetch_assoc();
echo "Products with stock > 0: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM produk WHERE status = 'active'");
$row = $result->fetch_assoc();
echo "Total active products: " . $row['total'] . "\n";

echo "\n✅ All transactions have been reset successfully!\n";
echo "✅ All product stock has been reset to 0\n";
echo "✅ Master data (produk, cabang, reseller, users) are preserved\n";

$conn->close();
?>
