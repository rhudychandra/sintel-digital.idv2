<!-- Info Box -->
<div class="info-box">
    <h3>ℹ️ Informasi Laporan</h3>
    <ul>
        <li><strong>Laporan Penjualan:</strong> Menampilkan data penjualan sesuai filter yang dipilih</li>
        <li><strong>Filter Cabang:</strong> 
            <?php if ($is_admin_or_manager): ?>
                Administrator dan Manager dapat melihat semua cabang
            <?php else: ?>
                Anda hanya dapat melihat data cabang Anda sendiri
            <?php endif; ?>
        </li>
        <li><strong>Total Penjualan:</strong> Dihitung dari semua transaksi dalam periode yang dipilih</li>
        <li><strong>Export Data:</strong> Tersedia dalam format Excel, PDF, dan Print</li>
        <li><strong>Grafik Analisis:</strong> Menampilkan trend penjualan dan distribusi metode pembayaran</li>
        <li><strong>Quick Select:</strong> Gunakan tombol preset untuk memilih periode dengan cepat</li>
    </ul>
</div>
