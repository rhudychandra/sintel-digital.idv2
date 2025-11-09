# Import Admin Database Update

## 🎯 Problem
Tombol Administrator sudah muncul, tapi saat diklik halaman kosong/tidak menampilkan apa-apa.

## 🔍 Root Cause
File `database_update_admin.sql` belum diimport ke database. File ini berisi:
- Tabel `cabang` (branches)
- Tabel `reseller`
- View `view_admin_dashboard` (untuk statistik)
- View lainnya untuk reporting
- Sample data

## ✅ Solution - Import database_update_admin.sql

### Metode 1: Via phpMyAdmin (RECOMMENDED)

1. **Buka phpMyAdmin**
   - URL: `http://localhost/phpmyadmin`

2. **Pilih Database**
   - Klik: `sinar_telkom_dashboard`

3. **Import File**
   - Klik tab **"Import"**
   - Klik **"Choose File"**
   - Pilih: `database_update_admin.sql`
   - Klik **"Go"**

4. **Wait for Completion**
   - Import akan memakan waktu beberapa detik
   - Tunggu sampai muncul pesan "Import has been successfully finished"

5. **Verify Tables Created**
   - Klik tab "Structure"
   - Check tabel baru:
     - ✅ `cabang`
     - ✅ `reseller`
     - ✅ `audit_log`
   - Check views:
     - ✅ `view_admin_dashboard`
     - ✅ `view_sales_per_cabang`
     - ✅ `view_stock_per_cabang`
     - ✅ `view_reseller_performance`

### Metode 2: Via MySQL Command Line

```bash
mysql -u root -p sinar_telkom_dashboard < database_update_admin.sql
```

### Metode 3: Via phpMyAdmin SQL Tab

1. Buka phpMyAdmin
2. Pilih database: `sinar_telkom_dashboard`
3. Klik tab "SQL"
4. Buka file `database_update_admin.sql` dengan text editor
5. Copy semua isinya
6. Paste ke SQL tab
7. Klik "Go"

## 🔄 Setelah Import:

### Step 1: Verify Tables
```sql
-- Check tabel cabang
SELECT * FROM cabang LIMIT 5;

-- Check tabel reseller
SELECT * FROM reseller LIMIT 5;

-- Check view dashboard
SELECT * FROM view_admin_dashboard;
```

**Expected Results:**
- `cabang`: 5 rows (Jakarta, Bandung, Surabaya, Medan, Denpasar)
- `reseller`: 5 rows (RSL001 - RSL005)
- `view_admin_dashboard`: 1 row dengan statistik

### Step 2: Test Admin Panel

1. **Buka Admin Panel**
   - URL: `http://localhost/sinartelekomdashboardsystem/admin/`
   - Atau klik tombol "Administrator" di dashboard

2. **Verify Dashboard Loads**
   - Harus tampil:
     - ✅ Sidebar dengan 8 menu
     - ✅ Statistics cards (6 cards)
     - ✅ Quick Actions
     - ✅ Menu Administrator

3. **Check Statistics Cards:**
   - Total Cabang: 5
   - Total Reseller: 5
   - Total Users: (jumlah users)
   - Total Produk: (jumlah produk)
   - Total Penjualan: Rp xxx
   - Total Stock: xxx

### Step 3: Test Each Menu

1. **Produk** - Klik menu Produk
   - Harus tampil tabel produk
   - Tombol: Tambah Produk, Edit, Delete

2. **Cabang** - Klik menu Cabang
   - Harus tampil tabel cabang (5 rows)
   - Tombol: Tambah Cabang, Edit, Delete

3. **Users** - Klik menu Users
   - Harus tampil tabel users
   - Tombol: Tambah User, Edit, Delete

4. **Reseller** - Klik menu Reseller
   - Harus tampil tabel reseller (5 rows)
   - Tombol: Tambah Reseller, Edit, Delete

5. **Penjualan** - Klik menu Penjualan
   - Harus tampil view penjualan

6. **Stock** - Klik menu Stock
   - Harus tampil view stock per cabang

7. **Grafik** - Klik menu Grafik
   - Harus tampil grafik penjualan

## 📋 What database_update_admin.sql Contains:

### 1. New Tables:
- **cabang** - Data cabang/branches
- **reseller** - Data reseller
- **audit_log** - Log aktivitas admin

### 2. Table Updates:
- **users** - Tambah kolom `cabang_id`
- **produk** - Tambah kolom `cabang_id`
- **inventory** - Tambah kolom `cabang_id`
- **penjualan** - Tambah kolom `cabang_id` dan `reseller_id`

