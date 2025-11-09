<?php
require_once '../config.php';

$conn = getDBConnection();

echo "<h2>Struktur Tabel Produk</h2>";
$result = $conn->query('DESCRIBE produk');
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>Test Insert</h2>";

// Test insert
$kode_produk = 'TEST-' . time();
$nama_produk = 'Test Product';
$kategori = 'test_category';
$harga = 10000;
$deskripsi = 'Test description';
$cabang_id = 1;

echo "<p>Trying to insert:</p>";
echo "<ul>";
echo "<li>kode_produk: $kode_produk</li>";
echo "<li>nama_produk: $nama_produk</li>";
echo "<li>kategori: $kategori</li>";
echo "<li>harga: $harga</li>";
echo "<li>deskripsi: $deskripsi</li>";
echo "<li>cabang_id: $cabang_id</li>";
echo "</ul>";

$stmt = $conn->prepare("INSERT INTO produk (kode_produk, nama_produk, kategori, harga, deskripsi, cabang_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssdsi", $kode_produk, $nama_produk, $kategori, $harga, $deskripsi, $cabang_id);

if ($stmt->execute()) {
    $insert_id = $stmt->insert_id;
    echo "<p style='color: green;'>✅ Insert successful! ID: $insert_id</p>";
    
    // Check what was actually inserted
    $check = $conn->query("SELECT * FROM produk WHERE produk_id = $insert_id");
    $row = $check->fetch_assoc();
    
    echo "<h3>Data yang tersimpan:</h3>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
    
    // Delete test data
    $conn->query("DELETE FROM produk WHERE produk_id = $insert_id");
    echo "<p>Test data deleted.</p>";
} else {
    echo "<p style='color: red;'>❌ Insert failed: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
