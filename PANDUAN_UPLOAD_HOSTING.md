# 🚀 PANDUAN LENGKAP UPLOAD WEBSITE KE HOSTING ONLINE
## Sinar Telekom Dashboard System

---

## 📦 BAGIAN 1: FILE YANG HARUS DI-UPLOAD

### ✅ **File Utama (Root Directory)**
```
✓ index.php                      - Landing page
✓ login.php                      - Halaman login
✓ logout.php                     - Proses logout
✓ dashboard.php                  - Dashboard utama
✓ profile.php                    - Halaman profil user
```

### ✅ **Folder config/**
```
✓ config/
   ├── config.php                - Konfigurasi database (HARUS DIEDIT!)
   └── .htaccess                 - Proteksi folder config
```

### ✅ **Folder admin/** (Semua file)
```
✓ admin/
   ├── index.php                 - Dashboard admin
   ├── users.php                 - Manajemen user
   ├── produk.php                - Manajemen produk
   ├── kategori.php              - Manajemen kategori
   ├── cabang.php                - Manajemen cabang
   ├── penjualan.php             - Data penjualan
   ├── inventory.php             - Inventory admin
   ├── stock.php                 - Manajemen stok
   ├── reseller.php              - Data reseller
   └── grafik.php                - Grafik & statistik
```

### ✅ **Folder modules/** (File Production)
```
✓ modules/
   ├── inventory/
   │   ├── inventory.php                        - Inventory utama
   │   ├── inventory_stock.php                  - Manajemen stok
   │   ├── inventory_stock_masuk.php            - Stok masuk
   │   ├── inventory_stock_keluar.php           - Stok keluar
   │   ├── inventory_stock_masuk_approval.php   - Approval stok masuk
   │   ├── inventory_stock_keluar_approval.php  - Approval stok keluar
   │   ├── inventory_laporan.php                - Laporan inventory
   │   └── inventory_laporan_enhanced.php       - Laporan enhanced
   │
   ├── laporan/
   │   ├── laporan_sidebar.php                  - Sidebar laporan
   │   ├── laporan_filter.php                   - Filter laporan
   │   ├── laporan_stats.php                    - Statistik
   │   ├── laporan_charts.php                   - Grafik
   │   ├── laporan_table.php                    - Tabel data
   │   ├── laporan_info.php                     - Info laporan
   │   ├── export_laporan_excel.php             - Export Excel
   │   └── export_laporan_pdf.php               - Export PDF
   │
   └── performance/
       └── performance-cluster.php              - Performance monitoring
```

### ✅ **Folder assets/**
```
✓ assets/
   ├── css/
   │   ├── styles.css                - Style utama
   │   ├── admin-styles.css          - Style admin panel
   │   └── laporan_styles.css        - Style laporan
   │
   ├── js/
   │   └── script.js                 - JavaScript utama
   │
   └── images/
       └── bg_login.jpg              - Background login (dan gambar lainnya)
```

### ✅ **Folder database/**
```
✓ database/
   └── database.sql                  - File SQL untuk import database
```

---

## ❌ BAGIAN 2: FILE YANG TIDAK PERLU DI-UPLOAD

### ❌ **File Development & Debug**
```
✗ modules/inventory/debug_*.php              - Semua file debug
✗ modules/inventory/inventory_stock_*_OLD.php - File backup lama
✗ admin/debug*.php                           - File debug admin
✗ admin/check_*.php                          - File checking
✗ admin/users_debug.php                      - Debug users
```

### ❌ **Folder Development**
```
✗ _development/                              - Seluruh folder development
   ✗ _development/backup/                    - File backup
   ✗ _development/debug/                     - File debug
   ✗ _development/sql_fixes/                 - SQL fixes
   ✗ _development/tests/                     - File testing
```

### ❌ **File Dokumentasi**
```
✗ docs/                                      - Seluruh folder dokumentasi
✗ *.md                                       - Semua file Markdown
✗ TODO*.md                                   - File TODO
✗ README.md                                  - File README
```

