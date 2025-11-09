# 🔧 Panduan Fix Menu Administrator

## 📋 Ringkasan Masalah

Menu Administrator (Penjualan, Stock, dan Grafik) menampilkan **blank screen** karena:
1. Tabel `cabang` dan `reseller` belum ada di database
2. View `view_admin_dashboard` dan `view_sales_per_cabang` belum dibuat
3. Query JOIN menggunakan tabel yang tidak exist

## ✅ Solusi yang Telah Dibuat

### File yang Dibuat/Diperbaiki:

1. **fix_admin_menu_tables.sql** (NEW)
   - Membuat tabel `cabang` (kantor cabang)
   - Membuat tabel `reseller` (mitra reseller)
   - Menambahkan kolom `cabang_id` ke tabel existing
   - Membuat view untuk admin panel
   - Insert sample data

2. **admin/penjualan.php** (UPDATED)
   - Menambahkan error handling
   - Query yang lebih robust dengan COALESCE
   - Menampilkan pesan error jika tabel belum ada

3. **admin/stock.php** (UPDATED)
   - Menambahkan error handling
   - Query untuk menampilkan stock terbaru per produk
   - Fallback jika tabel cabang belum ada

4. **admin/grafik.php** (UPDATED)
   - Menambahkan error handling
   - Fallback query jika view belum ada
   - Menampilkan data performance per cabang

5. **admin/index.php** (UPDATED)
   - Menambahkan error handling untuk dashboard statistics
   - Fallback manual calculation jika view belum ada
   - Menampilkan warning jika ada masalah

## 📝 Cara Menggunakan

### Step 1: Import File SQL

**Via phpMyAdmin (RECOMMENDED):**

1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Login dengan user root
3. Pilih database: `sinar_telkom_dashboard`
4. Klik tab **"Import"**
5. Klik **"Choose File"**
6. Pilih file: `fix_admin_menu_tables.sql`
7. Scroll ke bawah
8. Klik **"Go"**
9. Tunggu hingga muncul pesan sukses

**Via MySQL Command Line:**

```bash
mysql -u root -p sinar_telkom_dashboard < fix_admin_menu_tables.sql
```

### Step 2: Verify Database

Setelah import, verify bahwa tabel sudah dibuat:

```sql
-- Check tabel cabang
SELECT * FROM cabang;

-- Check tabel reseller
SELECT * FROM reseller;

-- Check view
SELECT * FROM view_admin_dashboard;
SELECT * FROM view_sales_per_cabang;
```

### Step 3: Test Admin Panel

1. **Login ke sistem**
   - URL: `http://localhost/sinartelekomdashboardsystem/`
   - Username: `admin` atau `rhudychandra`
   - Password: `password` atau `Tsel2025`

2. **Klik tombol "Administrator"** (jika sudah ada role administrator)

3. **Test setiap menu:**
   - ✅ Dashboard → Harus menampilkan statistik
   - ✅ Produk → Sudah berfungsi
   - ✅ Cabang → Sudah berfungsi
   - ✅ Users → Sudah berfungsi
   - ✅ Reseller → Sudah berfungsi
   - ✅ **Penjualan** → Sekarang menampilkan data (tidak blank)
   - ✅ **Stock** → Sekarang menampilkan data (tidak blank)
   - ✅ **Grafik** → Sekarang menampilkan data (tidak blank)

## 📊 Struktur Tabel Baru

### Tabel: cabang

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| cabang_id | INT | Primary Key |
| kode_cabang | VARCHAR(20) | Kode unik cabang |
| nama_cabang | VARCHAR(100) | Nama cabang |
| alamat | TEXT | Alamat lengkap |
| kota | VARCHAR(100) | Kota |
| provinsi | VARCHAR(100) | Provinsi |
| phone | VARCHAR(20) | Nomor telepon |
| email | VARCHAR(100) | Email cabang |
| status | ENUM | active/inactive |

**Sample Data:**
- JKT-01: Cabang Jakarta Pusat
- JKT-02: Cabang Jakarta Selatan
- BDG-01: Cabang Bandung
- SBY-01: Cabang Surabaya
- MDN-01: Cabang Medan

