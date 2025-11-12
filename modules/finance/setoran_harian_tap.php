<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
$page_title = "Setoran Harian TAP - Sinar Telkom Dashboard System";
$conn = getDBConnection();

// Resolve user's cabang name (auto-fill for evidence form)
$user_cabang_nama = '';
if (!empty($user['cabang_id'])) {
    $stmtCab = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
    $stmtCab->bind_param("i", $user['cabang_id']);
    if ($stmtCab->execute()) {
        $resCab = $stmtCab->get_result();
        if ($rowCab = $resCab->fetch_assoc()) {
            $user_cabang_nama = $rowCab['nama_cabang'];
        }
    }
    $stmtCab->close();
}

$canSelectCabang = hasAdminAccess($user['role'] ?? null);

// Detect setoran_evidence optional columns
$evidence_columns = [];
try {
    if ($resDescribe = $conn->query("DESCRIBE setoran_evidence")) {
        while ($col = $resDescribe->fetch_assoc()) {
            $evidence_columns[] = $col['Field'];
        }
        $resDescribe->close();
    }
} catch (Throwable $t) {
    // table might not exist yet
}
$evidence_has_nominal = in_array('nominal', $evidence_columns, true);
$evidence_has_bank_pengirim = in_array('bank_pengirim', $evidence_columns, true);

// Handle form submission for adding new setoran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_setoran'])) {
    $tanggal = $_POST['tanggal'];
    // Enforce cabang based on role: non-admin/manager must use their own branch
    $cabang = $canSelectCabang ? ($_POST['cabang'] ?? '') : ($user_cabang_nama ?? '');
    $resellers_json = $_POST['reseller'] ?? '[]';
    $resellers = json_decode($resellers_json, true) ?? [];

    if (!empty($resellers)) {
        $inserted_count = 0;
        foreach ($resellers as $reseller) {
            // Fetch sales data for the reseller on the selected date
            $stmt = $conn->prepare("SELECT
                dp.nama_produk as produk,
                dp.jumlah as qty,
                dp.harga_satuan,
                (dp.jumlah * dp.harga_satuan) as total
            FROM penjualan pj
            JOIN reseller r ON pj.reseller_id = r.reseller_id
            JOIN detail_penjualan dp ON pj.penjualan_id = dp.penjualan_id
            WHERE pj.tanggal_penjualan = ? AND r.nama_reseller = ?");
            $stmt->bind_param("ss", $tanggal, $reseller);
            $stmt->execute();
            $result = $stmt->get_result();
            $sales_data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($sales_data as $sale) {
                $produk = $sale['produk'];
                $qty = $sale['qty'];
                $harga_satuan = $sale['harga_satuan'];
                $total_setoran = $sale['total'];

                $stmt = $conn->prepare("INSERT INTO setoran_harian (tanggal, cabang, reseller, produk, qty, harga_satuan, total_setoran, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiddi", $tanggal, $cabang, $reseller, $produk, $qty, $harga_satuan, $total_setoran, $user['user_id']);
                if ($stmt->execute()) {
                    $inserted_count++;
                }
                $stmt->close();
            }
        }

        if ($inserted_count > 0) {
            $success_message = "$inserted_count setoran berhasil ditambahkan!";
        } else {
            $error_message = "Tidak ada data penjualan untuk reseller terpilih.";
        }
    } else {
        $error_message = "Tidak ada reseller yang dipilih.";
    }
}

