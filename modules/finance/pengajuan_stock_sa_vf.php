<?php
require_once '../../config/config.php';
requireLogin();

$user = getCurrentUser();
// Access: admin, manager, finance
if (!in_array($user['role'], ['administrator','manager','finance'])) {
    $_SESSION['error_message'] = 'Akses Ditolak! Halaman ini hanya bisa diakses oleh Administrator, Manager & Finance.';
    header('Location: ' . BASE_PATH . '/modules/finance/finance.php');
    exit();
}

$conn = getDBConnection();

// Utilities: check column existence quickly
function table_has_column($conn, $table, $column) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM $table LIKE ?");
        $stmt->bind_param('s', $column);
        $stmt->execute();
        $res = $stmt->get_result();
        return ($res && $res->num_rows > 0);
    } catch (Exception $e) { return false; }
}

// Get warehouse list (cabang)
$cabang_list = [];
try {
    $q = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status='active' ORDER BY nama_cabang");
    while ($r = $q->fetch_assoc()) { $cabang_list[] = $r; }
} catch (Exception $e) { /* ignore */ }

// Get Requester (reseller) options
$requester_list = [];
$has_kategori = table_has_column($conn, 'reseller', 'kategori');
try {
    if ($has_kategori) {
        $stmt = $conn->prepare("SELECT reseller_id, nama_reseller, cabang_id, kategori FROM reseller WHERE status='active' AND kategori IN ('General Manager','Manager','Supervisor','Player','Player/Pemain','Pemain') ORDER BY nama_reseller");
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query("SELECT reseller_id, nama_reseller, cabang_id FROM reseller WHERE status='active' ORDER BY nama_reseller");
    }
    while ($row = $res->fetch_assoc()) { $requester_list[] = $row; }
} catch (Exception $e) { /* ignore */ }

// Cabang name map
$cabang_map = [];
foreach ($cabang_list as $c) { $cabang_map[$c['cabang_id']] = $c['nama_cabang']; }

// Get RS Eksekusi list from outlet
$rs_filter = isset($_GET['rs_type']) ? $_GET['rs_type'] : 'sa'; // sa|vf
$outlets = [];
$has_jenis_rs = table_has_column($conn, 'outlet', 'jenis_rs');
$has_type_outlet = table_has_column($conn, 'outlet', 'type_outlet');
$has_kabupaten = table_has_column($conn, 'outlet', 'kabupaten');
try {
    $base = "SELECT outlet_id, nama_outlet, nomor_rs, id_digipos";
    if ($has_kabupaten) { $base .= ", kabupaten"; }
    else { $base .= ", city"; }
    if ($has_jenis_rs) $base .= ", jenis_rs";
    if ($has_type_outlet) $base .= ", type_outlet";
    $base .= " FROM outlet WHERE 1=1";
    $conds = [];

    // Filter RS Eksekusi SA atau Voucher
    if ($has_jenis_rs) {
        if ($rs_filter === 'sa') { 
            $conds[] = "jenis_rs LIKE '%RS Eksekusi SA%'"; 
        } else { 
            $conds[] = "jenis_rs LIKE '%RS Eksekusi Voucher%'"; 
        }
    } elseif ($has_type_outlet) {
        if ($rs_filter === 'sa') { 
            $conds[] = "type_outlet LIKE '%RS Eksekusi SA%'"; 
        } else { 
            $conds[] = "type_outlet LIKE '%RS Eksekusi Voucher%'"; 
        }
    }

    $sql = $base . (count($conds) ? (' AND ' . implode(' AND ', $conds)) : '') . " ORDER BY nama_outlet";
    // Debug: uncomment to see query
    echo "<!-- Query: " . htmlspecialchars($sql) . " -->";
    $rs = $conn->query($sql);
    while ($row = $rs->fetch_assoc()) { $outlets[] = $row; }
} catch (Exception $e) { /* ignore */ }

