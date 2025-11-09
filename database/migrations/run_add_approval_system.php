<?php
require_once __DIR__ . '/../../config/config.php';

$conn = getDBConnection();

echo "Starting approval system migration...\n\n";

// 1. Add status_approval column
$sql = "ALTER TABLE inventory 
ADD COLUMN status_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER keterangan";

if ($conn->query($sql)) {
    echo "✓ Column status_approval added successfully\n";
} else {
    echo "✗ Error adding column: " . $conn->error . "\n";
}

// 2. Update existing records to 'approved'
$sql = "UPDATE inventory SET status_approval = 'approved' WHERE status_approval IS NULL OR status_approval = 'pending'";

if ($conn->query($sql)) {
    echo "✓ Existing records updated to 'approved'\n";
} else {
    echo "✗ Error updating records: " . $conn->error . "\n";
}

// 3. Add index
$sql = "ALTER TABLE inventory ADD INDEX idx_status_approval (status_approval)";

if ($conn->query($sql)) {
    echo "✓ Index added successfully\n";
} else {
    // Index might already exist, ignore error
    echo "Note: " . $conn->error . "\n";
}

echo "\n=== VERIFICATION ===\n";

$result = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE status_approval = 'pending'");
$row = $result->fetch_assoc();
echo "Total pending: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE status_approval = 'approved'");
$row = $result->fetch_assoc();
echo "Total approved: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE status_approval = 'rejected'");
$row = $result->fetch_assoc();
echo "Total rejected: " . $row['total'] . "\n";

echo "\n✅ Approval system migration completed!\n";

$conn->close();
?>
