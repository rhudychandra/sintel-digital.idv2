# 📋 Rencana Pengembangan inventory.php (Root)

## 🎯 Tujuan
Menambahkan fitur baru ke `inventory.php` untuk melengkapi sistem inventory management:
1. **Stock Keluar** - Form pengeluaran stok (non-penjualan)
2. **Stock** - Monitoring stok real-time per produk/cabang
3. **Laporan Penjualan** - Laporan penjualan yang lebih detail dan lengkap

---

## 📊 Analisis File Saat Ini

### Fitur yang Sudah Ada:
- ✅ Dashboard dengan grafik penjualan per reseller
- ✅ Input Barang (Stok Masuk)
- ✅ Input Penjualan (Multiple Products)
- ✅ Laporan Penjualan Basic (di halaman input_penjualan)

### Menu Sidebar Saat Ini:
1. Dashboard
2. Input Barang
3. Input Penjualan

---

## 🆕 Fitur Baru yang Akan Ditambahkan

### 1. **Stock Keluar** (Halaman Baru)
**Tujuan:** Input pengeluaran stok untuk keperluan selain penjualan

**Use Cases:**
- Stok rusak/expired
- Stok hilang
- Stok untuk promosi/sample
- Stok untuk internal use
- Return ke supplier

**Form Fields:**
- Tanggal
- Cabang (auto untuk admin, dropdown untuk administrator/staff)
- Produk (dropdown)
- Quantity
- Alasan Pengeluaran (dropdown: Rusak, Hilang, Promosi, Internal, Return, Lainnya)
- Keterangan (textarea)

**Backend Logic:**
```php
1. Validasi stok mencukupi
2. Update stok produk (kurangi)
3. Insert ke tabel inventory dengan tipe_transaksi='keluar'
4. Set referensi = 'KELUAR-YYYYMMDD-XXXX'
5. Set keterangan dengan alasan
```

**Database Query:**
```sql
-- Check stock
SELECT stok FROM produk WHERE produk_id = ?

-- Update stock
UPDATE produk SET stok = stok - ? WHERE produk_id = ?

-- Insert inventory
INSERT INTO inventory (
    produk_id, tanggal, tipe_transaksi, jumlah, 
    stok_sebelum, stok_sesudah, referensi, keterangan, 
    user_id, cabang_id
) VALUES (?, ?, 'keluar', ?, ?, ?, ?, ?, ?, ?)
```

---

### 2. **Stock** (Halaman Baru)
**Tujuan:** Monitoring stok real-time dengan filter dan search

**Fitur:**
- Tabel stok semua produk
- Filter by Cabang
- Filter by Kategori
- Search by Nama Produk
- Status Stok (Low/Medium/Good)
- Export capability (future)

**Kolom Tabel:**
- Kode Produk
- Nama Produk
- Kategori
- Cabang
- Stok Saat Ini
- Status (Badge: Red/Yellow/Green)
- Harga
- Nilai Total (Stok × Harga)
- Last Update

**Status Logic:**
```
Low Stock: stok < 10 (Red Badge)
Medium Stock: stok 10-49 (Yellow Badge)
Good Stock: stok >= 50 (Green Badge)
```

**Database Query:**
```sql
SELECT 
    p.produk_id,
    p.kode_produk,
    p.nama_produk,
    p.kategori,
    p.stok,
    p.harga,
    (p.stok * p.harga) as nilai_total,
    COALESCE(c.nama_cabang, '-') as nama_cabang,
    MAX(i.tanggal) as last_update
FROM produk p
LEFT JOIN inventory i ON p.produk_id = i.produk_id
LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
WHERE p.status = 'active'
    [AND i.cabang_id = ?]
    [AND p.kategori = ?]
    [AND p.nama_produk LIKE ?]
GROUP BY p.produk_id, c.cabang_id
ORDER BY p.nama_produk
```

**Summary Cards:**
- Total Produk
- Total Nilai Stok
- Produk Low Stock
- Produk Out of Stock

---

### 3. **Laporan Penjualan** (Enhanced)
**Tujuan:** Laporan penjualan yang lebih detail dengan analisis

**Fitur:**
- Filter Range Tanggal
- Filter Cabang
- Filter Reseller
- Filter Status Pembayaran
- Summary Statistics
- Detail per Invoice
- Grafik Penjualan

**Summary Cards:**
- Total Penjualan (Rp)
- Total Transaksi
- Rata-rata per Transaksi
- Total Produk Terjual

**Tabel Detail:**
- No Invoice
- Tanggal
- Reseller
- Cabang
- Total Items
- Subtotal
- Status Pembayaran
- Metode Pembayaran
- Action (View Detail)