// Handle form submission for adding evidence setoran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_evidence'])) {
    $ev_tanggal = $_POST['ev_tanggal'] ?? date('Y-m-d');
    $ev_cabang = $_POST['ev_cabang'] ?? $user_cabang_nama;
    $ev_atas_nama = trim($_POST['ev_atas_nama'] ?? '');
    $ev_bank = $_POST['ev_bank'] ?? '';
    $ev_bank_pengirim = $_POST['ev_bank_pengirim'] ?? '';
    $ev_nominal_raw = $_POST['ev_nominal'] ?? '';
    // Normalize nominal (allow comma/period formats)
    $ev_nominal = is_string($ev_nominal_raw) ? floatval(str_replace(['.', ','], ['', ''], $ev_nominal_raw)) : floatval($ev_nominal_raw);
    $ev_keterangan = trim($_POST['ev_keterangan'] ?? '');

    // Basic validation
    if ($ev_tanggal && $ev_cabang && $ev_atas_nama && $ev_bank && $ev_bank_pengirim && $ev_nominal > 0 && isset($_FILES['ev_evidence'])) {
        // Use relative path from this file - portable across different installations
        // Works for: local dev, network access, hosting, different folder names
        $uploadDirFs = realpath(__DIR__ . '/../../assets/images/evidence');
        if (!$uploadDirFs || !is_dir($uploadDirFs)) {
            // Folder doesn't exist, create using absolute path construction
            $uploadDirFs = dirname(dirname(__DIR__)) . '/assets/images/evidence';
            if (!is_dir($uploadDirFs)) {
                @mkdir($uploadDirFs, 0777, true);
                @chmod($uploadDirFs, 0777); // Ensure writable from network
            }
        }
        $uploadDirUrl = BASE_PATH . '/assets/images/evidence';

        $file = $_FILES['ev_evidence'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                $error_message = 'Ukuran file terlalu besar. Maksimal 5MB.';
            } else {
                // Validate mime type
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];
                if (!isset($allowed[$mime])) {
                    $error_message = 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.';
                } else {
                    // Build safe filename
                    $ext = $allowed[$mime];
                    $baseName = 'evidence_' . date('Ymd_His') . '_' . (int)$user['user_id'] . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = rtrim($uploadDirFs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $baseName;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        // Set file permission for network access (Mac/Linux/Windows network)
                        @chmod($targetPath, 0644);
                        
                        $relativeUrl = rtrim($uploadDirUrl, '/') . '/' . $baseName;

                        // Insert record (supports new columns if available)
                        $uid = (int)$user['user_id'];
                        if ($evidence_has_nominal && $evidence_has_bank_pengirim) {
                            $stmt = $conn->prepare("INSERT INTO setoran_evidence (tanggal, cabang, atas_nama, bank_pengirim, bank, nominal, evidence_path, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            if ($stmt) {
                                $stmt->bind_param("sssssdssi", $ev_tanggal, $ev_cabang, $ev_atas_nama, $ev_bank_pengirim, $ev_bank, $ev_nominal, $relativeUrl, $ev_keterangan, $uid);
                                if ($stmt->execute()) {
                                    $success_message = 'Evidence setoran berhasil disimpan.';
                                } else {
                                    $error_message = 'Gagal menyimpan ke database: ' . htmlspecialchars($stmt->error);
                                }
                                $stmt->close();
                            } else {
                                $error_message = 'Struktur tabel setoran_evidence belum mendukung nominal/bank pengirim. Jalankan migrasi.';
                            }
                        } else {
                            // Fallback insert minimal columns to avoid blocking, plus show upgrade notice
                            $stmt = $conn->prepare("INSERT INTO setoran_evidence (tanggal, cabang, atas_nama, bank, evidence_path, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            if ($stmt) {
                                $stmt->bind_param("ssssssi", $ev_tanggal, $ev_cabang, $ev_atas_nama, $ev_bank, $relativeUrl, $ev_keterangan, $uid);
                                if ($stmt->execute()) {
                                    $success_message = 'Evidence tersimpan tanpa nominal/bank pengirim. Jalankan migrasi untuk menyimpan field baru.';
                                } else {
                                    $error_message = 'Gagal menyimpan ke database: ' . htmlspecialchars($stmt->error);
                                }
                                $stmt->close();
                            } else {
                                $error_message = 'Tabel setoran_evidence belum ada. Jalankan migrasi database.';
                            }
                        }
                    } else {
                        // Enhanced error for network debugging
                        $error_message = 'Gagal mengunggah file evidence. ';
                        $error_message .= 'Debug: Dir=' . $uploadDirFs . ', ';
                        $error_message .= 'Exists=' . (is_dir($uploadDirFs) ? 'yes' : 'no') . ', ';
                        $error_message .= 'Writable=' . (is_writable($uploadDirFs) ? 'yes' : 'no') . ', ';
                        $error_message .= 'TmpFile=' . (file_exists($file['tmp_name']) ? 'exists' : 'missing') . '. ';
                        $error_message .= 'Pastikan folder evidence memiliki permission 0777 untuk akses network.';
                    }
                }
            }
        } else {
            // Map upload error codes
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File melebihi upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File terlalu besar',
                UPLOAD_ERR_PARTIAL => 'Upload tidak lengkap',
                UPLOAD_ERR_NO_FILE => 'Tidak ada file',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temp tidak ada',
                UPLOAD_ERR_CANT_WRITE => 'Gagal tulis ke disk',
                UPLOAD_ERR_EXTENSION => 'Extension PHP error'
            ];
            $code = (int)$file['error'];
            $error_message = 'Upload error (kode ' . $code . '): ' . ($errors[$code] ?? 'Unknown') . '.';
        }
    } else {
        $error_message = 'Lengkapi semua field evidence setoran (termasuk nominal dan bank pengirim).';
    }
}

