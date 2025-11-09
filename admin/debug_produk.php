<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Produk Form Submission</h2>";
echo "<hr>";

echo "<h3>POST Data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Test Logic:</h3>";

if (isset($_POST['kategori'])) {
    $kategori = $_POST['kategori'];
    echo "Kategori from POST: " . htmlspecialchars($kategori) . "<br>";
    
    if ($kategori === 'lainnya') {
        echo "Kategori is 'lainnya'<br>";
        
        if (!empty($_POST['kategori_lain'])) {
            echo "kategori_lain is NOT empty: " . htmlspecialchars($_POST['kategori_lain']) . "<br>";
            $kategori = $_POST['kategori_lain'];
            echo "Final kategori: " . htmlspecialchars($kategori) . "<br>";
        } else {
            echo "kategori_lain is EMPTY<br>";
        }
    } else {
        echo "Kategori is standard: " . htmlspecialchars($kategori) . "<br>";
    }
} else {
    echo "No kategori in POST<br>";
}

echo "<hr>";
echo "<h3>Database Connection Test:</h3>";

require_once '../config.php';

try {
    $conn = getDBConnection();
    echo "✅ Database connection successful<br>";
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as total FROM produk");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Query successful. Total produk: " . $row['total'] . "<br>";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='produk.php'>← Back to Produk</a></p>";
?>