// Get products based on RS type
$produk_list = [];
try {
    if ($rs_filter === 'sa') {
        // Produk untuk RS Eksekusi SA: Perdana Internet Lite & ByU
        $stmt = $conn->prepare("SELECT produk_id, nama_produk, harga FROM produk WHERE status='active' AND kategori IN ('Perdana Internet Lite','Perdana Internet ByU') ORDER BY nama_produk");
    } else {
        // Produk untuk RS Eksekusi Voucher: Voucher Fisik Internet
        $stmt = $conn->prepare("SELECT produk_id, nama_produk, harga FROM produk WHERE status='active' AND kategori LIKE '%Voucher%Internet%' ORDER BY nama_produk");
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { 
        $produk_list[] = $row; 
    }
    $stmt->close();
} catch (Exception $e) { /* ignore */ }

$conn->close();

$page_title = 'Pengajuan Stock Eksekusi SA & VF';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-styles.css">
    <style>
        body { font-family: 'Lexend', sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        
        /* Filter Section */
        .filter-section { background: #fff; padding: 20px; margin: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .filter-row { display: flex; gap: 20px; align-items: flex-end; margin-bottom: 20px; }
        .filter-field { display: flex; flex-direction: column; gap: 6px; }
        .filter-field label { font-weight: 600; color: #2c3e50; font-size: 14px; }
        .filter-field input, .filter-field select { padding: 10px 14px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; min-width: 200px; }
        
        /* Section Title */
        .section-title { font-size: 18px; font-weight: 700; color: #2c3e50; margin: 0; }
        
        /* Table Container */
        .table-container { background: #fff; margin: 0 20px 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow-x: auto; overflow-y: visible; }
        
        /* Layout Row for table + sidebar */
        .content-row { display: flex; gap: 20px; align-items: flex-start; margin: 0 20px 20px; }
        .side-summary { background: #fff; width: 280px; padding: 16px 18px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: sticky; top: 100px; height: fit-content; }
        .side-summary .metric { margin: 8px 0; font-size: 14px; }

        /* Data Table */
        table.data-table { border-collapse: collapse; table-layout: auto; display: table; }
        .data-table thead { background: #8B1538; color: #fff; }
        .data-table th { padding: 14px 10px; text-align: left; font-weight: 600; font-size: 13px; white-space: nowrap; position: sticky; top: 0; z-index: 10; min-width: 100px; background: #8B1538; }
        .data-table td { padding: 10px; border-bottom: 1px solid #eef2f4; font-size: 13px; vertical-align: middle; white-space: nowrap; background: #fff; }
        .data-table tbody tr:hover { background: #f8f9fa; }
        .data-table tbody tr:hover td { background: #f8f9fa; }
        .data-table tbody tr.outlet-row:hover { background: #fff; }
        .data-table tbody tr.outlet-row:hover td { background: #fff; }
        
        /* Sticky first 3 columns (RS Eksekusi, No RS, ID DigiPOS) */
        .data-table th:nth-child(1), .data-table td:nth-child(1) { position: sticky; left: 0; z-index: 5; }
        .data-table th:nth-child(1) { z-index: 11; background: #8B1538; }
        .data-table td:nth-child(1) { background: #fff; box-shadow: 2px 0 4px rgba(0,0,0,0.05); }
        .data-table tbody tr:hover td:nth-child(1) { background: #f8f9fa; }
        
        .data-table th:nth-child(2), .data-table td:nth-child(2) { position: sticky; left: 200px; z-index: 5; }
        .data-table th:nth-child(2) { z-index: 11; background: #8B1538; }
        .data-table td:nth-child(2) { background: #fff; box-shadow: 2px 0 4px rgba(0,0,0,0.05); }
        .data-table tbody tr:hover td:nth-child(2) { background: #f8f9fa; }
        
        .data-table th:nth-child(3), .data-table td:nth-child(3) { position: sticky; left: 300px; z-index: 5; }
        .data-table th:nth-child(3) { z-index: 11; background: #8B1538; }
        .data-table td:nth-child(3) { background: #fff; box-shadow: 2px 0 4px rgba(0,0,0,0.05); }
        .data-table tbody tr:hover td:nth-child(3) { background: #f8f9fa; }
        
        /* Dynamic product columns */
        .data-table th.produk-col { min-width: 220px; white-space: normal; position: sticky; top: 0; }
        .data-table td.produk-input { width: 120px; min-width: 120px; }
        
        /* Form Controls */
        .data-table select { padding: 8px 10px; border: 1px solid #dfe4ea; border-radius: 6px; font-size: 13px; width: 100%; min-width: 150px; }
        .data-table input[type="number"] { padding: 8px 10px; border: 1px solid #dfe4ea; border-radius: 6px; font-size: 13px; width: 80px; text-align: right; }
        
        /* Badge */
        .badge { background: #e9ecef; padding: 4px 10px; border-radius: 6px; font-size: 12px; color: #495057; display: inline-block; }
        
        /* Product Row */
        .produk-row { background: #f8f9fa; }
        .produk-row td { padding: 8px 10px; }
        
        /* Buttons */
        .btn { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.3s; }
        .btn-add { background: #28a745; color: #fff; }
        .btn-add:hover { background: #218838; }
        .btn-remove { background: #dc3545; color: #fff; padding: 6px 10px; font-size: 12px; }
        .btn-remove:hover { background: #c82333; }
        .btn-primary { background: linear-gradient(135deg, #8B1538 0%, #C84B31 100%); color: #fff; padding: 12px 24px; font-size: 14px; }
        .btn-primary:hover { opacity: 0.9; }
        
        /* Summary */
        .summary-box { background: #fff; margin: 0 20px 20px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .summary-row { display: flex; gap: 30px; align-items: center; flex-wrap: wrap; }
        .summary-item { font-size: 15px; }
        .summary-item strong { color: #2c3e50; margin-right: 8px; }
        .summary-value { color: #8B1538; font-weight: 700; }
        
        /* Force vertical stacking for this page */
        .page-stack { display: flex; flex-direction: column; gap: 12px; align-items: stretch; width: 85%; max-width: 90%; margin: 0 auto; }
        .page-stack > * { width: 100%; max-width: 100%; }
        .filter-section, .table-container, .summary-box { float: none !important; box-sizing: border-box; margin-left: 0 !important; margin-right: 0 !important; }
        .table-container { width: 100% !important; max-width: 100%; }
        
        /* Popover add-produk on the right of button */
        td.aksi-cell { position: relative; }
        .produk-popover { position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%); width: 380px; background: #fff; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.15); padding: 14px; z-index: 20; border: 1px solid #eef2f4; }
        .produk-popover .row { display: flex; gap: 8px; align-items: center; }
        .produk-popover select { min-width: 230px; padding: 8px 10px; border: 1px solid #dfe4ea; border-radius: 6px; }
        .produk-popover input[type="number"] { width: 90px; padding: 8px 10px; border: 1px solid #dfe4ea; border-radius: 6px; text-align: right; }
        .produk-popover .actions { margin-top: 10px; display: flex; gap: 8px; justify-content: flex-end; }
        .hidden { display:none; }
        
        
        /* Actions */
        .action-buttons { margin-top: 15px; }
        
        /* Empty State */
        .empty-state { text-align: center; padding: 40px; color: #7f8c8d; font-size: 14px; }
    </style>
</head>
<body class="submenu-page">
<header class="dashboard-header">
    <div style="display:flex; align-items:center; gap:15px;">
        <img src="../../assets/images/logo_icon.png" alt="Logo" style="width:50px;height:50px;border-radius:10px;object-fit:contain;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <div>
            <h1 style="margin:0;"><?php echo htmlspecialchars($page_title); ?></h1>
            <div style="font-size:12px; color:#95a5a6;">Metode input sesuai referensi</div>
        </div>
    </div>
    <div class="header-buttons">
        <a href="laporan_setoran_global.php" class="back-button">← Kembali</a>
        <a href="<?php echo BASE_PATH; ?>/logout.php" class="logout-button">Logout</a>
    </div>
</header>

<main class="submenu-main">
    <div class="page-stack">
    <!-- Row 1: Filter only -->
    <div class="filter-section" style="margin: 20px;">
        <div class="filter-row" style="gap: 20px;">
            <div class="filter-field">
                <label>Tanggal Pengajuan</label>
                <input type="date" id="tanggalPengajuan" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="filter-field">
                <label>Pilih Jenis RS Eksekusi</label>
                <select id="filterRsType" onchange="changeRsType()">
                    <option value="sa" <?php echo $rs_filter==='sa'?'selected':''; ?>>RS Eksekusi SA</option>
                    <option value="vf" <?php echo $rs_filter==='vf'?'selected':''; ?>>RS Eksekusi Voucher</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Row 2: Table controls -->
    <div class="table-actions" style="display:flex; justify-content:flex-end; gap:10px; margin: 0 20px 8px;">
        <button class="btn btn-add" onclick="addProductColumn()">+ Tambah Produk</button>
    </div>

    <!-- Row 3: Table only (vertical layout) -->
    <div class="table-container">
        <table class="data-table" id="mainTable">
            <thead>
                <tr>
                    <th style="width: 200px;">RS Eksekusi</th>
                    <th style="width: 100px;">No RS</th>
                    <th style="width: 100px;">ID DigiPOS</th>
                    <th style="width: 120px;">Kabupaten</th>
                    <th style="width: 180px;">Requester</th>
                    <th style="width: 120px;">Cabang</th>
                    <th style="width: 120px;">Jenis</th>
                    <th style="width: 150px;">Warehouse</th>
                    <th class="th-total-qty" style="width: 100px;">Total Qty</th>
                    <th class="th-total-saldo" style="width: 130px;">Total Saldo</th>
                    <th class="th-aksi" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
<?php if (!empty($outlets)): ?>
<?php foreach ($outlets as $outlet): ?>
                <tr data-outlet-id="<?php echo $outlet['outlet_id']; ?>" class="outlet-row">
                    <td><strong><?php echo htmlspecialchars($outlet['nama_outlet']); ?></strong></td>
                    <td><?php echo htmlspecialchars($outlet['nomor_rs'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($outlet['id_digipos'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($has_kabupaten ? ($outlet['kabupaten'] ?? '-') : ($outlet['city'] ?? '-')); ?></td>
                    <td>
                        <select class="requester-select" onchange="updateCabang(this)">
                            <option value="">- Pilih -</option>
<?php foreach ($requester_list as $req): ?>
                            <option value="<?php echo $req['reseller_id']; ?>" 
                                data-cabang-id="<?php echo $req['cabang_id'] ?? 0; ?>" 
                                data-cabang="<?php echo htmlspecialchars($cabang_map[$req['cabang_id']] ?? '-'); ?>">
                                <?php echo htmlspecialchars($req['nama_reseller']); ?>
                                <?php if (!empty($req['kategori'])): ?>
                                    (<?php echo htmlspecialchars($req['kategori']); ?>)
                                <?php endif; ?>
                            </option>
<?php endforeach; ?>
                        </select>
                    </td>
                    <td class="cabang-cell"><span class="badge">-</span></td>
                    <td>
                        <select class="jenis-select">
                            <option value="NGRS">NGRS</option>
                            <option value="LinkAja">LinkAja</option>
                            <option value="Finpay">Finpay</option>
                        </select>
                    </td>
                    <td>
                        <select class="warehouse-select">
                            <option value="">- Pilih -</option>
<?php foreach ($cabang_list as $cab): ?>
                            <option value="<?php echo $cab['cabang_id']; ?>"><?php echo htmlspecialchars($cab['nama_cabang']); ?></option>
<?php endforeach; ?>
                        </select>
                    </td>
                    <td class="total-qty" style="font-weight:700; color:#2c3e50;">0</td>
                    <td class="total-saldo" style="font-weight:700; color:#8B1538;">Rp 0</td>
                    <td class="aksi-cell">-</td>
                </tr>
<?php endforeach; ?>
<?php else: ?>
                <tr>
                    <td colspan="11" class="empty-state">
                        Tidak ada RS Eksekusi <?php echo $rs_filter === 'sa' ? 'SA' : 'Voucher'; ?> ditemukan.
                    </td>
                </tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Row 3: Grand totals below table -->
    <div class="summary-box">
        <div class="summary-row">
            <div class="summary-item">
                <strong>Grand Total Qty:</strong>
                <span class="summary-value" id="grandTotalQty">0</span>
            </div>
            <div class="summary-item">
                <strong>Grand Total Saldo:</strong>
                <span class="summary-value" id="grandTotalSaldo">Rp 0</span>
            </div>
        </div>
    </div>

    <!-- Row 4: Submit button at the bottom -->
    <div class="summary-box" style="margin-top: 0;">
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="simpanPengajuan()">💾 Simpan Pengajuan (Preview)</button>
        </div>
    </div>
    </div>
</main>

<script>
// Produk list dari PHP
const produkList = <?php echo json_encode($produk_list); ?>;
const rsType = '<?php echo $rs_filter; ?>';

function rupiah(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function changeRsType() {
    const val = document.getElementById('filterRsType').value;
    window.location.href = '?rs_type=' + val;
}

function updateCabang(select) {
    const row = select.closest('tr');
    const opt = select.options[select.selectedIndex];
    const cabang = opt.getAttribute('data-cabang') || '-';
    const cabangCell = row.querySelector('.cabang-cell');
    cabangCell.innerHTML = '<span class="badge">' + cabang + '</span>';
}

function addProdukRow(btn) {
    const outletRow = btn.closest('tr');
    const outletId = outletRow.getAttribute('data-outlet-id');
    
    // Create new product row
    const newRow = document.createElement('tr');
    newRow.className = 'produk-row';
    newRow.setAttribute('data-parent-outlet', outletId);
    
    let produkOptions = '<option value="">- Pilih Produk -</option>';
    produkList.forEach(p => {
        produkOptions += `<option value="${p.produk_id}" data-harga="${p.harga}">${p.nama_produk}</option>`;
    });
    
    newRow.innerHTML = `
        <td colspan="8">
            <div style="display:flex; gap:10px; align-items:center; padding-left:20px;">
                <span style="color:#7f8c8d;">Produk:</span>
                <select class="produk-select" style="min-width:300px;" onchange="updateProdukInfo(this)">
                    ${produkOptions}
                </select>
                <span style="color:#7f8c8d;">Qty:</span>
                <input type="number" class="qty-input" min="0" value="0" style="width:100px;" 
                    data-harga="0" oninput="recalculateRow(this)">
                <span class="harga-info" style="color:#7f8c8d; font-size:12px;">HPP: Rp 0</span>
            </div>
        </td>
        <td class="produk-qty" style="text-align:center; font-weight:600;">0</td>
        <td class="produk-saldo" style="text-align:left; font-weight:600; color:#8B1538;">Rp 0</td>
        <td>
            <button class="btn btn-remove" onclick="removeProdukRow(this)">✕ Hapus</button>
        </td>
    `;
    
    // Insert after outlet row
    outletRow.insertAdjacentElement('afterend', newRow);
}

// Dynamic product columns (header dropdown + qty cells)
let productColSeq = 0;

function productSelectOptions() {
    let html = '<option value="">- Pilih Produk -</option>';
    produkList.forEach(p => { html += `<option value="${p.produk_id}" data-harga="${p.harga}">${p.nama_produk}</option>`; });
    return html;
}

function addProductColumn(){
    const table = document.getElementById('mainTable');
    const headRow = table.tHead.rows[0];
    const colId = 'pcol-' + (++productColSeq);

    // Build header cell with select and remove button
    const th = document.createElement('th');
    th.className = 'produk-col';
    th.setAttribute('data-col-id', colId);
    th.innerHTML = `<div style="display:flex; align-items:center; gap:6px;">
        <select class="produk-header-select" onchange="onProductHeaderChange(this)">${productSelectOptions()}</select>
        <button class="btn btn-remove" title="Hapus kolom" onclick="removeProductColumn(this)">✕</button>
    </div>`;

    // Insert before total qty column
    const thTotalQty = table.querySelector('th.th-total-qty');
    headRow.insertBefore(th, thTotalQty);

    // Add TD to each row before total qty cell
    document.querySelectorAll('#tableBody tr.outlet-row').forEach(row => {
        const td = document.createElement('td');
        td.className = 'produk-input';
        td.setAttribute('data-col-id', colId);
        td.innerHTML = `<input type="number" class="qty-input" min="0" value="0" data-produk-id="" data-harga="0" oninput="recalcRow(this)">`;
        const sumCell = row.querySelector('.total-qty');
        row.insertBefore(td, sumCell);
    });
}

function onProductHeaderChange(sel){
    const th = sel.closest('th');
    const colId = th.getAttribute('data-col-id');
    const prodId = sel.value;
    const harga = parseFloat(sel.options[sel.selectedIndex]?.getAttribute('data-harga')||0);
    // Update all inputs under this column
    document.querySelectorAll(`td[data-col-id="${colId}"] .qty-input`).forEach(inp => {
        inp.setAttribute('data-produk-id', prodId);
        inp.setAttribute('data-harga', harga);
        // Reset values when product changes
        if (parseInt(inp.value||'0')>0){ inp.value = 0; }
    });
    // Recalc all rows because product price changed
    document.querySelectorAll('#tableBody tr.outlet-row').forEach(r => {
        const any = r.querySelectorAll('input.qty-input');
        if (any.length) {
            // trigger recalculation by building totals from scratch
            let totQty=0, totNom=0;
            r.querySelectorAll('.qty-input').forEach(el=>{ const q=parseInt(el.value||'0'); const h=parseFloat(el.getAttribute('data-harga')||'0'); if(q>0){totQty+=q; totNom+=q*h;} });
            r.querySelector('.total-qty').textContent = totQty.toLocaleString('id-ID');
            r.querySelector('.total-saldo').textContent = rupiah(totNom);
        }
    });
    updateGrandTotals();
}

function removeProductColumn(btn){
    const th = btn.closest('th');
    const table = document.getElementById('mainTable');
    const colId = th.getAttribute('data-col-id');
    th.remove();
    document.querySelectorAll(`td[data-col-id="${colId}"]`).forEach(td=> td.remove());
    updateGrandTotals();
}

function updateProdukInfo(select) {
    const row = select.closest('tr');
    const opt = select.options[select.selectedIndex];
    const harga = parseFloat(opt.getAttribute('data-harga') || 0);
    const qtyInput = row.querySelector('.qty-input');
    const hargaInfo = row.querySelector('.harga-info');
    
    qtyInput.setAttribute('data-harga', harga);
    hargaInfo.textContent = 'HPP: ' + rupiah(harga);
    
    recalculateRow(qtyInput);
}

function recalcRow(input) {
    const row = input.closest('tr');
    // Recalc this outlet row's totals from all product columns
    let totQty = 0;
    let totNom = 0;
    row.querySelectorAll('.qty-input').forEach(el => {
        const q = parseInt(el.value || '0');
        const h = parseFloat(el.getAttribute('data-harga') || '0');
        if (q > 0) {
            totQty += q;
            totNom += (q * h);
        }
    });
    row.querySelector('.total-qty').textContent = totQty.toLocaleString('id-ID');
    row.querySelector('.total-saldo').textContent = rupiah(totNom);
    updateGrandTotals();
}

function recalculateRow(input) {
    const row = input.closest('tr');
    const qty = parseInt(input.value || 0);
    const harga = parseFloat(input.getAttribute('data-harga') || 0);
    const saldo = qty * harga;
    
    row.querySelector('.produk-qty').textContent = qty;
    row.querySelector('.produk-saldo').textContent = rupiah(saldo);
    
    // Update outlet row totals
    const outletId = row.getAttribute('data-parent-outlet');
    updateOutletTotals(outletId);
}

function updateOutletTotals(outletId) {
    const outletRow = document.querySelector(`tr[data-outlet-id="${outletId}"]`);
    const produkRows = document.querySelectorAll(`tr[data-parent-outlet="${outletId}"]`);
    
    let totalQty = 0;
    let totalSaldo = 0;
    
    produkRows.forEach(row => {
        const qty = parseInt(row.querySelector('.produk-qty').textContent || 0);
        const saldoText = row.querySelector('.produk-saldo').textContent.replace(/[^0-9]/g, '');
        const saldo = parseInt(saldoText || 0);
        
        totalQty += qty;
        totalSaldo += saldo;
    });
    
    outletRow.querySelector('.total-qty').textContent = totalQty;
    outletRow.querySelector('.total-saldo').textContent = rupiah(totalSaldo);
    
    updateGrandTotals();
}

function updateGrandTotals() {
    let grandQty = 0;
    let grandSaldo = 0;
    
    document.querySelectorAll('.outlet-row').forEach(row => {
        const qty = parseInt(row.querySelector('.total-qty').textContent || 0);
        const saldoText = row.querySelector('.total-saldo').textContent.replace(/[^0-9]/g, '');
        const saldo = parseInt(saldoText || 0);
        
        grandQty += qty;
        grandSaldo += saldo;
    });
    
    document.getElementById('grandTotalQty').textContent = grandQty.toLocaleString('id-ID');
    document.getElementById('grandTotalSaldo').textContent = rupiah(grandSaldo);
}

function removeProdukRow(btn) {
    const row = btn.closest('tr');
    const outletId = row.getAttribute('data-parent-outlet');
    row.remove();
    updateOutletTotals(outletId);
}

function simpanPengajuan() {
    const tanggal = document.getElementById('tanggalPengajuan').value;
    const data = {
        tanggal: tanggal,
        rs_type: rsType,
        items: []
    };
    
    document.querySelectorAll('.outlet-row').forEach(outletRow => {
        const outletId = outletRow.getAttribute('data-outlet-id');
        const requester = outletRow.querySelector('.requester-select').value;
        const jenis = outletRow.querySelector('.jenis-select').value;
        const warehouse = outletRow.querySelector('.warehouse-select').value;
        
        // Get all product rows for this outlet
        const produkRows = document.querySelectorAll(`tr[data-parent-outlet="${outletId}"]`);
        
        if (produkRows.length > 0 && requester && warehouse) {
            const produkData = [];
            produkRows.forEach(pRow => {
                const produkSelect = pRow.querySelector('.produk-select');
                const qtyInput = pRow.querySelector('.qty-input');
                const produkId = produkSelect.value;
                const qty = parseInt(qtyInput.value || 0);
                
                if (produkId && qty > 0) {
                    produkData.push({
                        produk_id: produkId,
                        produk_name: produkSelect.options[produkSelect.selectedIndex].text,
                        qty: qty,
                        harga: parseFloat(qtyInput.getAttribute('data-harga'))
                    });
                }
            });
            
            if (produkData.length > 0) {
                data.items.push({
                    outlet_id: outletId,
                    outlet_name: outletRow.children[0].textContent.trim(),
                    no_rs: outletRow.children[1].textContent.trim(),
                    id_digipos: outletRow.children[2].textContent.trim(),
                    kabupaten: outletRow.children[3].textContent.trim(),
                    requester_id: requester,
                    jenis: jenis,
                    warehouse_id: warehouse,
                    produk: produkData
                });
            }
        }
    });
    
    if (data.items.length === 0) {
        alert('Tidak ada data yang diinput. Pastikan telah mengisi Requester, Warehouse, dan menambahkan produk dengan qty > 0.');
        return;
    }
    
    console.log('Data Pengajuan:', data);
    alert(`Preview Pengajuan:\n\nTanggal: ${tanggal}\nJenis: ${rsType === 'sa' ? 'RS Eksekusi SA' : 'RS Eksekusi Voucher'}\nTotal Outlet: ${data.items.length}\n\nData siap disimpan ke database.`);
    
    // TODO: Send to server via AJAX
    // Example:
    // fetch('save_pengajuan.php', {
    //     method: 'POST',
    //     headers: {'Content-Type': 'application/json'},
    //     body: JSON.stringify(data)
    // }).then(response => response.json())
    //   .then(result => { ... });
}
</script>
</body>
</html>
