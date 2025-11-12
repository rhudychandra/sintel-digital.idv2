<!-- Sales Table -->
<div class="table-container" id="detail-penjualan">
    <div class="table-header">
        <h2>📋 Detail Penjualan</h2>
        <div class="export-buttons">
            <div class="print-controls" style="display: inline-flex; align-items: center; gap: 10px; margin-right: 10px; flex-wrap: wrap;">
                <label style="font-size:12px; color:#555; display:flex; align-items:center; gap:6px;">
                    Orientasi
                    <select id="print-orientation" style="padding:4px 6px; border:1px solid #ddd; border-radius:6px;">
                        <option value="landscape" selected>Landscape</option>
                        <option value="portrait">Portrait</option>
                    </select>
                </label>
                <label style="font-size:12px; color:#555; display:flex; align-items:center; gap:6px;">
                    Skala (%)
                    <input type="number" id="print-scale" min="30" max="100" step="5" placeholder="Auto" style="width:70px; padding:4px 6px; border:1px solid #ddd; border-radius:6px;" />
                </label>
            </div>
            <button onclick="exportToExcel()" class="btn-export btn-excel">
                📊 Export Excel
            </button>
            <button onclick="exportToPDF()" class="btn-export btn-pdf">
                📄 Export PDF
            </button>
            <button onclick="printReport()" class="btn-export btn-print">
                🖨️ Print
            </button>
        </div>
    </div>
    
    <div style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">
        Periode: <?php echo date('d/m/Y', strtotime($filter_start_date)); ?> - <?php echo date('d/m/Y', strtotime($filter_end_date)); ?>
        <?php if (!$is_admin_or_manager && $user_cabang_id): ?>
            <?php
            $cabang_info = $conn->query("SELECT nama_cabang FROM cabang WHERE cabang_id = " . intval($user_cabang_id));
            if ($cabang_info && $cabang_row = $cabang_info->fetch_assoc()) {
                echo " | Cabang: " . htmlspecialchars($cabang_row['nama_cabang']);
            }
            ?>
        <?php elseif (!empty($filter_cabang)): ?>
            <?php
            $cabang_info = $conn->query("SELECT nama_cabang FROM cabang WHERE cabang_id = " . intval($filter_cabang));
            if ($cabang_info && $cabang_row = $cabang_info->fetch_assoc()) {
                echo " | Cabang: " . htmlspecialchars($cabang_row['nama_cabang']);
            }
            ?>
        <?php endif; ?>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>No Invoice</th>
                    <th>Reseller</th>
                    <th>Cabang</th>
                    <th style="text-align: center;">Items</th>
                    <th style="text-align: right;">Subtotal</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: center;">Status</th>
                    <th>Metode</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $grand_total = 0;
                if ($sales_data && $sales_data->num_rows > 0) {
                    while ($row = $sales_data->fetch_assoc()): 
                        $grand_total += $row['total'];
                        $status_class = 'badge-' . $row['status_pembayaran'];
                        $status_text = ucfirst($row['status_pembayaran']);
                        
                        // Status icons
                        $status_icon = '';
                        switch($row['status_pembayaran']) {
                            case 'paid': $status_icon = '✅'; break;
                            case 'pending': $status_icon = '⏳'; break;
                            case 'cancelled': $status_icon = '❌'; break;
                        }
                        
                        // Payment method icons
                        $payment_icon = '';
                        switch($row['metode_pembayaran']) {
                            case 'cash': $payment_icon = '💵'; break;
                            case 'transfer': $payment_icon = '🏦'; break;
                            case 'credit_card': $payment_icon = '💳'; break;
                            case 'debit_card': $payment_icon = '💳'; break;
                            case 'e-wallet': $payment_icon = '📱'; break;
                        }
                ?>
                <tr>
                    <td style="text-align: center;"><?php echo $no++; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_penjualan'])); ?></td>
                    <td>
                        <strong style="color: #667eea;">
                            <?php echo htmlspecialchars($row['no_invoice']); ?>
                        </strong>
                    </td>
                    <td><?php echo htmlspecialchars($row['nama_reseller']); ?></td>
                    <td>
                        <span style="background: #e9ecef; padding: 4px 10px; border-radius: 8px; font-size: 12px;">
                            <?php echo htmlspecialchars($row['nama_cabang']); ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <strong style="color: #667eea;"><?php echo number_format($row['total_items']); ?></strong>
                    </td>
                    <td style="text-align: right;">
                        Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?>
                    </td>
                    <td style="text-align: right;">
                        <strong style="color: #27ae60;">
                            Rp <?php echo number_format($row['total'], 0, ',', '.'); ?>
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        <span class="<?php echo $status_class; ?>">
                            <?php echo $status_icon; ?> <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td>
                        <small style="display: flex; align-items: center; gap: 5px;">
                            <?php echo $payment_icon; ?>
                            <?php echo ucfirst(str_replace('_', ' ', $row['metode_pembayaran'])); ?>
                        </small>
                    </td>
                </tr>
                <?php 
                    endwhile;
                } else { 
                ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 60px; color: #7f8c8d;">
                        <div style="font-size: 64px; margin-bottom: 15px;">📭</div>
                        <strong style="font-size: 18px; display: block; margin-bottom: 8px;">
                            Tidak ada data penjualan
                        </strong>
                        <small>Coba ubah filter atau range tanggal untuk melihat data</small>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
            <?php if ($sales_data && $sales_data->num_rows > 0): ?>
            <tfoot>
                <tr style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); font-weight: 600;">
                    <td colspan="7" style="text-align: right; padding: 20px; font-size: 16px;">
                        <strong>GRAND TOTAL:</strong>
                    </td>
                    <td colspan="3" style="color: #27ae60; font-size: 18px; padding: 20px;">
                        <strong>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></strong>
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
