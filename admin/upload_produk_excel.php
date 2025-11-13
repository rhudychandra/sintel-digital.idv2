<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

// Check if user is administrator
if ($user['role'] !== 'administrator') {
    $_SESSION['message'] = "Error: Anda tidak memiliki akses untuk upload produk!";
    header('Location: produk.php');
    exit();
}

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error: Gagal mengupload file!";
        header('Location: produk.php');
        exit();
    }
    
    // Validate file type
    $allowed_extensions = ['csv', 'xlsx', 'xls'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $_SESSION['message'] = "Error: File harus berformat CSV, XLS, atau XLSX!";
        header('Location: produk.php');
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['message'] = "Error: Ukuran file maksimal 5MB!";
        header('Location: produk.php');
        exit();
    }
    
    // Process CSV file
    if ($file_extension === 'csv') {
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $row_number = 0;
        
        // Get all valid categories from database
        $valid_categories = [];
        $cat_result = $conn->query("SELECT nama_kategori FROM kategori_produk WHERE status='active'");
        while ($cat_row = $cat_result->fetch_assoc()) {
            $valid_categories[] = strtolower(trim($cat_row['nama_kategori']));
        }
        
        // Open and read CSV file
        if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
            // Skip header row
            $header = fgetcsv($handle, 1000, ',');
            
            // Validate header
            $expected_headers = ['kode_produk', 'nama_produk', 'kategori', 'harga', 'deskripsi'];
            $header_lower = array_map('strtolower', array_map('trim', $header));
            
            if ($header_lower !== $expected_headers) {
                $_SESSION['message'] = "Error: Format header CSV tidak sesuai! Header harus: kode_produk, nama_produk, kategori, harga, deskripsi";
                fclose($handle);
                header('Location: produk.php');
                exit();
            }
            
            // Process each row
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $row_number++;
                
                // Skip empty rows
                if (empty(array_filter($data))) {
                    continue;
                }
                
                // Extract data
                $kode_produk = trim($data[0]);
                $nama_produk = trim($data[1]);
                $kategori = trim($data[2]);
                $harga = trim($data[3]);
                $deskripsi = trim($data[4]);
                
                // Validate required fields
                if (empty($nama_produk)) {
                    $errors[] = "Baris $row_number: Nama produk tidak boleh kosong";
                    $error_count++;
                    continue;
                }
                
                if (empty($kategori)) {
                    $errors[] = "Baris $row_number: Kategori tidak boleh kosong";
                    $error_count++;
                    continue;
                }
                
                if (empty($harga)) {
                    $errors[] = "Baris $row_number: Harga tidak boleh kosong";
                    $error_count++;
                    continue;
                }
                
                // Validate kategori exists in database
                if (!in_array(strtolower($kategori), $valid_categories)) {
                    $errors[] = "Baris $row_number: Kategori '$kategori' tidak valid. Kategori harus sudah terdaftar di sistem.";
                    $error_count++;
                    continue;
                }
                
                // Validate harga is numeric
                if (!is_numeric($harga) || $harga <= 0) {
                    $errors[] = "Baris $row_number: Harga harus berupa angka positif";
                    $error_count++;
                    continue;
                }
                
                // Auto-generate kode_produk if empty
                if (empty($kode_produk)) {
                    $kode_produk = 'PRD-' . time() . '-' . rand(100, 999);
                }
                
                // Check if kode_produk already exists
                $check_stmt = $conn->prepare("SELECT produk_id FROM produk WHERE kode_produk = ?");
                $check_stmt->bind_param("s", $kode_produk);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $errors[] = "Baris $row_number: Kode produk '$kode_produk' sudah ada di database";
                    $error_count++;
                    $check_stmt->close();
                    continue;
                }
                $check_stmt->close();
                
                // Get HPP from CSV (column 3) or default to 80% of harga
                $hpp = isset($data[3]) && is_numeric($data[3]) ? floatval($data[3]) : ($harga * 0.80);
                
                // Calculate profit margin
                $profit_margin = ($hpp > 0) ? (($harga - $hpp) / $hpp) * 100 : 0;
                
                // Insert product
                try {
                    $stmt = $conn->prepare("INSERT INTO produk (kode_produk, nama_produk, kategori, hpp, harga, profit_margin, deskripsi, cabang_id) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");
                    $stmt->bind_param("sssddds", $kode_produk, $nama_produk, $kategori, $hpp, $harga, $profit_margin, $deskripsi);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $errors[] = "Baris $row_number: Gagal menyimpan produk - " . $stmt->error;
                        $error_count++;
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $errors[] = "Baris $row_number: Error - " . $e->getMessage();
                    $error_count++;
                }
            }
            
            fclose($handle);
            
            // Prepare result message
            $message = "<strong>Upload Selesai!</strong><br>";
            $message .= "✅ Berhasil: $success_count produk<br>";
            
            if ($error_count > 0) {
                $message .= "❌ Gagal: $error_count produk<br><br>";
                $message .= "<strong>Detail Error:</strong><br>";
                $message .= "<ul style='margin: 10px 0; padding-left: 20px;'>";
                foreach (array_slice($errors, 0, 10) as $error) {
                    $message .= "<li>" . htmlspecialchars($error) . "</li>";
                }
                if (count($errors) > 10) {
                    $message .= "<li>... dan " . (count($errors) - 10) . " error lainnya</li>";
                }
                $message .= "</ul>";
            }
            
            $_SESSION['message'] = $message;
            
        } else {
            $_SESSION['message'] = "Error: Gagal membaca file CSV!";
        }
        
    } else {
        // For Excel files (.xlsx, .xls), we need PHPSpreadsheet library
        // For now, show message to convert to CSV
        $_SESSION['message'] = "Info: Saat ini hanya mendukung format CSV. Silakan convert file Excel Anda ke CSV terlebih dahulu.";
    }
    
} else {
    $_SESSION['message'] = "Error: File tidak ditemukan!";
}

$conn->close();
header('Location: produk.php');
exit();
?>
