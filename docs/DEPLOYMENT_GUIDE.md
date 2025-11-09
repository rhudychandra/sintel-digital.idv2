# 📦 Panduan Upload Website ke Hosting Online

## 🎯 Daftar File yang WAJIB di-Upload

### 1️⃣ **File Konfigurasi Utama**
```
✅ config.php                    (PENTING - Edit kredensial database!)
✅ .htaccess                     (Konfigurasi Apache)
```

### 2️⃣ **File Halaman Utama**
```
✅ login.php                     (Halaman login)
✅ dashboard.php                 (Dashboard utama)
✅ logout.php                    (Logout handler)
✅ index.html                    (Landing page - optional)
```

### 3️⃣ **File Inventory & Penjualan**
```
✅ inventory.php                 (Halaman inventory utama)
✅ inventory_stock.php           (Manajemen stok)
✅ inventory_stock_keluar.php    (Stok keluar)
✅ inventory_laporan.php         (Laporan inventory)
✅ inventory_laporan_enhanced.php
```

### 4️⃣ **File Laporan & Export**
```
✅ laporan_sidebar.php
✅ laporan_filter.php
✅ laporan_stats.php
✅ laporan_charts.php
✅ laporan_table.php
✅ laporan_info.php
✅ export_laporan_excel.php      (Export ke Excel)
✅ export_laporan_pdf.php        (Export ke PDF)
```

### 5️⃣ **File CSS & JavaScript**
```
✅ styles.css                    (Style utama)
✅ laporan_styles.css            (Style laporan)
✅ script.js                     (JavaScript utama)
```

### 6️⃣ **Folder Admin (Semua file di dalamnya)**
```
✅ admin/
   ├── index.php                 (Dashboard admin)
   ├── users.php                 (Manajemen user)
   ├── produk.php                (Manajemen produk)
   ├── kategori.php              (Manajemen kategori)
   ├── cabang.php                (Manajemen cabang)
   ├── penjualan.php             (Data penjualan)
   ├── inventory.php             (Inventory admin)
   ├── stock.php                 (Manajemen stok)
   ├── reseller.php              (Data reseller)
   ├── grafik.php                (Grafik & statistik)
   └── admin-styles.css          (Style admin panel)
```

### 7️⃣ **File Database**
```
✅ database.sql                  (Struktur database - untuk import)
```

---

## ❌ File yang TIDAK PERLU di-Upload (Development Only)

### File Debug & Testing
```
❌ debug_*.php                   (Semua file debug)
❌ check_*.php                   (File checking)
❌ inventory_debug*.php
❌ inventory_test*.php
❌ admin/debug*.php
❌ admin/check*.php
```

### File Dokumentasi
```
❌ *.md                          (Semua file Markdown)
❌ README.md
❌ TODO*.md
❌ *_DOCUMENTATION.md
❌ *_GUIDE.md
❌ TESTING_GUIDE*.md
```

### File SQL Update/Fix
```
❌ add_*.sql                     (File SQL tambahan)
❌ fix_*.sql                     (File SQL perbaikan)
❌ update_*.sql                  (File SQL update)
❌ create_*.sql                  (Kecuali database.sql utama)
❌ database_*.sql                (Kecuali database.sql utama)
❌ clear_*.sql
```

### File Backup & Temporary
```
❌ *_backup.php
❌ *_old.php
❌ *_new.php
❌ *_simple.php
❌ dashboard.html                (Jika sudah ada dashboard.php)
❌ performance-cluster.*
```

---

## 🔧 Langkah-Langkah Upload ke Hosting

### **STEP 1: Persiapan Database**

1. **Login ke cPanel/phpMyAdmin hosting Anda**
2. **Buat database baru:**
   - Nama database: `sinar_telkom_dashboard` (atau sesuai keinginan)
   - Catat: nama database, username, password

3. **Import database:**
   - Buka phpMyAdmin
   - Pilih database yang baru dibuat
   - Klik tab "Import"
   - Upload file `database.sql`
   - Klik "Go"

### **STEP 2: Edit File config.php**

