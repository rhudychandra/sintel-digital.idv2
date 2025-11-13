<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

if ($user['role'] !== 'administrator') {
    $_SESSION['message'] = "Error: Anda tidak memiliki akses untuk upload outlet!";
    header('Location: outlet.php');
    exit();
}

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error: Gagal mengupload file!";
        header('Location: outlet.php');
        exit();
    }
    
    // Validate file type
    $allowed_extensions = ['csv'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $_SESSION['message'] = "Error: File harus berformat CSV!";
        header('Location: outlet.php');
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['message'] = "Error: Ukuran file maksimal 5MB!";
        header('Location: outlet.php');
        exit();
    }
    
    // Process CSV file
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    $row_number = 0;
    
    // Valid status and jenis
    $valid_status = ['PJP', 'Non PJP'];
    $valid_jenis = ['Retail', 'Pareto', 'RS Eksekusi Voucher', 'RS Eksekusi SA'];
    
    // Open and read CSV file
    if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
        // Skip header row
        $header = fgetcsv($handle, 2000, ',');
        
        // Validate header
        $expected_headers = ['nama_outlet', 'nomor_rs', 'id_digipos', 'nik_ktp', 'kelurahan_desa', 'kecamatan', 'city', 'nama_pemilik', 'nomor_hp_pemilik', 'type_outlet', 'jadwal_kategori', 'hari', 'sales_force_id', 'cabang_id', 'status_outlet', 'jenis_rs'];
        $header_lower = array_map('strtolower', array_map('trim', $header));
        
        if ($header_lower !== $expected_headers) {
            $_SESSION['message'] = "Error: Format header CSV tidak sesuai! Download template untuk melihat format yang benar.";
            fclose($handle);
            header('Location: outlet.php');
            exit();
        }
        
        // Process each row
        while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Extract data
            $nama_outlet = trim($data[0]);
            $nomor_rs = trim($data[1]);
            $id_digipos = trim($data[2]);
            $nik_ktp = trim($data[3]);
            $kelurahan_desa = trim($data[4]);
            $kecamatan = trim($data[5]);
            $city = trim($data[6]);
            $nama_pemilik = trim($data[7]);
            $nomor_hp_pemilik = trim($data[8]);
            $type_outlet = trim($data[9]);
            $jadwal_kategori = trim($data[10]);
            $hari = trim($data[11]);
            $sales_force_id = trim($data[12]);
            $cabang_id = trim($data[13]);
            $status_outlet = trim($data[14]);
            $jenis_rs = trim($data[15]);
            
            // Validate required fields
            if (empty($nama_outlet)) {
                $errors[] = "Baris $row_number: Nama outlet tidak boleh kosong";
                $error_count++;
                continue;
            }
            
            if (empty($nomor_rs)) {
                $errors[] = "Baris $row_number: Nomor RS tidak boleh kosong";
                $error_count++;
                continue;
            }
            
            if (empty($city)) {
                $errors[] = "Baris $row_number: Kota tidak boleh kosong";
                $error_count++;
                continue;
            }
            
            if (empty($nama_pemilik)) {
                $errors[] = "Baris $row_number: Nama pemilik tidak boleh kosong";
                $error_count++;
                continue;
            }
            
            if (empty($nomor_hp_pemilik)) {
                $errors[] = "Baris $row_number: Nomor HP pemilik tidak boleh kosong";
                $error_count++;
                continue;
            }
            
            // Validate status_outlet
            if (empty($status_outlet)) {
                $status_outlet = 'Non PJP'; // Default
            } elseif (!in_array($status_outlet, $valid_status)) {
                $errors[] = "Baris $row_number: Status outlet harus 'PJP' atau 'Non PJP'";
                $error_count++;
                continue;
            }
            
            // Validate jenis_rs
            if (empty($jenis_rs)) {
                $jenis_rs = 'Retail'; // Default
            } elseif (!in_array($jenis_rs, $valid_jenis)) {
                $errors[] = "Baris $row_number: Jenis RS tidak valid. Pilihan: Retail, Pareto, RS Eksekusi Voucher, RS Eksekusi SA";
                $error_count++;
                continue;
            }
            
            // Check if nomor_rs already exists
            $check_stmt = $conn->prepare("SELECT outlet_id FROM outlet WHERE nomor_rs = ?");
            $check_stmt->bind_param("s", $nomor_rs);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors[] = "Baris $row_number: Nomor RS '$nomor_rs' sudah ada di database";
                $error_count++;
                $check_stmt->close();
                continue;
            }
            $check_stmt->close();
            
            // Convert empty strings to NULL for nullable fields
            $sales_force_id = !empty($sales_force_id) && is_numeric($sales_force_id) ? intval($sales_force_id) : null;
            $cabang_id = !empty($cabang_id) && is_numeric($cabang_id) ? intval($cabang_id) : null;
            $nik_ktp = !empty($nik_ktp) ? $nik_ktp : null;
            
            // Insert outlet
            try {
                $stmt = $conn->prepare("INSERT INTO outlet (nama_outlet, nomor_rs, id_digipos, nik_ktp, kelurahan_desa, kecamatan, city, nama_pemilik, nomor_hp_pemilik, type_outlet, jadwal_kategori, hari, sales_force_id, cabang_id, status_outlet, jenis_rs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssssiiss", $nama_outlet, $nomor_rs, $id_digipos, $nik_ktp, $kelurahan_desa, $kecamatan, $city, $nama_pemilik, $nomor_hp_pemilik, $type_outlet, $jadwal_kategori, $hari, $sales_force_id, $cabang_id, $status_outlet, $jenis_rs);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $errors[] = "Baris $row_number: Gagal menyimpan outlet - " . $stmt->error;
                    $error_count++;
                }
                $stmt->close();
            } catch (Exception $e) {
                $errors[] = "Baris $row_number: Error - " . $e->getMessage();
                $error_count++;
            }
        }
        
        fclose($handle);
        
        // Generate result message
        $message = "Upload selesai! $success_count outlet berhasil diimport";
        
        if ($error_count > 0) {
            $message .= ", $error_count gagal.";
            if (!empty($errors)) {
                $message .= "<br><br><strong>Detail error:</strong><br>" . implode("<br>", array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $message .= "<br>... dan " . (count($errors) - 10) . " error lainnya";
                }
            }
        }
        
        $_SESSION['message'] = $message;
        $conn->close();
        header('Location: outlet.php');
        exit();
        
    } else {
        $_SESSION['message'] = "Error: Gagal membuka file CSV!";
        $conn->close();
        header('Location: outlet.php');
        exit();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Outlet Excel - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Administrator</h2>
                <p><?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="outlet.php" class="nav-item active">
                    <span class="nav-icon">🏪</span>
                    <span>Outlet</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="outlet.php" class="btn-back">← Kembali ke Outlet</a>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Upload Outlet dari Excel</h1>
                <div class="header-info">
                    <span class="date"><?php echo date('d F Y'); ?></span>
                </div>
            </header>
            
            <div class="admin-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Upload File CSV</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Panduan Upload:</strong>
                                <ol style="margin: 10px 0 0 20px;">
                                    <li>Download template CSV di bawah</li>
                                    <li>Isi data outlet sesuai format</li>
                                    <li>Simpan sebagai CSV (UTF-8)</li>
                                    <li>Upload file di form ini</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div style="margin: 20px 0;">
                            <a href="template_outlet.csv" class="btn-success" download>
                                <i class="fas fa-download"></i> Download Template CSV
                            </a>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="admin-form">
                            <div class="form-group">
                                <label for="excel_file">Pilih File CSV *</label>
                                <input type="file" id="excel_file" name="excel_file" accept=".csv" required>
                                <small style="color: #7f8c8d;">Format: CSV (max 5MB)</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-upload"></i> Upload & Import
                                </button>
                                <a href="outlet.php" class="btn-cancel">Batal</a>
                            </div>
                        </form>
                        
                        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="margin-bottom: 15px;">Format Kolom CSV:</h4>
                            <table class="data-table" style="font-size: 11px;">
                                <thead>
                                    <tr>
                                        <th>Kolom</th>
                                        <th>Deskripsi</th>
                                        <th>Wajib</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>nama_outlet</td><td>Nama toko/outlet</td><td>Ya</td></tr>
                                    <tr><td>nomor_rs</td><td>Nomor registrasi (unique)</td><td>Ya</td></tr>
                                    <tr><td>id_digipos</td><td>ID dari sistem Digipos</td><td>Tidak</td></tr>
                                    <tr><td>nik_ktp</td><td>NIK KTP pemilik</td><td>Tidak</td></tr>
                                    <tr><td>kelurahan_desa</td><td>Kelurahan/Desa</td><td>Tidak</td></tr>
                                    <tr><td>kecamatan</td><td>Kecamatan</td><td>Tidak</td></tr>
                                    <tr><td>city</td><td>Kota/Kabupaten</td><td>Ya</td></tr>
                                    <tr><td>nama_pemilik</td><td>Nama pemilik outlet</td><td>Ya</td></tr>
                                    <tr><td>nomor_hp_pemilik</td><td>Nomor HP pemilik</td><td>Ya</td></tr>
                                    <tr><td>type_outlet</td><td>Tipe outlet (Retail, Grosir, dll)</td><td>Tidak</td></tr>
                                    <tr><td>jadwal_kategori</td><td>Kategori jadwal kunjungan</td><td>Tidak</td></tr>
                                    <tr><td>hari</td><td>Hari kunjungan</td><td>Tidak</td></tr>
                                    <tr><td>sales_force_id</td><td>ID sales force (dari tabel reseller)</td><td>Tidak</td></tr>
                                    <tr><td>cabang_id</td><td>ID cabang</td><td>Tidak</td></tr>
                                    <tr><td>status_outlet</td><td>PJP atau Non PJP</td><td>Tidak (default: Non PJP)</td></tr>
                                    <tr><td>jenis_rs</td><td>Retail / Pareto / RS Eksekusi Voucher / RS Eksekusi SA</td><td>Tidak (default: Retail)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/force-lexend.js"></script>
</body>
</html>