### ❌ **File Database Migration (Sudah ada di database.sql)**
```
✗ database/migrations/                       - Folder migrations
✗ database/seeds/                            - Folder seeds
```

### ❌ **File PowerShell & Script**
```
✗ *.ps1                                      - PowerShell scripts
✗ fix_*.ps1                                  - Fix scripts
✗ update_*.ps1                               - Update scripts
```

### ❌ **File HTML Development**
```
✗ modules/performance/performance-cluster.html
```

---

## 🔧 BAGIAN 3: LANGKAH-LANGKAH UPLOAD

### **STEP 1: Persiapan Database di Hosting**

1. **Login ke cPanel hosting Anda**

2. **Buat Database Baru:**
   - Masuk ke "MySQL Databases"
   - Buat database baru: `sinar_telkom_dashboard`
   - Buat user database baru
   - Berikan semua privileges ke user tersebut
   - **CATAT:**
     - Nama database lengkap (biasanya: `username_sinar_telkom_dashboard`)
     - Username database (biasanya: `username_dbuser`)
     - Password database

3. **Import Database:**
   - Buka phpMyAdmin
   - Pilih database yang baru dibuat
   - Klik tab "Import"
   - Upload file `database/database.sql`
   - Klik "Go" dan tunggu sampai selesai

### **STEP 2: Edit File config.php (PENTING!)**

**Sebelum upload**, edit file `config/config.php`:

```php
<?php
// Database Configuration
// ⚠️ GANTI DENGAN KREDENSIAL HOSTING ANDA!

define('DB_HOST', 'localhost');                    // Biasanya 'localhost'
define('DB_USER', 'username_dbuser');              // ⚠️ GANTI!
define('DB_PASS', 'password_database_anda');       // ⚠️ GANTI!
define('DB_NAME', 'username_sinar_telkom_dashboard'); // ⚠️ GANTI!
```

**Contoh untuk hosting shared:**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'sinarte1_dbuser');
define('DB_PASS', 'P@ssw0rd123!');
define('DB_NAME', 'sinarte1_dashboard');
```

### **STEP 3: Upload File ke Hosting**

#### **Opsi A: Via FTP (FileZilla)**

1. **Download FileZilla** (jika belum punya)
2. **Connect ke FTP:**
   - Host: ftp.namadomain.com
   - Username: username FTP Anda
   - Password: password FTP Anda
   - Port: 21

3. **Upload File:**
   - Di sisi kiri (local): pilih folder project Anda
   - Di sisi kanan (remote): masuk ke folder `public_html` atau `www`
   - Upload semua file yang ada di checklist ✅
   - Pastikan struktur folder tetap sama

#### **Opsi B: Via cPanel File Manager**

1. **Login ke cPanel**
2. **Buka "File Manager"**
3. **Masuk ke folder `public_html`**
4. **Upload file:**
   - Zip semua file yang perlu di-upload (hanya yang ✅)
   - Upload file zip
   - Extract di hosting
   - Hapus file zip setelah extract

### **STEP 4: Struktur Folder di Hosting**

Pastikan struktur folder seperti ini:

```
public_html/
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── profile.php
│
├── config/
│   ├── config.php
│   └── .htaccess
│
├── admin/
│   ├── index.php
│   ├── users.php
│   ├── produk.php
│   └── ... (semua file admin)
│
├── modules/
│   ├── inventory/
│   │   ├── inventory.php
│   │   ├── inventory_stock.php
│   │   └── ... (file inventory lainnya)
│   ├── laporan/
│   │   └── ... (file laporan)
│   └── performance/
│       └── performance-cluster.php
│
├── assets/
│   ├── css/
│   │   ├── styles.css
│   │   ├── admin-styles.css
│   │   └── laporan_styles.css
│   ├── js/
│   │   └── script.js
│   └── images/
│       └── bg_login.jpg
│
└── database/
    └── database.sql (optional, untuk backup)
