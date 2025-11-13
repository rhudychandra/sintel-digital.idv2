# Cara Menjalankan Reset Database di Hostinger

## ⚠️ PERHATIAN PENTING

**Script reset ini akan menghapus SEMUA data transaksi secara PERMANEN!**
- Semua penjualan
- Semua detail penjualan  
- Semua inventory records
- Semua pelanggan
- Reset stock produk ke 0

**Yang DIPERTAHANKAN:**
- Master produk
- Master cabang
- Master reseller
- User accounts
- Kategori produk

---

## 📋 Persiapan Sebelum Reset

### 1. Backup Database Terlebih Dahulu

**Via Hostinger hPanel:**
```
1. Login ke Hostinger hPanel
2. Klik "Databases" → pilih database Anda
3. Klik "Manage" → Tab "Backup/Export"
4. Klik "Export" → Download file .sql
5. Simpan file backup di tempat aman
```

**Via phpMyAdmin:**
```
1. Login ke phpMyAdmin di Hostinger
2. Pilih database Anda
3. Klik tab "Export"
4. Pilih "Quick" atau "Custom"
5. Klik "Go" untuk download
```

### 2. Siapkan Password Kuat

Buat password kuat untuk proteksi script reset, contoh:
- `SinarTelekom2025!Reset@123`
- `ResetDB#2025$Secure`

---

## 🚀 Langkah-Langkah Eksekusi

### Metode 1: Via phpMyAdmin dengan File SQL (PALING AMAN - DISARANKAN)

#### A. Download File SQL

File `reset_all_data.sql` sudah tersedia di:
```
database/migrations/reset_all_data.sql
```

#### B. Eksekusi via phpMyAdmin

1. **Login ke phpMyAdmin**
   ```
   Hostinger hPanel → Databases → pilih database → "Enter phpMyAdmin"
   ```

2. **Import File SQL**
   - Pilih database Anda di sidebar kiri
   - Klik tab **"SQL"** (bukan Import!)
   - Buka file `reset_all_data.sql` di text editor
   - Copy SEMUA isi file
   - Paste ke SQL query box di phpMyAdmin
   - Klik **"Go"** untuk eksekusi

3. **Lihat Hasil**
   - Akan muncul pesan sukses untuk setiap query
   - Di bagian bawah akan muncul tabel verifikasi
   - Semua kolom "Total" harusnya menunjukkan **0**

4. **✅ Selesai!**
   - Tidak perlu hapus file (SQL aman disimpan di server)
   - Tidak ada security risk
   - Bisa digunakan lagi kapan saja

---

### Metode 2: Via phpMyAdmin (Manual Query)

Jika tidak ingin menggunakan file PHP, bisa jalankan query SQL langsung:

1. **Login ke phpMyAdmin**
   ```
   Hostinger hPanel → Databases → pilih DB → "Enter phpMyAdmin"
   ```

2. **Jalankan Query Berikut (Copy-Paste satu per satu):**

   ```sql
   -- 1. Disable foreign key checks
   SET FOREIGN_KEY_CHECKS = 0;

   -- 2. Delete all detail_penjualan
   DELETE FROM detail_penjualan;

   -- 3. Delete all penjualan
   DELETE FROM penjualan;

   -- 4. Delete all inventory records
   DELETE FROM inventory;

   -- 5. Reset all product stock to 0
   UPDATE produk SET stok = 0;

   -- 6. Delete all pelanggan
   DELETE FROM pelanggan;

   -- 7. Re-enable foreign key checks
   SET FOREIGN_KEY_CHECKS = 1;
   ```

3. **Verifikasi Hasil:**
   ```sql
   SELECT COUNT(*) as total FROM penjualan;
   SELECT COUNT(*) as total FROM detail_penjualan;
   SELECT COUNT(*) as total FROM inventory;
   SELECT COUNT(*) as total FROM pelanggan;
   SELECT COUNT(*) as total FROM produk WHERE stok > 0;
   ```
   
   Semua harusnya menunjukkan 0.

---

### Metode 3: Via SSH/Terminal (Untuk Advanced User)

Jika Hostinger plan Anda support SSH:

```bash
# 1. SSH ke server
ssh u123456789@yourdomain.com

# 2. Masuk ke folder project
cd public_html/database/migrations/

# 3. Jalankan script
php reset_all_data_complete.php

# 4. Lihat output hasil reset
```