### Tabel: reseller

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| reseller_id | INT | Primary Key |
| kode_reseller | VARCHAR(20) | Kode unik reseller |
| nama_reseller | VARCHAR(100) | Nama reseller |
| nama_perusahaan | VARCHAR(150) | Nama perusahaan |
| cabang_id | INT | Foreign Key ke cabang |
| komisi_persen | DECIMAL(5,2) | Persentase komisi |
| status | ENUM | active/inactive/suspended |

**Sample Data:**
- RSL-001: Budi Reseller (Jakarta)
- RSL-002: Siti Network (Jakarta)
- RSL-003: Ahmad Telkom (Bandung)
- RSL-004: Dewi Connect (Surabaya)
- RSL-005: Joko Network (Medan)

## 🔍 View yang Dibuat

### 1. view_admin_dashboard
Menampilkan statistik untuk dashboard admin:
- Total cabang
- Total reseller
- Total users
- Total produk
- Total penjualan
- Total stock

### 2. view_sales_per_cabang
Menampilkan performance penjualan per cabang:
- Kode dan nama cabang
- Total transaksi
- Total penjualan
- Jumlah reseller
- Jumlah produk

### 3. view_stock_per_cabang
Menampilkan stock inventory per cabang:
- Data produk per cabang
- Total stock
- Nilai stock

### 4. view_reseller_performance
Menampilkan performance reseller:
- Data reseller
- Total transaksi
- Total penjualan
- Total komisi

## 🎯 Fitur Error Handling

Semua file admin sekarang memiliki error handling yang baik:

1. **Try-Catch Block**
   - Menangkap error database
   - Menampilkan pesan error yang informatif

2. **Fallback Query**
   - Jika view tidak ada, gunakan query manual
   - Jika tabel tidak ada, tampilkan pesan error

3. **User-Friendly Messages**
   - Pesan error yang jelas
   - Instruksi untuk fix masalah
   - Link ke file SQL yang perlu dijalankan

## ✅ Checklist Setelah Fix

- [ ] File SQL berhasil di-import tanpa error
- [ ] Tabel `cabang` sudah ada dan berisi 5 sample data
- [ ] Tabel `reseller` sudah ada dan berisi 5 sample data
- [ ] View `view_admin_dashboard` sudah dibuat
- [ ] View `view_sales_per_cabang` sudah dibuat
- [ ] Menu **Penjualan** tidak blank screen lagi
- [ ] Menu **Stock** tidak blank screen lagi
- [ ] Menu **Grafik** tidak blank screen lagi
- [ ] Dashboard menampilkan statistik dengan benar
- [ ] Tidak ada error di console browser

## 🐛 Troubleshooting

### Problem 1: Error saat import SQL

**Error:** `Table 'cabang' already exists`

**Solution:**
```sql
-- Drop tabel jika sudah ada
DROP TABLE IF EXISTS reseller;
DROP TABLE IF EXISTS cabang;

-- Lalu jalankan ulang fix_admin_menu_tables.sql
```

### Problem 2: Menu masih blank screen

**Check:**
1. Apakah SQL sudah di-import?
2. Apakah ada error di browser console? (F12)
3. Apakah PHP error reporting aktif?

**Solution:**
```php
// Tambahkan di awal file PHP untuk debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Problem 3: Data tidak muncul

**Check:**
```sql
-- Verify data ada
SELECT COUNT(*) FROM cabang;
SELECT COUNT(*) FROM reseller;
SELECT COUNT(*) FROM penjualan;
```

**Solution:**
Jika data kosong, insert sample data lagi dari file SQL.

## 📞 Support

Jika masih ada masalah:

1. Check file log PHP: `C:/xampp/php/logs/php_error_log`
2. Check MySQL error log: `C:/xampp/mysql/data/*.err`
3. Pastikan semua service XAMPP running
4. Restart Apache dan MySQL

## 🎉 Hasil Akhir

Setelah mengikuti panduan ini:

✅ Menu Administrator lengkap dan berfungsi
✅ Penjualan menampilkan data transaksi
✅ Stock menampilkan data inventory
✅ Grafik menampilkan performance cabang
✅ Dashboard menampilkan statistik akurat
✅ Error handling yang baik
✅ User experience yang smooth

---

**File Created:** 2024
**Status:** Ready to Use
**Difficulty:** Easy ⭐
