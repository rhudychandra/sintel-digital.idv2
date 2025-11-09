# 📋 TODO: Development Menu Inventory Administrator

## Status: 🚧 IN PROGRESS

### Checklist Pengembangan:

#### Phase 1: File Utama ✅
- [ ] Buat file `admin/inventory.php`
  - [ ] Setup authentication & authorization
  - [ ] Dashboard statistik inventory
  - [ ] Riwayat transaksi dengan tabel
  - [ ] Filter (tanggal, cabang, produk, tipe)
  - [ ] Pagination
  - [ ] Responsive design

#### Phase 2: Update Sidebar 🔄
- [ ] Update sidebar di `admin/index.php`
- [ ] Update sidebar di `admin/produk.php`
- [ ] Update sidebar di `admin/cabang.php`
- [ ] Update sidebar di `admin/users.php`
- [ ] Update sidebar di `admin/reseller.php`
- [ ] Update sidebar di `admin/penjualan.php`
- [ ] Update sidebar di `admin/stock.php`
- [ ] Update sidebar di `admin/grafik.php`

#### Phase 3: Testing 🧪
- [ ] Test filter tanggal
- [ ] Test filter cabang
- [ ] Test filter produk
- [ ] Test filter tipe transaksi
- [ ] Test pagination
- [ ] Test responsive design (mobile/tablet)
- [ ] Test dengan data banyak
- [ ] Test dengan data kosong

#### Phase 4: Dokumentasi 📝
- [ ] Buat dokumentasi penggunaan
- [ ] Update README jika perlu
- [ ] Screenshot fitur

---

## Fitur yang Akan Diimplementasikan:

### 1. Dashboard Inventory
- ✅ Total Transaksi (bulan ini)
- ✅ Total Stok Masuk (bulan ini)
- ✅ Total Stok Keluar (bulan ini)
- ✅ Total Nilai Inventory
- ✅ Produk Low Stock Alert
- ✅ Grafik pergerakan stok

### 2. Riwayat Transaksi
- ✅ Tabel lengkap dengan kolom:
  - ID Transaksi
  - Tanggal
  - Cabang
  - Produk
  - Kategori
  - Tipe (Masuk/Keluar)
  - Jumlah
  - Stok Sebelum
  - Stok Sesudah
  - Referensi (No Invoice)
  - Keterangan
  - User
- ✅ Badge warna (hijau=masuk, merah=keluar)
- ✅ Sorting by date DESC

### 3. Filter & Search
- ✅ Filter Range Tanggal (Start - End)
- ✅ Filter Cabang (dropdown)
- ✅ Filter Produk (dropdown)
- ✅ Filter Tipe Transaksi (Semua/Masuk/Keluar)
- ✅ Button Reset Filter

### 4. Pagination
- ✅ Limit 50 records per page
- ✅ Navigation (Previous/Next)
- ✅ Page numbers
- ✅ Total records info

### 5. UI/UX
- ✅ Consistent dengan admin panel design
- ✅ Responsive (mobile-friendly)
- ✅ Loading states
- ✅ Empty states
- ✅ Error handling

---

## Database Queries:

### Query Statistik:
```sql
SELECT 
    COUNT(*) as total_transaksi,
    SUM(CASE WHEN tipe_transaksi='masuk' THEN jumlah ELSE 0 END) as total_masuk,
    SUM(CASE WHEN tipe_transaksi='keluar' THEN jumlah ELSE 0 END) as total_keluar,
    SUM(CASE WHEN tipe_transaksi='masuk' THEN jumlah * p.harga ELSE 0 END) as nilai_masuk,
    SUM(CASE WHEN tipe_transaksi='keluar' THEN jumlah * p.harga ELSE 0 END) as nilai_keluar
FROM inventory i
LEFT JOIN produk p ON i.produk_id = p.produk_id
WHERE MONTH(i.tanggal) = MONTH(CURRENT_DATE())
AND YEAR(i.tanggal) = YEAR(CURRENT_DATE());
```

### Query Riwayat dengan Filter:
```sql
SELECT 
    i.inventory_id,
    i.tanggal,
    i.tipe_transaksi,
    i.jumlah,
    i.stok_sebelum,
    i.stok_sesudah,
    i.referensi,
    i.keterangan,
    p.nama_produk,
    p.kategori,
    p.harga,
    COALESCE(c.nama_cabang, '-') as nama_cabang,
    u.full_name as user_name
FROM inventory i
LEFT JOIN produk p ON i.produk_id = p.produk_id
LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
LEFT JOIN users u ON i.user_id = u.user_id
WHERE 1=1
    AND i.tanggal BETWEEN ? AND ?
    [AND i.cabang_id = ?]
    [AND i.produk_id = ?]
    [AND i.tipe_transaksi = ?]
ORDER BY i.tanggal DESC, i.inventory_id DESC
LIMIT ? OFFSET ?;
```

### Query Low Stock:
```sql
SELECT 
    p.produk_id,
    p.nama_produk,
    p.stok,
    p.kategori,
    c.nama_cabang
FROM produk p
LEFT JOIN inventory i ON p.produk_id = i.produk_id
LEFT JOIN cabang c ON i.cabang_id = c.cabang_id
WHERE p.stok < 10
AND p.status = 'active'
GROUP BY p.produk_id, c.cabang_id
ORDER BY p.stok ASC
LIMIT 10;
```

---

## File Structure:

```
admin/
├── inventory.php (NEW) ⭐
│   ├── Authentication check
│   ├── Dashboard section
│   ├── Filter form
│   ├── Riwayat table
│   └── Pagination
├── stock.php (EXISTING)
└── ... (other admin files)
```

---

## Notes:

- Menu Inventory di admin panel berbeda dengan `inventory.php` user
- Admin panel fokus pada **monitoring & reporting**
- User inventory fokus pada **input transaksi**
- Keduanya menggunakan tabel `inventory` yang sama
- Administrator bisa melihat **semua cabang**
- Design konsisten dengan admin panel yang ada

---

**Created:** <?php echo date('Y-m-d H:i:s'); ?>
**Status:** In Progress
**Target Completion:** Today