### 3. Views:
- **view_admin_dashboard** - Statistik untuk dashboard
- **view_sales_per_cabang** - Penjualan per cabang
- **view_stock_per_cabang** - Stock per cabang
- **view_reseller_performance** - Performance reseller

### 4. Stored Procedures:
- **sp_get_sales_by_daterange** - Get sales by date range

### 5. Sample Data:
- 5 Cabang (Jakarta, Bandung, Surabaya, Medan, Denpasar)
- 5 Reseller (RSL001 - RSL005)
- 1 Super Administrator user

### 6. Indexes:
- Performance indexes untuk query optimization

## ✅ Verification Checklist:

- [ ] Import `database_update_admin.sql` berhasil
- [ ] Tabel `cabang` ada (5 rows)
- [ ] Tabel `reseller` ada (5 rows)
- [ ] Tabel `audit_log` ada
- [ ] View `view_admin_dashboard` ada
- [ ] Kolom `cabang_id` ada di tabel `users`
- [ ] Kolom `cabang_id` ada di tabel `produk`
- [ ] Kolom `cabang_id` ada di tabel `inventory`
- [ ] Kolom `cabang_id` dan `reseller_id` ada di tabel `penjualan`
- [ ] Buka admin panel
- [ ] Dashboard menampilkan statistics cards
- [ ] Semua menu bisa diklik
- [ ] Data cabang tampil
- [ ] Data reseller tampil

## 🐛 Troubleshooting:

### Error: Table already exists

Jika muncul error "Table 'cabang' already exists":
```sql
-- Drop tables dulu
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS reseller;
DROP TABLE IF EXISTS cabang;

-- Lalu import ulang database_update_admin.sql
```

### Error: Column already exists

Jika muncul error "Duplicate column name 'cabang_id'":
- Abaikan error ini
- Lanjutkan import
- Atau edit file SQL, hapus baris ALTER TABLE yang error

### Error: View already exists

Jika muncul error "View already exists":
```sql
-- Drop views dulu
DROP VIEW IF EXISTS view_admin_dashboard;
DROP VIEW IF EXISTS view_sales_per_cabang;
DROP VIEW IF EXISTS view_stock_per_cabang;
DROP VIEW IF EXISTS view_reseller_performance;

-- Lalu import ulang
```

### Admin Panel Masih Kosong

Jika setelah import admin panel masih kosong:

1. **Check View:**
```sql
SELECT * FROM view_admin_dashboard;
```

Jika error, recreate view:
```sql
CREATE OR REPLACE VIEW view_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM cabang WHERE status = 'active') as total_cabang,
    (SELECT COUNT(*) FROM reseller WHERE status = 'active') as total_reseller,
    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
    (SELECT COUNT(*) FROM produk) as total_produk,
    (SELECT SUM(total_harga) FROM penjualan) as total_penjualan,
    (SELECT SUM(stok) FROM inventory) as total_stok;
```

2. **Check PHP Errors:**
- Enable error reporting di config.php
- Check browser console untuk errors
- Check Apache error log

3. **Clear Browser Cache:**
- Ctrl + Shift + Delete
- Clear cache & cookies
- Refresh page (Ctrl + F5)

## 🚀 Quick Commands:

```bash
# Import database update
mysql -u root -p sinar_telkom_dashboard < database_update_admin.sql

# Verify tables
mysql -u root -p sinar_telkom_dashboard -e "SHOW TABLES;"

# Check cabang data
mysql -u root -p sinar_telkom_dashboard -e "SELECT * FROM cabang;"

# Check reseller data
mysql -u root -p sinar_telkom_dashboard -e "SELECT * FROM reseller;"

# Check view
mysql -u root -p sinar_telkom_dashboard -e "SELECT * FROM view_admin_dashboard;"
```

## 📝 Summary:

**Problem:** Admin panel tidak menampilkan apa-apa

**Root Cause:** Database belum di-update dengan tabel dan view yang diperlukan

**Solution:** Import `database_update_admin.sql`

**Result:** 
- ✅ Tabel cabang & reseller dibuat
- ✅ View dashboard dibuat
- ✅ Admin panel menampilkan data
- ✅ Semua fitur admin berfungsi

---

**File:** `database_update_admin.sql`  
**Method:** Import via phpMyAdmin  
**Time:** ~30 seconds  
**Difficulty:** Easy ⭐