```

### **STEP 5: Set Permission File**

Set permission yang benar untuk keamanan:

```
Folder:
- public_html/          : 755
- config/               : 755
- admin/                : 755
- modules/              : 755
- assets/               : 755

File:
- *.php                 : 644
- config/config.php     : 600 (lebih aman)
- *.css                 : 644
- *.js                  : 644
- *.jpg, *.png          : 644
```

**Cara set permission di cPanel:**
1. Klik kanan pada file/folder
2. Pilih "Change Permissions"
3. Set sesuai angka di atas

### **STEP 6: Testing Website**

1. **Akses website Anda:**
   ```
   https://namadomain.com
   atau
   https://namadomain.com/login.php
   ```

2. **Login dengan akun default:**
   - Username: `admin`
   - Password: `password`

3. **Test semua fitur:**
   - ✅ Login/Logout
   - ✅ Dashboard
   - ✅ Inventory
   - ✅ Stock Masuk/Keluar
   - ✅ Approval System
   - ✅ Laporan
   - ✅ Export Excel/PDF
   - ✅ Admin Panel (Users, Produk, Kategori, dll)

---

## 🏢 BAGIAN 4: REKOMENDASI HOSTING

Berdasarkan spesifikasi aplikasi Anda, berikut rekomendasi hosting:

### **Spesifikasi Minimum yang Dibutuhkan:**
```
✓ PHP Version: 7.4 atau lebih tinggi (recommended: PHP 8.0+)
✓ MySQL/MariaDB: 5.7 atau lebih tinggi
✓ Storage: Minimal 500 MB
✓ RAM: Minimal 512 MB
✓ Bandwidth: Unlimited atau minimal 10 GB/bulan
✓ SSL Certificate: Gratis (Let's Encrypt)
✓ cPanel: Untuk kemudahan management
```

### **🌟 Rekomendasi Hosting Indonesia:**

#### **1. Niagahoster** ⭐⭐⭐⭐⭐
```
✓ Paket: Bayi (Rp 14.900/bulan)
✓ Storage: Unlimited
✓ Bandwidth: Unlimited
✓ PHP 8.0+, MySQL
✓ SSL Gratis
✓ cPanel
✓ Support 24/7 Bahasa Indonesia
✓ Server di Indonesia (cepat)
```
**Cocok untuk:** Pemula, UMKM, Website kecil-menengah

#### **2. Hostinger** ⭐⭐⭐⭐⭐
```
✓ Paket: Premium (Rp 26.900/bulan)
✓ Storage: 100 GB SSD
✓ Bandwidth: Unlimited
✓ PHP 8.0+, MySQL
✓ SSL Gratis
✓ Custom Control Panel
✓ Support 24/7
```
**Cocok untuk:** Website dengan traffic menengah

#### **3. Rumahweb** ⭐⭐⭐⭐
```
✓ Paket: Personal (Rp 20.000/bulan)
✓ Storage: 1.5 GB
✓ Bandwidth: Unlimited
✓ PHP 7.4+, MySQL
✓ SSL Gratis
✓ cPanel
✓ Support Lokal
```
**Cocok untuk:** Bisnis lokal, support Indonesia

#### **4. IDCloudHost** ⭐⭐⭐⭐
```
✓ Paket: Warrior (Rp 15.000/bulan)
✓ Storage: Unlimited
✓ Bandwidth: Unlimited
✓ PHP 8.0+, MySQL
✓ SSL Gratis
✓ cPanel
✓ Server Indonesia
```
**Cocok untuk:** Startup, bisnis berkembang

#### **5. Dewaweb** ⭐⭐⭐⭐⭐
```
✓ Paket: Hunter (Rp 20.000/bulan)
✓ Storage: Unlimited
✓ Bandwidth: Unlimited
✓ PHP 8.1+, MySQL
✓ SSL Gratis
✓ cPanel
✓ Support Premium
```
**Cocok untuk:** Bisnis serius, performa tinggi

### **🌍 Rekomendasi Hosting International:**

#### **1. DigitalOcean** (VPS) ⭐⭐⭐⭐⭐
```
✓ Paket: Basic Droplet ($6/bulan)
✓ RAM: 1 GB
✓ Storage: 25 GB SSD
✓ Bandwidth: 1 TB
✓ Full Control (Root Access)
```
**Cocok untuk:** Developer, kontrol penuh, scalable

#### **2. Vultr** (VPS) ⭐⭐⭐⭐
```
✓ Paket: Cloud Compute ($6/bulan)
✓ RAM: 1 GB
✓ Storage: 25 GB SSD
✓ Bandwidth: 1 TB
```
**Cocok untuk:** Performa tinggi, global reach

### **💡 Rekomendasi Berdasarkan Kebutuhan:**

**Untuk Pemula / Budget Terbatas:**
→ **Niagahoster** atau **Hostinger** (Shared Hosting)
- Mudah digunakan
- Support Bahasa Indonesia
- Harga terjangkau
- Cocok untuk traffic kecil-menengah

**Untuk Bisnis Berkembang:**
→ **Dewaweb** atau **IDCloudHost** (Shared/Cloud Hosting)
- Performa lebih baik
- Support premium
- Cocok untuk traffic menengah-tinggi

**Untuk Developer / Kontrol Penuh:**
→ **DigitalOcean** atau **Vultr** (VPS)
- Full control
- Scalable
- Performa maksimal
- Butuh skill server management

---

## 🔒 BAGIAN 5: KEAMANAN PRODUCTION

### **1. Ganti Password Default**

Setelah upload, **SEGERA** ganti password default:

```sql
-- Login ke phpMyAdmin, jalankan query ini:
UPDATE users 
SET password = '$2y$10$YourNewHashedPasswordHere' 
WHERE username = 'admin';
```

Atau buat password baru via PHP:
```php
<?php
echo password_hash('password_baru_anda', PASSWORD_DEFAULT);
?>
```

### **2. Proteksi File Sensitif**

Buat file `.htaccess` di folder `config/`:
```apache
# Deny access to config folder
Order Deny,Allow
Deny from all
```

### **3. Disable Error Display**

Edit `config/config.php`, tambahkan:
```php
// Production mode
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
```

### **4. Aktifkan SSL/HTTPS**

Di cPanel:
1. Masuk ke "SSL/TLS Status"
2. Aktifkan AutoSSL (Let's Encrypt)
3. Tunggu beberapa menit
4. Website otomatis pakai HTTPS

### **5. Backup Rutin**

Setup backup otomatis:
- **Database:** Backup setiap hari
- **File:** Backup setiap minggu
- Simpan backup di tempat terpisah

---

## 📋 BAGIAN 6: CHECKLIST UPLOAD

```
PERSIAPAN:
☐ Database sudah dibuat di hosting
☐ User database sudah dibuat
☐ Privileges database sudah di-set
☐ File database.sql sudah di-import
☐ Import database berhasil (cek di phpMyAdmin)

KONFIGURASI:
☐ config.php sudah diedit dengan kredensial hosting
☐ DB_HOST sudah benar
☐ DB_USER sudah benar
☐ DB_PASS sudah benar
☐ DB_NAME sudah benar

UPLOAD:
☐ Semua file ✅ sudah di-upload
☐ Struktur folder sudah benar
☐ File tidak ada yang corrupt
☐ Permission file sudah di-set (755/644)

TESTING:
☐ Website bisa diakses (https://namadomain.com)
☐ Login berhasil
☐ Dashboard muncul
☐ Menu inventory berfungsi
☐ Stock masuk/keluar berfungsi
☐ Approval system berfungsi
☐ Laporan berfungsi
☐ Export Excel/PDF berfungsi
☐ Admin panel berfungsi

KEAMANAN:
☐ Password default sudah diganti
☐ Error display sudah di-disable
☐ SSL/HTTPS sudah aktif
☐ Backup sudah di-setup
☐ File sensitif sudah diproteksi
```

---

## 🆘 BAGIAN 7: TROUBLESHOOTING

### **Problem 1: "Connection failed" / Error Database**

**Penyebab:**
- Kredensial database salah
- Database belum dibuat
- User tidak punya akses

**Solusi:**
1. Cek `config/config.php`
2. Pastikan DB_HOST, DB_USER, DB_PASS, DB_NAME benar
3. Cek di phpMyAdmin apakah database ada
4. Cek privileges user database

### **Problem 2: "500 Internal Server Error"**

**Penyebab:**
- Permission file salah
- Syntax error di .htaccess
- PHP version tidak kompatibel

**Solusi:**
1. Set permission: folder 755, file 644
2. Cek error log di cPanel
3. Cek PHP version (minimal 7.4)
4. Rename .htaccess sementara untuk test

### **Problem 3: Halaman Blank/Putih**

**Penyebab:**
- PHP error
- Memory limit
- Syntax error

**Solusi:**
1. Enable error display sementara:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Cek error log
3. Cek PHP memory limit (minimal 128M)

### **Problem 4: CSS/Style Tidak Muncul**

**Penyebab:**
- Path file salah
- File tidak ter-upload
- Browser cache

**Solusi:**
1. Cek apakah file CSS ada di folder assets/css/
2. Clear browser cache (Ctrl+F5)
3. Cek path di HTML: `assets/css/styles.css`
4. Cek permission file CSS (644)

### **Problem 5: Login Gagal**

**Penyebab:**
- Password salah
- Session tidak jalan
- Database tidak terkoneksi

**Solusi:**
1. Cek koneksi database
2. Cek session di PHP (session_start)
3. Reset password via phpMyAdmin
4. Cek error log

### **Problem 6: Upload File Gagal**

**Penyebab:**
- File size limit
- Permission folder
- PHP upload limit

**Solusi:**
1. Cek PHP upload_max_filesize
2. Cek post_max_size
3. Set permission folder upload: 755
4. Edit php.ini atau .htaccess:
   ```apache
   php_value upload_max_filesize 20M
   php_value post_max_size 20M
   ```

---

## 📞 BAGIAN 8: SUPPORT & BANTUAN

### **Cek Error Log:**
1. Login cPanel
2. Masuk "Error Log"
3. Lihat error terbaru
4. Fix sesuai error message

### **Hubungi Support Hosting:**
Jika masalah tidak bisa diselesaikan:
- Buka ticket support
- Jelaskan masalah dengan detail
- Sertakan error message
- Minta bantuan instalasi jika perlu

### **Resource Tambahan:**
- Dokumentasi hosting Anda
- Forum support hosting
- Stack Overflow
- PHP documentation

---

## 📝 CATATAN PENTING

1. ⚠️ **JANGAN upload file development/debug ke production**
2. ⚠️ **SELALU backup sebelum update**
3. ⚠️ **Test di local dulu sebelum upload**
4. ⚠️ **Ganti password default SEGERA**
5. ⚠️ **Aktifkan SSL/HTTPS untuk keamanan**
6. ⚠️ **Monitor error log secara berkala**
7. ⚠️ **Update PHP dan MySQL secara berkala**
8. ⚠️ **Jangan share kredensial database**

---

## 🎉 SELAMAT!

Jika semua checklist sudah ✅, website Anda sudah online dan siap digunakan!

**Website Anda:** https://namadomain.com

**Login Admin:**
- URL: https://namadomain.com/login.php
- Username: admin
- Password: (yang sudah Anda ganti)

---

**Good luck! 🚀**

*Dibuat untuk: Sinar Telekom Dashboard System*
*Versi: 1.0*
*Tanggal: 2024*
