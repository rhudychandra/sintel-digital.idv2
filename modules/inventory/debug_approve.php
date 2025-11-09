<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

echo "<h1>Debug Approval System</h1>";
echo "<hr>";

// Get sample inventory
$inventory_id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($inventory_id > 0) {
    echo "<h2>Testing Inventory ID: $inventory_id</h2>";
    
    // Get inventory details
    $stmt = $conn->prepare("SELECT i.*, p.nama_produk, p.cabang_id as produk_cabang_id FROM inventory i 
                           LEFT JOIN produk p ON i.produk_id = p.produk_id 
                           WHERE i.inventory_id = ?");
    $stmt->bind_param("i", $inventory_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $inventory = $result->fetch_assoc();
        
        echo "<h3>Inventory Details:</h3>";
        echo "<pre>";
        print_r($inventory);
        echo "</pre>";
        
        echo "<h3>Find Produk at Cabang Tujuan:</h3>";
        echo "Cabang Tujuan ID: " . $inventory['cabang_id'] . "<br>";
        echo "Nama Produk: " . $inventory['nama_produk'] . "<br>";
        
        $stmt = $conn->prepare("SELECT produk_id, nama_produk, cabang_id, stok FROM produk WHERE cabang_id = ? AND nama_produk = ? LIMIT 1");
        $stmt->bind_param("is", $inventory['cabang_id'], $inventory['nama_produk']);
        $stmt->execute();
        $result_dest = $stmt->get_result();
        
        if ($result_dest->num_rows > 0) {
            echo "✅ Produk found at cabang tujuan:<br>";
            $produk_dest = $result_dest->fetch_assoc();
            echo "<pre>";
            print_r($produk_dest);
            echo "</pre>";
        } else {
            echo "❌ Produk NOT found at cabang tujuan!<br>";
            
            echo "<h4>All products with same name:</h4>";
            $stmt = $conn->prepare("SELECT produk_id, nama_produk, cabang_id, stok FROM produk WHERE nama_produk = ?");
            $stmt->bind_param("s", $inventory['nama_produk']);
            $stmt->execute();
            $result_all = $stmt->get_result();
            
            while ($p = $result_all->fetch_assoc()) {
                echo "<pre>";
                print_r($p);
                echo "</pre>";
            }
        }
        
        // Check for related stock keluar
        if (preg_match('/Ref: ([A-Z0-9-]+)/', $inventory['keterangan'], $matches)) {
            $related_ref = $matches[1];
            echo "<h3>Related Stock Keluar (Ref: $related_ref):</h3>";
            
            $stmt = $conn->prepare("SELECT i.*, p.nama_produk, p.cabang_id as produk_cabang_id FROM inventory i
                                   LEFT JOIN produk p ON i.produk_id = p.produk_id
                                   WHERE i.tipe_transaksi = 'keluar' 
                                   AND i.referensi = ? 
                                   LIMIT 1");
            $stmt->bind_param("s", $related_ref);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stock_keluar = $result->fetch_assoc();
                echo "✅ Stock keluar found:<br>";
                echo "<pre>";
                print_r($stock_keluar);
                echo "</pre>";
                
                echo "<h4>Find Produk at Cabang Asal:</h4>";
                echo "Cabang Asal ID: " . $stock_keluar['cabang_id'] . "<br>";
                
                $stmt = $conn->prepare("SELECT produk_id, nama_produk, cabang_id, stok FROM produk WHERE cabang_id = ? AND nama_produk = ? LIMIT 1");
                $stmt->bind_param("is", $stock_keluar['cabang_id'], $stock_keluar['nama_produk']);
                $stmt->execute();
                $result_source = $stmt->get_result();
                
                if ($result_source->num_rows > 0) {
                    echo "✅ Produk found at cabang asal:<br>";
                    $produk_source = $result_source->fetch_assoc();
                    echo "<pre>";
                    print_r($produk_source);
                    echo "</pre>";
                } else {
                    echo "❌ Produk NOT found at cabang asal!<br>";
                }
            } else {
                echo "❌ Stock keluar NOT found!<br>";
            }
        }
    } else {
        echo "❌ Inventory not found!<br>";
    }
} else {
    echo "<h2>List Pending Stock Masuk:</h2>";
    $result = $conn->query("SELECT inventory_id, tanggal, referensi, keterangan FROM inventory WHERE tipe_transaksi = 'masuk' AND status_approval = 'pending' ORDER BY inventory_id DESC LIMIT 10");
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<a href='?id=" . $row['inventory_id'] . "'>ID: " . $row['inventory_id'] . " | " . $row['tanggal'] . " | " . $row['referensi'] . "</a><br>";
        }
    } else {
        echo "No pending stock masuk<br>";
    }
}

$conn->close();
?>