**PENTING!** Edit file `config.php` sebelum upload:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');           // Biasanya 'localhost'
define('DB_USER', 'username_hosting');    // ⚠️ GANTI dengan username database hosting
define('DB_PASS', 'password_hosting');    // ⚠️ GANTI dengan password database hosting
define('DB_NAME', 'nama_database');       // ⚠️ GANTI dengan nama database hosting
```

**Contoh untuk hosting:**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'sinarte1_dbuser');
define('DB_PASS', 'P@ssw0rd123!');
define('DB_NAME', 'sinarte1_dashboard');
```

### **STEP 3: Edit File .htaccess**

Edit bagian error reporting untuk production:

```apache
# Enable PHP error reporting for development
# Comment out for production
# php_flag display_errors on          ← Tambahkan # di depan
# php_value error_reporting E_ALL     ← Tambahkan # di depan
```

Atau hapus kedua baris tersebut untuk keamanan.

### **STEP 4: Upload File ke Hosting**

**Via FTP/FileZilla:**
1. Connect ke FTP hosting Anda
2. Masuk ke folder `public_html` atau `www`
3. Upload semua file yang ada di checklist ✅ di atas
4. Pastikan struktur folder tetap sama:
   ```
   public_html/
   ├── config.php
   ├── .htaccess
   ├── login.php
   ├── dashboard.php
   ├── inventory.php
   ├── styles.css
   ├── admin/
   │   ├── index.php
   │   ├── users.php
   │   └── ...
   └── ...
   ```

**Via cPanel File Manager:**
1. Login ke cPanel
2. Buka "File Manager"
3. Masuk ke `public_html`
4. Upload file (bisa zip dulu, lalu extract di hosting)

### **STEP 5: Set Permission File**

Set permission untuk keamanan:
```
- Folder: 755
- File PHP: 644
- config.php: 600 (lebih aman)
- .htaccess: 644
```

### **STEP 6: Testing**

1. **Akses website:**
   ```
   https://namadomain.com
   atau
   https://namadomain.com/login.php
   ```

2. **Login dengan akun default:**
   - Username: `admin`
   - Password: `password` atau `admin` atau `Tsel2025`

3. **Test fitur utama:**
   - ✅ Login/Logout
   - ✅ Dashboard
   - ✅ Inventory
   - ✅ Laporan
   - ✅ Admin Panel

---

## 🔒 Keamanan Production

### 1. **Ganti Password Default**
Setelah upload, segera ganti password default di database:
```sql
UPDATE users 
SET password = '$2y$10$...' 
WHERE username = 'admin';
```

### 2. **Disable Error Display**
Pastikan error tidak ditampilkan di production (sudah di .htaccess)

### 3. **Backup Rutin**
- Backup database setiap hari
- Backup file setiap minggu

### 4. **SSL Certificate**
Aktifkan HTTPS/SSL di hosting untuk keamanan

---

## 📋 Checklist Upload

```
☐ Database sudah dibuat di hosting
☐ File database.sql sudah di-import
☐ config.php sudah diedit dengan kredensial hosting
☐ .htaccess sudah diedit (disable error display)
☐ Semua file ✅ sudah di-upload
☐ Struktur folder sudah benar
☐ Permission file sudah di-set
☐ Website bisa diakses
☐ Login berhasil
☐ Semua fitur berfungsi
☐ Password default sudah diganti
```

---

## 🆘 Troubleshooting

### **Error: "Connection failed"**
- Cek kredensial database di `config.php`
- Pastikan database sudah dibuat
- Cek apakah user database punya akses

### **Error: "500 Internal Server Error"**
- Cek permission file (644 untuk PHP)
- Cek syntax error di .htaccess
- Lihat error log di cPanel

### **Halaman blank/putih**
- Enable error display sementara untuk debug
- Cek PHP version (minimal PHP 7.4)
- Cek error log

### **CSS/Style tidak muncul**
- Cek path file CSS
- Clear browser cache
- Pastikan file CSS ter-upload

---

## 📞 Support

Jika ada masalah saat upload:
1. Cek error log di cPanel
2. Pastikan PHP version minimal 7.4
3. Pastikan MySQL/MariaDB aktif
4. Hubungi support hosting jika perlu

---

## 📝 Catatan Penting

1. **Jangan upload file development** (debug, test, backup)
2. **Selalu backup sebelum update**
3. **Test di local dulu sebelum upload**
4. **Ganti password default segera**
5. **Aktifkan SSL/HTTPS**
6. **Monitor error log secara berkala**

---

**Selamat! Website Anda siap online! 🚀**
