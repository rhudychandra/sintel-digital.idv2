<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

// Check if user is administrator
if ($user['role'] !== 'administrator') {
    $_SESSION['message'] = "Error: Anda tidak memiliki akses untuk upload reseller!";
    header('Location: reseller.php');
    exit();
}

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error: Gagal mengupload file!";
        header('Location: reseller.php');
        exit();
    }
    
    // Validate file type
    $allowed_extensions = ['csv', 'xlsx', 'xls'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $_SESSION['message'] = "Error: File harus berformat CSV, XLS, atau XLSX!";
        header('Location: reseller.php');
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['message'] = "Error: Ukuran file maksimal 5MB!";
        header('Location: reseller.php');
        exit();
    }
    
    // Process CSV file
    if ($file_extension === 'csv') {
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $row_number = 0;
        
        // Valid categories
        $valid_categories = ['Sales Force', 'General Manager', 'Manager', 'Supervisor', 'Player/Pemain', 'Merchant'];
        
        // Valid status
        $valid_status = ['active', 'inactive'];
        
        // Open and read CSV file
        if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
            // Skip header row
            $header = fgetcsv($handle, 1000, ',');
            
            // Validate header
            $expected_headers = ['kode_reseller', 'nama_reseller', 'nama_perusahaan', 'kategori', 'alamat', 'kota', 'provinsi', 'telepon', 'email', 'contact_person', 'status'];
            $header_lower = array_map('strtolower', array_map('trim', $header));
            
            if ($header_lower !== $expected_headers) {
                $_SESSION['message'] = "Error: Format header CSV tidak sesuai! Header harus: kode_reseller, nama_reseller, nama_perusahaan, kategori, alamat, kota, provinsi, telepon, email, contact_person, status";
                fclose($handle);
                header('Location: reseller.php');
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
                $kode_reseller = trim($data[0]);
                $nama_reseller = trim($data[1]);
                $nama_perusahaan = trim($data[2]);
                $kategori = trim($data[3]);
                $alamat = trim($data[4]);
                $kota = trim($data[5]);
                $provinsi = trim($data[6]);
                $telepon = trim($data[7]);
                $email = trim($data[8]);
                $contact_person = trim($data[9]);
                $status = trim($data[10]);
                
                // Validate required fields
                if (empty($kode_reseller)) {
                    $errors[] = "Baris $row_number: Kode reseller tidak boleh kosong";
                    $error_count++;
                    continue;
                }
                
                if (empty($nama_reseller)) {
                    $errors[] = "Baris $row_number: Nama reseller tidak boleh kosong";
                    $error_count++;
                    continue;
                }
                
                if (empty($kategori)) {
                    $errors[] = "Baris $row_number: Kategori tidak boleh kosong";
                    $error_count++;
                    continue;
                }
                
                // Validate kategori
                if (!in_array($kategori, $valid_categories)) {
                    $errors[] = "Baris $row_number: Kategori '$kategori' tidak valid. Pilihan: Sales Force, General Manager, Manager, Supervisor, Player/Pemain, Merchant";
                    $error_count++;
                    continue;
                }
                
                // Validate status
                if (empty($status)) {
                    $status = 'active'; // Default status
                } elseif (!in_array(strtolower($status), $valid_status)) {
                    $errors[] = "Baris $row_number: Status harus 'active' atau 'inactive'";
                    $error_count++;
                    continue;
                }
                
                // Validate email format if provided
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Baris $row_number: Format email tidak valid";
                    $error_count++;
                    continue;
                }
                
                // Check if kode_reseller already exists
                $check_stmt = $conn->prepare("SELECT reseller_id FROM reseller WHERE kode_reseller = ?");
                $check_stmt->bind_param("s", $kode_reseller);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $errors[] = "Baris $row_number: Kode reseller '$kode_reseller' sudah ada di database";
                    $error_count++;
                    $check_stmt->close();
                    continue;
                }
                $check_stmt->close();
                
                // Insert reseller (cabang_id = NULL, will be set manually later if needed)
                try {
                    $stmt = $conn->prepare("INSERT INTO reseller (kode_reseller, nama_reseller, nama_perusahaan, kategori, alamat, kota, provinsi, telepon, email, contact_person, cabang_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)");
                    $stmt->bind_param("sssssssssss", $kode_reseller, $nama_reseller, $nama_perusahaan, $kategori, $alamat, $kota, $provinsi, $telepon, $email, $contact_person, $status);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $errors[] = "Baris $row_number: Gagal menyimpan reseller - " . $stmt->error;
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
            $message .= "✅ Berhasil: $success_count reseller<br>";
            
            if ($error_count > 0) {
                $message .= "❌ Gagal: $error_count reseller<br><br>";
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
header('Location: reseller.php');
exit();
?>
