<?php // Partial: Input Barang view (expects variables from parent scope)
?>
<?php if (!in_array($user['role'], ['administrator', 'manager', 'finance'])): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 30px; border-radius: 12px; text-align: center;">
        <h2 style="margin: 0 0 10px 0;">🚫 Akses Ditolak</h2>
        <p>Anda tidak memiliki akses ke halaman Input Barang.</p>
        <p>Hanya role <strong>Administrator, Manager, dan Finance</strong> yang dapat mengakses halaman ini.</p>
        <a href="?page=dashboard" class="btn-add" style="margin-top: 20px; display: inline-block;">← Kembali ke Dashboard</a>
    </div>
<?php else: ?>
<div class="form-container">
    <h2>📥 Form Input Barang</h2>
    <form method="POST">
        <input type="hidden" name="action" value="input_barang">
        
        <div class="form-group">
            <label>Tanggal</label>
            <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
    <?php if (in_array($user['role'], ['administrator', 'manager'])): ?>
        <!-- Dropdown Cabang untuk Administrator & Manager (Semua Cabang) -->
        <div class="form-group">
            <label>Cabang</label>
            <select name="cabang_id" required>
                <option value="">-- Pilih Cabang --</option>
                <?php 
                if ($cabang_list) {
                    $cabang_list->data_seek(0);
                    while ($c = $cabang_list->fetch_assoc()): 
                ?>
                    <option value="<?php echo $c['cabang_id']; ?>">
                        <?php echo htmlspecialchars($c['nama_cabang']); ?>
                    </option>
                <?php endwhile; } ?>
            </select>
        </div>
        <?php else: ?>
    <!-- Readonly Cabang untuk Finance (non admin/manager) -->
        <div class="form-group">
            <label>Cabang</label>
            <input type="text" value="<?php 
                $stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
                $stmt->bind_param("i", $user['cabang_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $cabang = $result->fetch_assoc();
                    echo htmlspecialchars($cabang['nama_cabang']);
                } else {
                    echo 'Cabang tidak ditemukan';
                }
            ?>" readonly style="background: #f8f9fa; cursor: not-allowed;">
            <small style="color: #7f8c8d; font-size: 12px;">Cabang otomatis sesuai dengan akun Anda</small>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>Produk</label>
            <select name="produk_id" required>
                <option value="">-- Pilih Produk --</option>
                <?php 
                if ($products) {
                    $products->data_seek(0);
                    while ($p = $products->fetch_assoc()): 
                ?>
                    <option value="<?php echo $p['produk_id']; ?>">
                        <?php echo htmlspecialchars($p['nama_produk']); ?> (Stok: <?php echo $p['stok']; ?>)
                    </option>
                <?php endwhile; } ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="qty" min="1" required placeholder="Masukkan jumlah">
        </div>
        
        <div class="form-group">
            <label>Alasan Barang Masuk</label>
            <select name="alasan_masuk" required>
                <option value="">-- Pilih Alasan --</option>
                <option value="DO Cluster">DO Cluster</option>
                <option value="Supplier">Supplier</option>
                <option value="Stock Masuk">Stock Masuk</option>
                <option value="Return">Return</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Keterangan Tambahan</label>
            <textarea name="keterangan" rows="3" placeholder="Keterangan detail (opsional)"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">💾 Simpan</button>
            <a href="?page=dashboard" class="btn-cancel">❌ Batal</a>
        </div>
    </form>
</div>

<!-- Riwayat Input Barang -->
<div style="margin-top: 40px;">
    <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
        <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 20px;">📋 Riwayat Input Barang</h2>
        <form method="GET" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="input_barang">
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Mulai</label>
                    <input type="date" name="input_start" value="<?php echo $input_barang_start_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Tanggal Akhir</label>
                    <input type="date" name="input_end" value="<?php echo $input_barang_end_date; ?>" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                </div>
                <div>
                    <button type="submit" class="btn-add">🔍 Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #2c3e50;">Data Input Barang</h3>
            <div style="display: flex; gap: 10px;">
                <button onclick="exportInputToExcel()" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                    📊 Excel
                </button>
                <button onclick="exportInputToCSV()" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                    📄 CSV
                </button>
                <button onclick="exportInputToPDF()" style="background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                    📕 PDF
                </button>
                <button onclick="printInputBarang()" style="background: #9b59b6; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                    🖨️ Print
                </button>
            </div>
        </div>
        <table class="data-table" id="inputBarangTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Referensi</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Cabang</th>
                    <th>Qty</th>
                    <th>Nilai</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_qty = 0;
                $total_nilai = 0;
                
                if ($input_barang_data && $input_barang_data->num_rows > 0) {
                    while ($row = $input_barang_data->fetch_assoc()) { 
                        $nilai = $row['jumlah'] * $row['harga'];
                        $total_qty += $row['jumlah'];
                        $total_nilai += $nilai;
                        
                        // Extract alasan from keterangan
                        $alasan = '-';
                        if (preg_match('/Alasan: ([^|]+)/', $row['keterangan'], $matches)) {
                            $alasan = trim($matches[1]);
                        }
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                    <td><strong style="color: #667eea; font-size: 12px;"><?php echo htmlspecialchars($row['referensi']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                    <td><span style="font-size: 12px;"><?php echo htmlspecialchars($row['kategori']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['nama_cabang'] ?? '-'); ?></td>
                    <td style="text-align: center;"><strong><?php echo number_format($row['jumlah']); ?></strong></td>
                    <td><strong style="color: #27ae60;">Rp <?php echo number_format($nilai, 0, ',', '.'); ?></strong></td>
                    <td><span style="font-size: 12px;"><?php echo htmlspecialchars($alasan); ?></span></td>
                    <td>
                        <?php 
                        $status = $row['status_approval'];
                        $badge_color = '';
                        $badge_bg = '';
                        
                        switch($status) {
                            case 'approved':
                                $badge_color = '#27ae60';
                                $badge_bg = '#d4edda';
                                $status_text = '✅ Approved';
                                break;
                            case 'pending':
                                $badge_color = '#f39c12';
                                $badge_bg = '#fff3cd';
                                $status_text = '⏳ Pending';
                                break;
                            case 'rejected':
                                $badge_color = '#e74c3c';
                                $badge_bg = '#f8d7da';
                                $status_text = '❌ Rejected';
                                break;
                            default:
                                $badge_color = '#7f8c8d';
                                $badge_bg = '#e9ecef';
                                $status_text = $status;
                        }
                        ?>
                        <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td><small><?php echo htmlspecialchars($row['user_name']); ?></small></td>
                </tr>
                <?php 
                    }
                } else { ?>
                <tr>
                    <td colspan="11" style="text-align: center; padding: 30px; color: #7f8c8d;">
                        Tidak ada data input barang untuk periode ini
                    </td>
                </tr>
                <?php } ?>
            </tbody>
            <?php if ($input_barang_data && $input_barang_data->num_rows > 0): ?>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: 600;">
                    <td colspan="6" style="text-align: right; padding: 15px;">TOTAL:</td>
                    <td style="text-align: center;"><strong><?php echo number_format($total_qty); ?></strong></td>
                    <td style="color: #27ae60; font-size: 16px;"><strong>Rp <?php echo number_format($total_nilai, 0, ',', '.'); ?></strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
// Export Input Barang to Excel
function exportInputToExcel() {
    const table = document.getElementById('inputBarangTable');
    if (!table) {
        alert('Tidak ada data untuk di-export');
        return;
    }
    
    let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    html += '<head><meta charset="utf-8"><style>table {border-collapse: collapse;} th, td {border: 1px solid #ddd; padding: 8px;}</style></head>';
    html += '<body>';
    html += '<h2>Riwayat Input Barang</h2>';
    html += '<p>Periode: <?php echo date("d/m/Y", strtotime($input_barang_start_date)); ?> - <?php echo date("d/m/Y", strtotime($input_barang_end_date)); ?></p>';
    html += '<table>' + table.innerHTML + '</table>';
    html += '</body></html>';
    
    const blob = new Blob(['\ufeff', html], { type: 'application/vnd.ms-excel' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'Riwayat_Input_Barang_' + new Date().toISOString().slice(0,10) + '.xls';
    link.click();
    window.URL.revokeObjectURL(url);
}

// Export Input Barang to CSV
function exportInputToCSV() {
    const table = document.getElementById('inputBarangTable');
    if (!table) {
        alert('Tidak ada data untuk di-export');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'Riwayat_Input_Barang_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
    window.URL.revokeObjectURL(url);
}

// Export Input Barang to PDF
function exportInputToPDF() {
    window.print();
}

// Print Input Barang
function printInputBarang() {
    const printContent = document.getElementById('inputBarangTable').outerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write('<html><head><title>Riwayat Input Barang</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
    printWindow.document.write('h2 { color: #2c3e50; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
    printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }');
    printWindow.document.write('th { background: #667eea; color: white; font-weight: 600; }');
    printWindow.document.write('tfoot { background: #f8f9fa; font-weight: 600; }');
    printWindow.document.write('@media print { button { display: none; } }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2>Riwayat Input Barang</h2>');
    printWindow.document.write('<p><strong>Periode:</strong> <?php echo date("d/m/Y", strtotime($input_barang_start_date)); ?> - <?php echo date("d/m/Y", strtotime($input_barang_end_date)); ?></p>');
    printWindow.document.write('<p><strong>Dicetak:</strong> ' + new Date().toLocaleString('id-ID') + '</p>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.print();
}
</script>

<?php endif; ?>