// Handle export
if (isset($_GET['export'])) {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    // Apply cabang filter for export based on role
    $export_cabang_filter = $canSelectCabang ? ($_GET['cabang_filter'] ?? '') : ($user_cabang_nama ?? '');
    if (!empty($export_cabang_filter)) {
        $stmt = $conn->prepare("SELECT * FROM setoran_harian WHERE tanggal BETWEEN ? AND ? AND cabang = ? ORDER BY tanggal DESC");
        $stmt->bind_param("sss", $start_date, $end_date, $export_cabang_filter);
    } else {
        $stmt = $conn->prepare("SELECT * FROM setoran_harian WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal DESC");
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($_GET['export'] === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="setoran_harian_' . $start_date . '_to_' . $end_date . '.xls"');
        echo "Tanggal\tCabang\tReseller\tProduk\tQty\tTotal Setoran\n";
        foreach ($data as $row) {
            echo $row['tanggal'] . "\t" . $row['cabang'] . "\t" . $row['reseller'] . "\t" . $row['produk'] . "\t" . $row['qty'] . "\t" . $row['total_setoran'] . "\n";
        }
        exit;
    } elseif ($_GET['export'] === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="setoran_harian_' . $start_date . '_to_' . $end_date . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Tanggal', 'Cabang', 'Reseller', 'Produk', 'Qty', 'Total Setoran']);
        foreach ($data as $row) {
            fputcsv($output, [$row['tanggal'], $row['cabang'], $row['reseller'], $row['produk'], $row['qty'], $row['total_setoran']]);
        }
        fclose($output);
        exit;
    }
}

// Handle Evidence export (Excel)
if (isset($_GET['evidence_export'])) {
    $exportType = $_GET['evidence_export'];
    // Evidence date range (defaults to current month)
    $e_start = $_GET['evidence_start_date'] ?? date('Y-m-01');
    $e_end = $_GET['evidence_end_date'] ?? date('Y-m-d');
    // Evidence cabang (admin/manager selectable; others locked)
    $e_cabang = $canSelectCabang ? ($_GET['evidence_cabang'] ?? '') : ($user_cabang_nama ?? '');
    // Build select fields depending on table structure
    $selectFields = "tanggal, cabang, atas_nama, bank, evidence_path, keterangan";
    if ($evidence_has_bank_pengirim) { $selectFields = "tanggal, cabang, atas_nama, bank_pengirim, bank, evidence_path, keterangan"; }
    if ($evidence_has_nominal && strpos($selectFields, 'nominal') === false) {
        // Insert nominal before evidence_path
        $selectFields = str_replace('evidence_path', 'nominal, evidence_path', $selectFields);
    }

    // Restrict by selected cabang (empty means all for admin/manager)
    $cabangFilter = $e_cabang;
    $stmt = $conn->prepare("SELECT $selectFields FROM setoran_evidence WHERE tanggal BETWEEN ? AND ? AND (? = '' OR cabang = ?) ORDER BY tanggal DESC");
    $stmt->bind_param("ssss", $e_start, $e_end, $cabangFilter, $cabangFilter);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($exportType === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="laporan_evidence_setoran_' . date('Ymd_His') . '.xls"');
        // Header row
        $headers = ['Tanggal', 'Cabang', 'Atas Nama'];
        if ($evidence_has_bank_pengirim) { $headers[] = 'Bank Pengirim'; }
        $headers[] = 'Bank Tujuan';
        if ($evidence_has_nominal) { $headers[] = 'Nominal'; }
        $headers[] = 'Evidence URL';
        $headers[] = 'Keterangan';
        echo implode("\t", $headers) . "\n";
        foreach ($rows as $r) {
            $line = [];
            $line[] = $r['tanggal'] ?? '';
            $line[] = $r['cabang'] ?? '';
            $line[] = $r['atas_nama'] ?? '';
            if ($evidence_has_bank_pengirim) { $line[] = $r['bank_pengirim'] ?? ''; }
            $line[] = $r['bank'] ?? '';
            if ($evidence_has_nominal) { $line[] = isset($r['nominal']) ? (string)$r['nominal'] : ''; }
            $line[] = $r['evidence_path'] ?? '';
            $line[] = $r['keterangan'] ?? '';
            echo implode("\t", $line) . "\n";
        }
        exit;
    }
}

// Get data for display
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
// Laporan setoran cabang filter (admin/manager can select; others forced to own cabang)
$cabang_filter = $canSelectCabang ? ($_GET['cabang_filter'] ?? '') : ($user_cabang_nama ?? '');
// Evidence date range defaults to the same as setoran to keep GAP aligned by default
$evidence_start_date = $_GET['evidence_start_date'] ?? $start_date;
$evidence_end_date = $_GET['evidence_end_date'] ?? $end_date;
// Evidence cabang filter (admin/manager can select; others locked to own cabang)
$evidence_cabang = $canSelectCabang ? ($_GET['evidence_cabang'] ?? '') : ($user_cabang_nama ?? '');

$stmt = !empty($cabang_filter)
    ? $conn->prepare("SELECT * FROM setoran_harian WHERE tanggal BETWEEN ? AND ? AND cabang = ? ORDER BY tanggal DESC")
    : $conn->prepare("SELECT * FROM setoran_harian WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal DESC");
if (!empty($cabang_filter)) {
    $stmt->bind_param("sss", $start_date, $end_date, $cabang_filter);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}
$stmt->execute();
$result = $stmt->get_result();
$setoran_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Pre-calculate total laporan setoran (grand total) for GAP comparison, aligned to evidence filters
$setoran_total_nominal = 0;
try {
    // Prefer laporan cabang filter if provided; otherwise fall back to evidence cabang filter
    $sum_cabang = '';
    if (!empty($cabang_filter)) {
        $sum_cabang = $cabang_filter;
    } elseif (!empty($evidence_cabang)) {
        $sum_cabang = $evidence_cabang;
    }

    if (!empty($sum_cabang)) {
        $stmtSum = $conn->prepare("SELECT COALESCE(SUM(total_setoran),0) AS total FROM setoran_harian WHERE tanggal BETWEEN ? AND ? AND cabang = ?");
        $stmtSum->bind_param("sss", $start_date, $end_date, $sum_cabang);
    } else {
        $stmtSum = $conn->prepare("SELECT COALESCE(SUM(total_setoran),0) AS total FROM setoran_harian WHERE tanggal BETWEEN ? AND ?");
        $stmtSum->bind_param("ss", $start_date, $end_date);
    }
    if ($stmtSum->execute()) {
        $resSum = $stmtSum->get_result();
        if ($rowSum = $resSum->fetch_assoc()) {
            $setoran_total_nominal = (float)$rowSum['total'];
        }
    }
    $stmtSum->close();
} catch (Throwable $t) {
    // fallback: keep zero if sum fails
}

// Get cabang options
$cabang_result = $conn->query("SELECT DISTINCT nama_cabang as cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");
$cabang_options = $cabang_result->fetch_all(MYSQLI_ASSOC);

// Get reseller options
$reseller_result = $conn->query("SELECT nama_reseller as nama FROM reseller WHERE status = 'active' ORDER BY nama_reseller");
$reseller_options = $reseller_result->fetch_all(MYSQLI_ASSOC);

// Load recent evidences
$evidence_rows = [];
try {
    $selectFields = "evidence_id, tanggal, cabang, atas_nama, bank, evidence_path, keterangan, created_at";
    if ($evidence_has_nominal) { $selectFields .= ", nominal"; }
    if ($evidence_has_bank_pengirim) { $selectFields .= ", bank_pengirim"; }
    $stmtEv = $conn->prepare("SELECT $selectFields FROM setoran_evidence WHERE tanggal BETWEEN ? AND ? AND (? = '' OR cabang = ?) ORDER BY tanggal DESC");
    $cabangFilter = $evidence_cabang; // if empty and admin/manager, no restriction
    $stmtEv->bind_param("ssss", $evidence_start_date, $evidence_end_date, $cabangFilter, $cabangFilter);
    if ($stmtEv->execute()) {
        $resultEv = $stmtEv->get_result();
        $evidence_rows = $resultEv->fetch_all(MYSQLI_ASSOC);
    }
    $stmtEv->close();
} catch (Throwable $e) {
    // Table might not exist yet; ignore for now
}

// Aggregate totals for evidence nominal if column exists
$evidence_total_nominal = 0;
$evidence_bank_pengirim_totals = [];
$evidence_bank_tujuan_totals = [];
if ($evidence_has_nominal && !empty($evidence_rows)) {
    foreach ($evidence_rows as $evRow) {
        $nom = isset($evRow['nominal']) ? (float)$evRow['nominal'] : 0.0;
        $evidence_total_nominal += $nom;
        if ($evidence_has_bank_pengirim) {
            $bp = $evRow['bank_pengirim'] ?? 'UNKNOWN';
            $evidence_bank_pengirim_totals[$bp] = ($evidence_bank_pengirim_totals[$bp] ?? 0) + $nom;
        }
        $bt = $evRow['bank'] ?? 'UNKNOWN';
        $evidence_bank_tujuan_totals[$bt] = ($evidence_bank_tujuan_totals[$bt] ?? 0) + $nom;
    }
    // Sort descending by total
    arsort($evidence_bank_pengirim_totals);
    arsort($evidence_bank_tujuan_totals);
}

// Compute GAP between laporan setoran and evidence
$gap_nominal = $setoran_total_nominal - $evidence_total_nominal; // positive means setoran > evidence
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
    <link rel="stylesheet" href="../../assets/css/laporan_styles.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        body {
            font-family: 'Lexend', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .dashboard-header {
            background: var(--gradient);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-buttons {
            display: flex;
            gap: 1rem;
        }

        .back-button, .logout-button {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .logout-button {
            background: var(--error-color);
            color: white;
        }

        .logout-button:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .main-content {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.5fr 2fr;
            gap: 2rem;
        }
        .full-width {
            grid-column: 1 / -1;
        }

        .form-card, .report-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .submit-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        .metrics-bar {
            display:flex; flex-wrap:wrap; gap:0.75rem; margin:0.5rem 0 1rem 0;
        }
        .metric-button {
            position:relative; padding:0.85rem 1.2rem; border:none; border-radius:14px; font-weight:600; font-size:0.9rem; letter-spacing:0.5px; cursor:default; color:#fff; overflow:hidden; min-width:220px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 6px 18px -4px rgba(0,0,0,0.25);
            backdrop-filter:blur(6px);
        }
        .metric-button span.value { font-weight:700; font-size:1rem; }
        .metric-gradient-setoran { background:linear-gradient(135deg,#0ea5e9,#6366f1,#3b82f6); }
        .metric-gradient-evidence { background:linear-gradient(135deg,#10b981,#34d399,#059669); }
        .metric-gradient-gap-pos { background:linear-gradient(135deg,#f59e0b,#f97316,#ef4444); }
        .metric-gradient-gap-neg { background:linear-gradient(135deg,#06b6d4,#3b82f6,#6366f1); }
        .metric-button:before { content:""; position:absolute; inset:0; background:linear-gradient(120deg,rgba(255,255,255,0.15),rgba(255,255,255,0)); mix-blend-mode:overlay; }
        .metric-button:hover { transform:translateY(-3px); box-shadow:0 10px 28px -6px rgba(0,0,0,0.35); }

        .filter-section {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .filter-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .export-btn {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 0.5rem;
        }

        .export-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .data-table th, .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: var(--gradient);
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .total-row {
            background: #f1f5f9;
            font-weight: 600;
        }

        .success-message {
            background: var(--success-color);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message {
            background: var(--error-color);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .data-table {
                font-size: 0.875rem;
            }

            .data-table th, .data-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="../../assets/images/logo_icon.png" alt="Logo" style="width: 50px; height: 50px; border-radius: 10px; object-fit: contain; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h1>Setoran Harian TAP</h1>
        </div>
        <div class="header-buttons">
            <a href="finance" class="back-button">← Kembali ke Finance</a>
            <a href="<?php echo BASE_PATH; ?>/logout" class="logout-button">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <h1 class="page-title">💰 Setoran Harian TAP</h1>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Form Section: Input Setoran (Left) -->
            <div class="form-card">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle"></i>
                    Tambah Setoran Baru
                </h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="tanggal">
                            <i class="fas fa-calendar"></i> Tanggal
                        </label>
                        <input type="date" id="tanggal" name="tanggal" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cabang">
                            <i class="fas fa-building"></i> Cabang
                        </label>
                        <?php if ($canSelectCabang): ?>
                            <select id="cabang" name="cabang" class="form-select" required onchange="loadResellers()">
                                <option value="">Pilih Cabang</option>
                                <?php foreach ($cabang_options as $cabang): ?>
                                    <option value="<?php echo htmlspecialchars($cabang['cabang']); ?>" <?php echo ($cabang['cabang'] === $user_cabang_nama) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cabang['cabang']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" id="cabang_display" class="form-input" value="<?php echo htmlspecialchars($user_cabang_nama); ?>" readonly>
                            <input type="hidden" id="cabang" name="cabang" value="<?php echo htmlspecialchars($user_cabang_nama); ?>">
                            <script>
                                // Ensure reseller list loads for fixed cabang
                                document.addEventListener('DOMContentLoaded', function(){
                                    loadResellers();
                                });
                            </script>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reseller">
                            <i class="fas fa-user"></i> Reseller
                        </label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <select id="reseller" class="form-select" style="flex: 1;">
                                <option value="">Pilih Reseller</option>
                                <?php foreach ($reseller_options as $reseller): ?>
                                    <option value="<?php echo htmlspecialchars($reseller['nama']); ?>"><?php echo htmlspecialchars($reseller['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" onclick="addReseller()" class="export-btn" style="background: var(--primary-color);">
                                <i class="fas fa-plus"></i> Tambah Reseller
                            </button>
                        </div>
                        <div id="selected-resellers" style="margin-top: 1rem;">
                            <!-- Selected resellers will be listed here -->
                        </div>
                        <input type="hidden" id="selected-resellers-input" name="reseller" value="[]">
                    </div>

                    <!-- Sales Summary Table -->
                    <div id="sales-summary" style="display: block; margin-top: 1rem;">
                        <h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--text-primary);">Resume Penjualan Reseller</h3>
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th>Reseller</th>
                                        <th>Produk</th>
                                        <th>Qty</th>
                                        <th>Harga Satuan</th>
                                        <th>Total</th>
                                        <th>Metode Pembayaran</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="sales-table-body">
                                    <tr><td colspan="7" style="text-align: center;">Pilih reseller untuk melihat resume penjualan.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="grand_total">
                            <i class="fas fa-calculator"></i> Grand Total Setoran (Rp)
                        </label>
                        <input type="number" id="grand_total" name="grand_total" class="form-input" placeholder="0" min="0" step="1000" readonly required>
                    </div>

                    <button type="submit" name="add_setoran" class="submit-btn">
                        <i class="fas fa-save"></i> Simpan Setoran
                    </button>
                </form>
            </div>
            <!-- Report Section: Laporan Setoran (Right) -->
            <div class="report-card">
                <h2 class="card-title">
                    <i class="fas fa-chart-line"></i>
                    Laporan Setoran Harian
                </h2>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-group">
                        <label class="form-label" for="start_date">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" class="form-input" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label class="form-label" for="end_date">Tanggal Akhir</label>
                        <input type="date" id="end_date" name="end_date" class="form-input" value="<?php echo $end_date; ?>">
                    </div>
                    <?php if ($canSelectCabang): ?>
                        <div class="filter-group">
                            <label class="form-label" for="cabang_filter">Cabang</label>
                            <select id="cabang_filter" name="cabang_filter" class="form-select">
                                <option value="">Semua Cabang</option>
                                <?php foreach ($cabang_options as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['cabang']); ?>" <?php echo ($cabang_filter === $c['cabang']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['cabang']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="cabang_filter" value="<?php echo htmlspecialchars($cabang_filter); ?>">
                    <?php endif; ?>
                    <button type="button" onclick="filterData()" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <div>
                        <button onclick="exportData('excel')" class="export-btn">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button onclick="exportData('csv')" class="export-btn">
                            <i class="fas fa-file-csv"></i> CSV
                        </button>
                        <button onclick="window.print()" class="export-btn">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Data Table -->
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Cabang</th>
                                <th>Reseller</th>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Total Setoran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total = 0;
                            foreach ($setoran_data as $row):
                                $total += $row['total_setoran'];
                            ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['cabang']); ?></td>
                                    <td><?php echo htmlspecialchars($row['reseller']); ?></td>
                                    <td><?php echo htmlspecialchars($row['produk']); ?></td>
                                    <td><?php echo htmlspecialchars($row['qty']); ?></td>
                                    <td>Rp <?php echo number_format($row['total_setoran'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="5"><strong>Total</strong></td>
                                <td><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($setoran_data)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        <i class="fas fa-inbox fa-3x"></i>
                        <p style="margin-top: 1rem;">Tidak ada data setoran untuk periode ini.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Input Evidence (Full Width) -->
        <div class="form-card full-width" style="margin-top: 1.5rem;" id="evidence-form">
            <h2 class="card-title">
                <i class="fas fa-file-image"></i>
                Input Evidence Setoran
            </h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label" for="ev_tanggal">
                        <i class="fas fa-calendar"></i> Tanggal
                    </label>
                    <input type="date" id="ev_tanggal" name="ev_tanggal" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_nominal">
                        <i class="fas fa-money-bill-wave"></i> Nominal (Rp)
                    </label>
                    <input type="number" id="ev_nominal" name="ev_nominal" class="form-input" placeholder="Contoh: 15000" min="1" step="any" inputmode="numeric" required>
                    <small style="color: var(--text-secondary);">Masukkan angka tanpa tanda titik/koma. Validasi server akan tetap membersihkan format.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_cabang">
                        <i class="fas fa-building"></i> Cabang
                    </label>
                    <?php if ($canSelectCabang): ?>
                        <select id="ev_cabang" name="ev_cabang" class="form-select" required>
                            <option value="">Pilih Cabang</option>
                            <?php foreach ($cabang_options as $cabang): ?>
                                <option value="<?php echo htmlspecialchars($cabang['cabang']); ?>" <?php echo ($cabang['cabang'] === $user_cabang_nama) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cabang['cabang']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" id="ev_cabang" name="ev_cabang" class="form-input" value="<?php echo htmlspecialchars($user_cabang_nama); ?>" readonly>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_bank_pengirim">
                        <i class="fas fa-credit-card"></i> Bank Pengirim
                    </label>
                    <select id="ev_bank_pengirim" name="ev_bank_pengirim" class="form-select" required>
                        <option value="">Pilih Bank Pengirim</option>
                        <option value="BCA">BCA</option>
                        <option value="BRI">BRI</option>
                        <option value="BNI">BNI</option>
                        <option value="MANDIRI">MANDIRI</option>
                        <option value="DANA">DANA</option>
                        <option value="GOPAY">GOPAY</option>
                        <option value="OVO">OVO</option>
                        <option value="E-MONEY">E-MONEY</option>
                        <option value="LAINNYA">LAINNYA</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_atas_nama">
                        <i class="fas fa-user"></i> Setoran Atas Nama
                    </label>
                    <input type="text" id="ev_atas_nama" name="ev_atas_nama" class="form-input" placeholder="Nama pemilik rekening/penyetor" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_bank">
                        <i class="fas fa-university"></i> Bank Tujuan
                    </label>
                    <select id="ev_bank" name="ev_bank" class="form-select" required>
                        <option value="">Pilih Bank</option>
                        <option value="BCA CV">BCA CV</option>
                        <option value="BNI CV">BNI CV</option>
                        <option value="BRI CV">BRI CV</option>
                        <option value="MANDIRI CV">MANDIRI CV</option>
                        <option value="Bank Lain">Bank Lain</option>
                        <option value="Program">Program</option>
                        <option value="Budget Marketing">Budget Marketing</option>
                        <option value="Eksekusi">Eksekusi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_evidence">
                        <i class="fas fa-image"></i> Evidence (gambar)
                    </label>
                    <input type="file" id="ev_evidence" name="ev_evidence" class="form-input" accept="image/jpeg,image/png,image/webp" required>
                    <small style="color: var(--text-secondary);">Format: JPG, PNG, WEBP. Maks 5MB.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_keterangan">
                        <i class="fas fa-align-left"></i> Keterangan
                    </label>
                    <input type="text" id="ev_keterangan" name="ev_keterangan" class="form-input" placeholder="Opsional">
                </div>

                <button type="submit" name="add_evidence" class="submit-btn">
                    <i class="fas fa-upload"></i> Simpan Evidence
                </button>
            </form>
        </div>

        <!-- Laporan Evidence (Full Width) -->
    <div class="form-card full-width" style="margin-top: 1.5rem;" id="evidence-report">
            <h2 class="card-title">
                <i class="fas fa-folder-open"></i>
                Laporan Evidence Setoran
            </h2>
            <div class="filter-section" style="margin-top: 0;" id="evidence-filters">
                <div style="display:flex; gap: 0.75rem; align-items:flex-end; flex-wrap: wrap;">
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <label for="evidence_start_date" class="form-label" style="margin:0; font-size:.9rem; color:var(--text-secondary);">Dari</label>
                        <input type="date" id="evidence_start_date" class="form-input" style="padding:.4rem .6rem;" value="<?php echo htmlspecialchars($evidence_start_date); ?>">
                        <label for="evidence_end_date" class="form-label" style="margin-left:.5rem; font-size:.9rem; color:var(--text-secondary);">Sampai</label>
                        <input type="date" id="evidence_end_date" class="form-input" style="padding:.4rem .6rem;" value="<?php echo htmlspecialchars($evidence_end_date); ?>">
                        <button type="button" class="export-btn" onclick="filterEvidence()" style="background: var(--accent-color);">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                    <?php if ($canSelectCabang): ?>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <label for="evidence_cabang" class="form-label" style="margin:0; font-size:.9rem; color:var(--text-secondary);">Cabang</label>
                            <select id="evidence_cabang" class="form-select" style="padding:.4rem .6rem; min-width:150px;">
                                <option value="">Semua Cabang</option>
                                <?php foreach ($cabang_options as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['cabang']); ?>" <?php echo ($evidence_cabang === $c['cabang']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['cabang']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="evidence_cabang" value="<?php echo htmlspecialchars($evidence_cabang); ?>">
                    <?php endif; ?>
                    <button type="button" class="export-btn" onclick="exportEvidenceExcel()">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button type="button" class="export-btn" onclick="exportEvidencePDF()" style="background: var(--secondary-color);">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button type="button" class="export-btn" onclick="printEvidence()">
                        <i class="fas fa-print"></i> Print (Full Section)
                    </button>
                    <button type="button" class="export-btn" onclick="printEvidenceOnly()" style="background: var(--accent-color);">
                        <i class="fas fa-print"></i> Print Evidence Only
                    </button>
                </div>
            </div>
            <div style="overflow-x: auto;" id="evidence-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Cabang</th>
                            <th>Atas Nama</th>
                            <th>Bank Pengirim</th>
                            <th>Bank</th>
                            <th>Nominal</th>
                            <th>Evidence</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($evidence_rows)): ?>
                            <?php foreach ($evidence_rows as $ev): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($ev['tanggal']))); ?></td>
                                    <td><?php echo htmlspecialchars($ev['cabang']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['atas_nama']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['bank_pengirim'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($ev['bank']); ?></td>
                                    <td>
                                        <?php echo isset($ev['nominal']) ? ('Rp ' . number_format((float)$ev['nominal'], 0, ',', '.')) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($ev['evidence_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($ev['evidence_path']); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($ev['evidence_path']); ?>" alt="Evidence" style="height:50px; width:auto; border-radius:6px; border:1px solid var(--border); object-fit:cover;" />
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($ev['keterangan'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; color: var(--text-secondary);">Belum ada evidence.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if ($evidence_has_nominal): ?>
                    <?php
                        $gap_class = $gap_nominal >= 0 ? 'metric-gradient-gap-pos' : 'metric-gradient-gap-neg';
                    ?>
                    <div style="margin-top:1rem; background:#f1f5f9; padding:1rem; border-radius:8px;">
                        <h3 style="margin:0 0 .75rem 0; font-size:1.15rem; display:flex; align-items:center; gap:8px;">💹 Ringkasan Nominal & GAP</h3>
                        <div class="metrics-bar">
                            <button type="button" class="metric-button metric-gradient-setoran" disabled>
                                <span>Total Setoran</span>
                                <span class="value">Rp <?php echo number_format($setoran_total_nominal,0,',','.'); ?></span>
                            </button>
                            <button type="button" class="metric-button metric-gradient-evidence" disabled>
                                <span>Total Evidence</span>
                                <span class="value">Rp <?php echo number_format($evidence_total_nominal,0,',','.'); ?></span>
                            </button>
                            <button type="button" class="metric-button <?php echo $gap_class; ?>" disabled>
                                <span>GAP (Setoran - Evidence)</span>
                                <span class="value">Rp <?php echo number_format($gap_nominal,0,',','.'); ?></span>
                            </button>
                        </div>
                        <div style="display:grid; gap:1rem; grid-template-columns: repeat(auto-fit, minmax(250px,1fr));">
                            <div>
                                <h4 style="margin:0 0 .5rem 0; font-size:.95rem;">Per Bank Pengirim</h4>
                                <?php if (!empty($evidence_bank_pengirim_totals)): ?>
                                    <table style="width:100%; border-collapse:collapse;">
                                        <thead>
                                            <tr style="background:#6366f1; color:#fff; font-size:.75rem; text-transform:uppercase;">
                                                <th style="padding:6px; text-align:left;">Bank Pengirim</th>
                                                <th style="padding:6px; text-align:right;">Total Nominal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($evidence_bank_pengirim_totals as $b=>$val): ?>
                                                <tr style="background:#fff; border-bottom:1px solid #e2e8f0;">
                                                    <td style="padding:6px;"><?php echo htmlspecialchars($b); ?></td>
                                                    <td style="padding:6px; text-align:right;">Rp <?php echo number_format($val,0,',','.'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin:0; font-size:.85rem; color:var(--text-secondary);">Tidak ada data bank pengirim.</p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 style="margin:0 0 .5rem 0; font-size:.95rem;">Per Bank Tujuan</h4>
                                <?php if (!empty($evidence_bank_tujuan_totals)): ?>
                                    <table style="width:100%; border-collapse:collapse;">
                                        <thead>
                                            <tr style="background:#8b5cf6; color:#fff; font-size:.75rem; text-transform:uppercase;">
                                                <th style="padding:6px; text-align:left;">Bank Tujuan</th>
                                                <th style="padding:6px; text-align:right;">Total Nominal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($evidence_bank_tujuan_totals as $b=>$val): ?>
                                                <tr style="background:#fff; border-bottom:1px solid #e2e8f0;">
                                                    <td style="padding:6px;"><?php echo htmlspecialchars($b); ?></td>
                                                    <td style="padding:6px; text-align:right;">Rp <?php echo number_format($val,0,',','.'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin:0; font-size:.85rem; color:var(--text-secondary);">Tidak ada data bank tujuan.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="margin-top:1rem; background:#fff3cd; color:#856404; padding:1rem; border-radius:8px; font-size:.9rem;">
                        Kolom nominal belum tersedia di tabel <code>setoran_evidence</code>. Jalankan migrasi untuk menampilkan total.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function exportEvidenceExcel() {
            const url = new URL(window.location);
            url.searchParams.set('evidence_export', 'excel');
            // include evidence date range if available
            const es = document.getElementById('evidence_start_date');
            const ee = document.getElementById('evidence_end_date');
            if (es && es.value) url.searchParams.set('evidence_start_date', es.value);
            if (ee && ee.value) url.searchParams.set('evidence_end_date', ee.value);
            const ec = document.getElementById('evidence_cabang');
            if (ec && ec.value !== undefined) url.searchParams.set('evidence_cabang', ec.value);
            window.location.href = url.toString();
        }

        async function exportEvidencePDF() {
            const { jsPDF } = window.jspdf;
            const node = document.querySelector('#evidence-report');
            const table = node.querySelector('table');
            if (!table) return;
            const canvas = await html2canvas(table, { scale: 2, backgroundColor: '#ffffff' });
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('l', 'mm', 'a4');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const imgWidth = pageWidth - 20; // margins
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            let y = 10;
            if (imgHeight > pageHeight - 20) {
                // Scale down to fit one page height
                const scale = (pageHeight - 20) / imgHeight;
                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth * scale, imgHeight * scale);
            } else {
                pdf.addImage(imgData, 'PNG', 10, y, imgWidth, imgHeight);
            }
            pdf.save('laporan_evidence_setoran_' + new Date().toISOString().slice(0,10) + '.pdf');
        }

        function printEvidence() {
            // Print the entire evidence section but hide filters in print
            const node = document.querySelector('#evidence-report');
            const content = node.innerHTML;
            const w = window.open('', '_blank');
            w.document.write('<html><head><title>Print Evidence Section</title>');
            w.document.write('<link rel="stylesheet" href="../../assets/css/styles.css">');
            w.document.write('<style>body{font-family: "Lexend", sans-serif; padding:16px;} .data-table th{background:#334155;color:#fff;} img{max-height:60px;} thead{display:table-header-group;} @media print{#evidence-filters,.metrics-bar,button{display:none!important;}}</style>');
            w.document.write('</head><body>');
            w.document.write('<h2 style="margin-top:0;">Laporan Evidence Setoran</h2>');
            w.document.write(content);
            w.document.write('</body></html>');
            w.document.close();
            w.focus();
            w.print();
            w.close();
        }

        function printEvidenceOnly() {
            // Print ONLY the evidence table (thead + tbody), excluding filters and summaries
            const wrapper = document.querySelector('#evidence-table-wrapper');
            if (!wrapper) { alert('Evidence table tidak ditemukan'); return; }
            const table = wrapper.querySelector('table');
            if (!table) { alert('Evidence table tidak ditemukan'); return; }
            const tableHtml = table.outerHTML;
            const w = window.open('', '_blank');
            w.document.write('<html><head><title>Print Evidence Only</title>');
            w.document.write('<style>body{font-family:Lexend,Arial,sans-serif;margin:0;padding:12px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ccc;padding:6px;font-size:11px;} thead{background:#111827;color:#fff;display:table-header-group;} tfoot{display:table-footer-group;} @page{size:auto;margin:12mm;} </style>');
            w.document.write('</head><body>');
            w.document.write(tableHtml);
            w.document.write('</body></html>');
            w.document.close();
            w.focus();
            w.print();
            w.close();
        }
        function filterData() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const cabangEl = document.getElementById('cabang_filter');
            const cabangVal = cabangEl ? cabangEl.value : '';
            const url = new URL(window.location);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            if (cabangVal !== '') { url.searchParams.set('cabang_filter', cabangVal); } else { url.searchParams.delete('cabang_filter'); }
            window.location.href = url.toString();
        }

        function filterEvidence() {
            const startDate = document.getElementById('evidence_start_date').value;
            const endDate = document.getElementById('evidence_end_date').value;
            const cabang = document.getElementById('evidence_cabang') ? document.getElementById('evidence_cabang').value : '';
            const url = new URL(window.location);
            if (startDate) url.searchParams.set('evidence_start_date', startDate);
            if (endDate) url.searchParams.set('evidence_end_date', endDate);
            if (cabang !== '') url.searchParams.set('evidence_cabang', cabang); else url.searchParams.delete('evidence_cabang');
            window.location.href = url.toString();
        }

        function exportData(type) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const cabangEl = document.getElementById('cabang_filter');
            const cabangVal = cabangEl ? cabangEl.value : '';
            const url = new URL(window.location);
            url.searchParams.set('export', type);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            if (cabangVal !== '') { url.searchParams.set('cabang_filter', cabangVal); } else { url.searchParams.delete('cabang_filter'); }
            window.location.href = url.toString();
        }

        function loadResellers() {
            const cabangEl = document.getElementById('cabang');
            const cabang = cabangEl ? cabangEl.value : '';
            const resellerSelect = document.getElementById('reseller');

            if (!cabang) {
                resellerSelect.innerHTML = '<option value="">Pilih Cabang terlebih dahulu</option>';
                return;
            }

            // AJAX request to get resellers for selected cabang
            fetch(`get_resellers.php?cabang=${encodeURIComponent(cabang)}`)
                .then(response => response.json())
                .then(data => {
                    resellerSelect.innerHTML = '<option value="">Pilih Reseller</option>';
                    data.forEach(reseller => {
                        const option = document.createElement('option');
                        option.value = reseller.nama_reseller;
                        option.textContent = reseller.nama_reseller;
                        resellerSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading resellers:', error);
                    resellerSelect.innerHTML = '<option value="">Error loading resellers</option>';
                });
        }

        let selectedResellers = [];

        function addReseller() {
            const resellerSelect = document.getElementById('reseller');
            const selectedValue = resellerSelect.value;
            const selectedText = resellerSelect.options[resellerSelect.selectedIndex].text;

            if (!selectedValue || selectedResellers.includes(selectedValue)) {
                return;
            }

            selectedResellers.push(selectedValue);

            const selectedResellersDiv = document.getElementById('selected-resellers');
            const resellerItem = document.createElement('div');
            resellerItem.className = 'reseller-item';
            resellerItem.style.display = 'flex';
            resellerItem.style.alignItems = 'center';
            resellerItem.style.gap = '0.5rem';
            resellerItem.style.marginBottom = '0.5rem';
            resellerItem.innerHTML = `
                <span>${selectedText}</span>
                <button type="button" onclick="removeReseller('${selectedValue}')" class="export-btn" style="background: var(--error-color); padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            selectedResellersDiv.appendChild(resellerItem);

            // Update hidden input
            document.getElementById('selected-resellers-input').value = JSON.stringify(selectedResellers);

            // Reset dropdown
            resellerSelect.value = '';

            // Load sales data for all selected resellers
            loadSalesData();
        }

        function removeReseller(reseller) {
            selectedResellers = selectedResellers.filter(r => r !== reseller);
            const selectedResellersDiv = document.getElementById('selected-resellers');
            selectedResellersDiv.innerHTML = '';
            selectedResellers.forEach(r => {
                const resellerItem = document.createElement('div');
                resellerItem.className = 'reseller-item';
                resellerItem.style.display = 'flex';
                resellerItem.style.alignItems = 'center';
                resellerItem.style.gap = '0.5rem';
                resellerItem.style.marginBottom = '0.5rem';
                resellerItem.innerHTML = `
                    <span>${r}</span>
                    <button type="button" onclick="removeReseller('${r}')" class="export-btn" style="background: var(--error-color); padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                selectedResellersDiv.appendChild(resellerItem);
            });
            // Update hidden input
            document.getElementById('selected-resellers-input').value = JSON.stringify(selectedResellers);
            loadSalesData();
        }

        function loadSalesData() {
            const salesSummary = document.getElementById('sales-summary');
            const salesTableBody = document.getElementById('sales-table-body');
            const grandTotalInput = document.getElementById('grand_total');

            if (selectedResellers.length === 0) {
                salesSummary.style.display = 'none';
                grandTotalInput.value = '';
                return;
            }

            const resellersStr = selectedResellers.join(',');

            // AJAX request to get sales data for selected resellers
            const tanggal = document.getElementById('tanggal').value;
            fetch(`get_sales.php?tanggal=${encodeURIComponent(tanggal)}&reseller=${encodeURIComponent(resellersStr)}`)
                .then(response => response.json())
                .then(data => {
                    salesTableBody.innerHTML = '';
                    let grandTotal = 0;
                    if (data.length > 0) {
                        data.forEach(sale => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${sale.reseller}</td>
                                <td>${sale.produk}</td>
                                <td>${sale.qty}</td>
                                <td>Rp ${parseFloat(sale.harga_satuan).toLocaleString('id-ID')}</td>
                                <td>Rp ${parseFloat(sale.total).toLocaleString('id-ID')}</td>
                                <td>${sale.metode_pembayaran || 'N/A'}</td>
                                <td>${sale.status || ''}</td>
                            `;
                            salesTableBody.appendChild(row);
                            grandTotal += parseFloat(sale.total) || 0;
                        });
                        salesSummary.style.display = 'block';
                    } else {
                        salesTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Tidak ada data penjualan untuk reseller terpilih.</td></tr>';
                        salesSummary.style.display = 'block';
                        grandTotal = 0;
                    }
                    grandTotalInput.value = grandTotal;
                })
                .catch(error => {
                    console.error('Error loading sales data:', error);
                    salesTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--error-color);">Error loading sales data.</td></tr>';
                    salesSummary.style.display = 'block';
                    grandTotalInput.value = '';
                });
        }
    </script>
</body>
</html>
