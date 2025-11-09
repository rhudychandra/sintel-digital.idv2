# 🧪 Panduan Testing Inventory Enhancement

## 📋 Testing Checklist

### Prerequisites:
- ✅ XAMPP Apache & MySQL running
- ✅ Database `sinar_telkom_dashboard` sudah ada
- ✅ User sudah login ke sistem

---

## 🔍 Testing Steps

### 1. Test Stock Keluar (`inventory_stock_keluar.php`)

#### A. Test Form Input:
1. Buka: `http://localhost/sinartelekomdashboardsystem/inventory_stock_keluar.php`
2. Login jika belum
3. Isi form:
   - Tanggal: Pilih tanggal hari ini
   - Cabang: Pilih cabang (jika administrator) atau otomatis (jika admin)
   - Produk: Pilih produk yang ada stoknya
   - Quantity: Masukkan angka (pastikan < stok tersedia)
   - Alasan: Pilih salah satu (Rusak, Hilang, dll)
   - Keterangan: Isi keterangan tambahan
4. Klik "💾 Simpan Stock Keluar"
5. **Expected Result:**
   - ✅ Muncul pesan sukses dengan nomor referensi
   - ✅ Stok produk berkurang di database
   - ✅ Data muncul di tabel riwayat

#### B. Test Validasi:
1. Coba input quantity > stok tersedia
2. **Expected Result:**
   - ❌ Muncul error "Stok tidak mencukupi!"

#### C. Test Filter Riwayat:
1. Ubah tanggal mulai dan akhir
2. Klik "🔍 Filter"
3. **Expected Result:**
   - ✅ Tabel menampilkan data sesuai range tanggal

---

### 2. Test Stock Monitoring (`inventory_stock.php`)

#### A. Test Summary Cards:
1. Buka: `http://localhost/sinartelekomdashboardsystem/inventory_stock.php`
2. Periksa 4 cards di atas:
   - Total Produk
   - Total Nilai Stok
   - Low Stock (<10)
   - Out of Stock
3. **Expected Result:**
   - ✅ Angka sesuai dengan data di database

#### B. Test Filter Cabang:
1. Pilih cabang dari dropdown
2. Klik "🔍 Terapkan Filter"
3. **Expected Result:**
   - ✅ Tabel hanya menampilkan produk dari cabang tersebut

#### C. Test Filter Kategori:
1. Pilih kategori dari dropdown
2. Klik "🔍 Terapkan Filter"
3. **Expected Result:**
   - ✅ Tabel hanya menampilkan produk kategori tersebut

#### D. Test Search:
1. Ketik nama produk di search box
2. Klik "🔍 Terapkan Filter"
3. **Expected Result:**
   - ✅ Tabel menampilkan produk yang match

#### E. Test Status Badges:
1. Periksa kolom "Status" di tabel
2. **Expected Result:**
   - ✅ Badge merah untuk stok = 0
   - ✅ Badge kuning untuk stok < 10
   - ✅ Badge biru untuk stok 10-49
   - ✅ Badge hijau untuk stok ≥ 50

---

### 3. Test Laporan Penjualan (`inventory_laporan.php`)

#### A. Test Summary Statistics:
1. Buka: `http://localhost/sinartelekomdashboardsystem/inventory_laporan.php`
2. Periksa 4 cards:
   - Total Penjualan (Rp)
   - Total Transaksi
   - Rata-rata per Transaksi
   - Total Produk Terjual
3. **Expected Result:**
   - ✅ Angka sesuai dengan data penjualan

#### B. Test Filter Tanggal:
1. Set tanggal mulai dan akhir
2. Klik "🔍 Terapkan Filter"
3. **Expected Result:**
   - ✅ Data sesuai range tanggal
   - ✅ Summary cards update

#### C. Test Filter Reseller:
1. Pilih reseller dari dropdown
2. Klik "🔍 Terapkan Filter"
3. **Expected Result:**
   - ✅ Hanya menampilkan penjualan reseller tersebut

#### D. Test Filter Status:
1. Pilih status (Paid/Pending/Cancelled)
2. Klik "🔍 Terapkan Filter"
3. **Expected Result:**
   - ✅ Hanya menampilkan transaksi dengan status tersebut

#### E. Test Grand Total:
1. Periksa footer tabel
2. **Expected Result:**
   - ✅ Grand total = sum semua total di tabel

---

## 🔄 Integration Testing