---

## 🔍 Verifikasi Setelah Reset

### 1. Cek via Database

Login ke phpMyAdmin, jalankan query:
```sql
-- Cek total records (harusnya 0)
SELECT 'penjualan' as tabel, COUNT(*) as total FROM penjualan
UNION ALL
SELECT 'detail_penjualan', COUNT(*) FROM detail_penjualan
UNION ALL
SELECT 'inventory', COUNT(*) FROM inventory
UNION ALL
SELECT 'pelanggan', COUNT(*) FROM pelanggan;

-- Cek stock produk (harusnya semua 0)
SELECT nama_produk, stok 
FROM produk 
WHERE stok > 0;
```

### 2. Cek via Website

1. Login ke dashboard admin
2. Buka menu **Laporan Setoran Global**
3. Verifikasi semua nilai menunjukkan 0 atau "Rp 0"
4. Buka **Inventory** → Stock semua produk harusnya 0
5. Buka **Penjualan** → Tidak ada data transaksi

---

## 🛡️ Security Checklist

- [ ] Sudah backup database sebelum reset
- [ ] Menggunakan password kuat di `run_reset_online.php`
- [ ] File `run_reset_online.php` sudah dihapus setelah selesai
- [ ] Tidak ada yang bisa akses script tanpa password
- [ ] Sudah verifikasi hasil reset

---

## 🆘 Troubleshooting

### Error: "Cannot connect to database"

**Solusi:**
```php
// Cek file config.php, pastikan kredensial database benar
// Path: config/config.php

define('DB_HOST', 'localhost');  // atau IP dari Hostinger
define('DB_USER', 'u123456_user');
define('DB_PASS', 'password_database_anda');
define('DB_NAME', 'u123456_dbname');
```

### Error: "Foreign key constraint fails"

**Solusi:**
- Script sudah handle ini dengan `SET FOREIGN_KEY_CHECKS = 0`
- Jika masih error, jalankan manual di phpMyAdmin

### Timeout Error (Script terlalu lama)

**Solusi:**
```php
// Tambahkan di awal run_reset_online.php
set_time_limit(300); // 5 menit
ini_set('max_execution_time', 300);
```

### File run_reset_online.php tidak bisa diakses

**Solusi:**
```
1. Cek permission file: harus 644 atau 755
2. Cek path: harus di public_html/database/migrations/
3. Cek .htaccess: pastikan tidak ada restrict access
```

---

## 📞 Kontak Support

Jika mengalami kesulitan:
1. Backup dulu database Anda
2. Screenshot error message
3. Hubungi tim IT atau developer

---

## 🔄 Restore dari Backup (Jika Diperlukan)

Jika terjadi kesalahan dan perlu restore:

**Via phpMyAdmin:**
```
1. Login phpMyAdmin
2. Pilih database
3. Klik tab "Import"
4. Pilih file backup .sql
5. Klik "Go"
```

**Via Hostinger hPanel:**
```
1. Databases → pilih database
2. Manage → Backup/Import
3. Upload file backup
4. Execute import
```

---

## ⏱️ Estimasi Waktu

- Backup database: 2-5 menit
- Upload & setup: 2-3 menit
- Eksekusi reset: 10-30 detik (tergantung jumlah data)
- Verifikasi: 2-3 menit
- **Total: ~10-15 menit**

---

## ✅ Checklist Lengkap

```
SEBELUM RESET:
[ ] Backup database sudah dibuat
[ ] Password kuat sudah disiapkan
[ ] File run_reset_online.php sudah diedit
[ ] File sudah diupload ke hosting
[ ] Tim sudah dikonfirmasi

SAAT RESET:
[ ] Login berhasil dengan password
[ ] Checkbox konfirmasi sudah dicentang
[ ] Double-confirm di alert popup
[ ] Output log menunjukkan success

SETELAH RESET:
[ ] File run_reset_online.php sudah dihapus
[ ] Verifikasi via phpMyAdmin (semua 0)
[ ] Verifikasi via website (dashboard, inventory)
[ ] Backup file run_reset_online.php di local (optional)
[ ] Update dokumentasi/log internal
```

---

**🎯 INGAT: BACKUP DULU, HAPUS FILE SETELAH SELESAI!**
```