**Modal Detail Invoice:**
- Header: Invoice info
- Tabel: List produk
- Footer: Total

**Database Query:**
```sql
-- Summary
SELECT 
    COUNT(DISTINCT p.penjualan_id) as total_transaksi,
    SUM(p.total) as total_penjualan,
    AVG(p.total) as rata_rata,
    SUM(dp.jumlah) as total_produk_terjual
FROM penjualan p
LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
WHERE p.tanggal_penjualan BETWEEN ? AND ?
    [AND p.reseller_id = ?]
    [AND p.status_pembayaran = ?]

-- Detail List
SELECT 
    p.penjualan_id,
    p.no_invoice,
    p.tanggal_penjualan,
    r.nama_reseller,
    c.nama_cabang,
    COUNT(dp.detail_id) as total_items,
    p.subtotal,
    p.total,
    p.status_pembayaran,
    p.metode_pembayaran
FROM penjualan p
LEFT JOIN reseller r ON p.reseller_id = r.reseller_id
LEFT JOIN users u ON p.user_id = u.user_id
LEFT JOIN cabang c ON u.cabang_id = c.cabang_id
LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
WHERE p.tanggal_penjualan BETWEEN ? AND ?
GROUP BY p.penjualan_id
ORDER BY p.tanggal_penjualan DESC
```

---

## 🎨 UI/UX Design

### Menu Sidebar (Updated):
```
📊 Dashboard
📥 Input Barang (Stok Masuk)
📤 Stock Keluar (NEW)
💰 Input Penjualan
📦 Stock (NEW)
📋 Laporan Penjualan (NEW)
```

### Color Scheme:
- **Primary:** #667eea (Purple)
- **Success:** #27ae60 (Green)
- **Danger:** #e74c3c (Red)
- **Warning:** #f39c12 (Orange)
- **Info:** #3498db (Blue)

### Responsive Design:
- Desktop: Full layout
- Tablet: Adjusted grid
- Mobile: Stacked layout

---

## 🔐 Security & Validation

### Input Validation:
- ✅ Required fields check
- ✅ Numeric validation (qty, harga)
- ✅ Date validation
- ✅ Stock availability check
- ✅ SQL injection prevention (prepared statements)

### Authorization:
- **Admin:** Hanya bisa akses data cabangnya sendiri
- **Administrator/Staff:** Bisa akses semua cabang

---

## 📝 Implementation Steps

### Phase 1: Stock Keluar
1. ✅ Tambah menu "Stock Keluar" di sidebar
2. ✅ Buat form input stock keluar
3. ✅ Implement backend logic
4. ✅ Validasi stok
5. ✅ Update inventory table
6. ✅ Success/error handling

### Phase 2: Stock Monitoring
1. ✅ Tambah menu "Stock" di sidebar
2. ✅ Buat summary cards
3. ✅ Buat tabel stock dengan filter
4. ✅ Implement search functionality
5. ✅ Add status badges
6. ✅ Responsive design

### Phase 3: Laporan Penjualan
1. ✅ Tambah menu "Laporan Penjualan" di sidebar
2. ✅ Buat filter form
3. ✅ Buat summary statistics
4. ✅ Buat tabel laporan
5. ✅ Implement modal detail
6. ✅ Add export button (future)

### Phase 4: Testing
1. ✅ Test semua form input
2. ✅ Test filter & search
3. ✅ Test validasi
4. ✅ Test responsive design
5. ✅ Test dengan berbagai role user

---

## 📊 Database Impact

### Tables Used:
- `inventory` - Insert/Select
- `produk` - Update/Select
- `penjualan` - Select
- `detail_penjualan` - Select
- `reseller` - Select
- `cabang` - Select
- `users` - Select

### No New Tables Required
Semua fitur menggunakan tabel yang sudah ada.

---

## 🚀 Expected Results

### User Benefits:
1. **Stock Keluar:** Tracking lengkap untuk semua jenis pengeluaran stok
2. **Stock:** Real-time monitoring stok dengan alert
3. **Laporan Penjualan:** Analisis penjualan yang lebih detail

### Business Benefits:
1. Better inventory control
2. Reduced stock discrepancies
3. Improved reporting
4. Better decision making

---

## 📈 Future Enhancements

### Phase 5 (Future):
- Export to Excel/PDF
- Print functionality
- Email notifications
- Barcode scanning
- Stock forecasting
- Automated reorder points

---

## 📞 Notes

- Maintain consistency dengan design yang ada
- Follow existing code patterns
- Ensure backward compatibility
- Document all changes
- Test thoroughly before deployment

---

**Created:** <?php echo date('Y-m-d H:i:s'); ?>
**Status:** Ready for Implementation
**Priority:** High