### Test Navigation Between Pages:
1. Mulai dari `inventory.php?page=dashboard`
2. Klik menu "Stock Keluar" → Harus ke `inventory_stock_keluar.php`
3. Klik menu "Stock" → Harus ke `inventory_stock.php`
4. Klik menu "Laporan Penjualan" → Harus ke `inventory_laporan.php`
5. Klik menu "Dashboard" → Harus kembali ke `inventory.php?page=dashboard`
6. **Expected Result:**
   - ✅ Semua link berfungsi
   - ✅ Active state menu benar
   - ✅ Tidak ada broken links

### Test Data Consistency:
1. Input stock keluar di `inventory_stock_keluar.php`
2. Cek di `inventory_stock.php` → Stok harus berkurang
3. Cek di `admin/inventory.php` → Transaksi harus muncul
4. **Expected Result:**
   - ✅ Data konsisten di semua halaman

---

## 📱 Responsive Testing

### Desktop (>1024px):
- [ ] Layout full width
- [ ] Stats grid 4 kolom
- [ ] Tabel tidak scroll horizontal
- [ ] Form layout horizontal

### Tablet (768-1024px):
- [ ] Stats grid 2 kolom
- [ ] Tabel scroll horizontal jika perlu
- [ ] Form masih readable

### Mobile (<768px):
- [ ] Stats grid 1 kolom (stacked)
- [ ] Tabel scroll horizontal
- [ ] Form stacked vertical
- [ ] Sidebar collapsible (jika ada)

---

## 🐛 Common Issues & Solutions

### Issue 1: "Produk tidak ditemukan"
**Solution:**
- Check apakah produk status = 'active'
- Verify produk_id exists di database

### Issue 2: "Stok tidak mencukupi"
**Solution:**
- Check stok di tabel produk
- Pastikan qty input <= stok tersedia

### Issue 3: Filter tidak bekerja
**Solution:**
- Check URL parameters
- Verify SQL query dengan filter
- Check browser console untuk errors

### Issue 4: Data tidak muncul
**Solution:**
- Check apakah ada data di database
- Verify date range filter
- Check SQL query di PHP error log

---

## 📊 Database Verification

### Check Stock Keluar Records:
```sql
SELECT * FROM inventory 
WHERE tipe_transaksi = 'keluar' 
AND referensi LIKE 'KELUAR-%'
ORDER BY tanggal DESC 
LIMIT 10;
```

### Check Stock Levels:
```sql
SELECT 
    nama_produk, 
    stok,
    CASE 
        WHEN stok = 0 THEN 'Out of Stock'
        WHEN stok < 10 THEN 'Low Stock'
        WHEN stok < 50 THEN 'Medium'
        ELSE 'Good'
    END as status
FROM produk 
WHERE status = 'active'
ORDER BY stok ASC;
```

### Check Sales Summary:
```sql
SELECT 
    COUNT(*) as total_transaksi,
    SUM(total) as total_penjualan,
    AVG(total) as rata_rata
FROM penjualan
WHERE tanggal_penjualan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
```

---

## ✅ Acceptance Criteria

### Stock Keluar:
- ✅ Form dapat disubmit
- ✅ Stok berkurang otomatis
- ✅ Referensi unik ter-generate
- ✅ Data tersimpan ke inventory table
- ✅ Riwayat dapat difilter

### Stock Monitoring:
- ✅ Summary cards akurat
- ✅ Filter berfungsi semua
- ✅ Status badges sesuai logic
- ✅ Tabel responsive
- ✅ Data real-time

### Laporan Penjualan:
- ✅ Summary statistics benar
- ✅ Filter lengkap berfungsi
- ✅ Grand total akurat
- ✅ Tabel detail lengkap
- ✅ UI/UX user-friendly

---

## 🎓 Best Practices

### For Testing:
1. Test dengan data real (bukan dummy)
2. Test semua role user (admin, administrator, staff)
3. Test edge cases (stok 0, data kosong, dll)
4. Test di berbagai browser (Chrome, Firefox, Edge)
5. Test responsive di berbagai device

### For Deployment:
1. Backup database sebelum deploy
2. Test di staging environment dulu
3. Monitor error logs setelah deploy
4. Collect user feedback
5. Iterate based on feedback

---

**Last Updated:** <?php echo date('d F Y H:i:s'); ?>  
**Status:** Ready for Testing  
**Priority:** High
