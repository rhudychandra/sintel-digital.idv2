# 📦 Panduan Upload Website ke Hosting (Struktur Baru)

## 🎯 Struktur Folder Baru

Setelah reorganisasi, struktur folder menjadi lebih rapi:

```
sinartelekomdashboardsystem/
│
├── 📁 config/                      # Konfigurasi
│   ├── config.php                  # ⚠️ EDIT kredensial database!
│   └── .htaccess                   # ⚠️ EDIT disable error display!
│
├── 📁 assets/                      # Asset statis
│   ├── css/
│   │   ├── styles.css
│   │   ├── laporan_styles.css
│   │   └── admin-styles.css
│   └── js/
│       └── script.js
│
├── 📁 modules/                     # Modul aplikasi
│   ├── inventory/
│   │   ├── inventory.php
│   │   ├── inventory_stock.php
│   │   ├── inventory_stock_keluar.php
│   │   ├── inventory_laporan.php
│   │   └── inventory_laporan_enhanced.php
│   │
│   ├── laporan/
│   │   ├── laporan_sidebar.php
│   │   ├── laporan_filter.php
│   │   ├── laporan_stats.php
│   │   ├── laporan_charts.php
│   │   ├── laporan_table.php
│   │   ├── laporan_info.php
│   │   ├── export_laporan_excel.php
│   │   └── export_laporan_pdf.php
│   │
│   └── performance/
│       ├── performance-cluster.php
│       └── performance-cluster.html
│
├── 📁 admin/                       # Admin panel
│   ├── index.php
│   ├── users.php
│   ├── produk.php
│   ├── kategori.php
│   ├── cabang.php
│   ├── penjualan.php
│   ├── inventory.php
│   ├── stock.php
│   ├── reseller.php
│   └── grafik.php
│
├── 📁 database/                    # Database files
│   └── database.sql                # ⚠️ IMPORT ke hosting!
│
├── 📄 index.php                    # Entry point
├── 📄 login.php                    # Login page
├── 📄 logout.php                   # Logout handler
└── 📄 dashboard.php                # Main dashboard
```

---

## ✅ File yang WAJIB di-Upload

### 1. **Folder Config**
```
✅ config/config.php               ⚠️ EDIT DULU!
✅ config/.htaccess                ⚠️ EDIT DULU!
```

### 2. **Folder Assets (Semua)**
```
✅ assets/css/styles.css
✅ assets/css/laporan_styles.css
✅ assets/css/admin-styles.css
✅ assets/js/script.js
```

### 3. **Folder Modules (Semua)**
```
✅ modules/inventory/              (semua file .php)
✅ modules/laporan/                (semua file .php)
✅ modules/performance/            (semua file .php & .html)
```

### 4. **Folder Admin (Semua)**
```
✅ admin/                          (semua file .php)
```

### 5. **Folder Database**
```
✅ database/database.sql           (untuk import)
```

### 6. **File Root**
```
✅ index.php
✅ login.php
✅ logout.php
✅ dashboard.php
```

---

## ❌ Folder yang JANGAN di-Upload

```
❌ docs/                           (dokumentasi)
❌ _development/                   (file development)
```

---

## 🔧 Langkah Upload ke Hosting

### **STEP 1: Persiapan Database**

1. Login ke cPanel/phpMyAdmin hosting
2. Buat database baru
3. Import file `database/database.sql`
4. Catat: nama database, username, password

### **STEP 2: Edit File Konfigurasi**

**Edit `config/config.php`:**
```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');              // Biasanya 'localhost'
define('DB_USER', 'username_hosting');       // ⚠️ GANTI!
define('DB_PASS', 'password_hosting');       // ⚠️ GANTI!
define('DB_NAME', 'nama_database_hosting');  // ⚠️ GANTI!
```

**Edit `config/.htaccess`:**
```apache
# Disable error display untuk production
# php_flag display_errors on          ← Comment atau hapus
# php_value error_reporting E_ALL     ← Comment atau hapus
```

### **STEP 3: Upload File**

**Via FTP/FileZilla:**
1. Connect ke FTP hosting
2. Masuk ke `public_html/`
3. Upload folder & file sesuai struktur:
   ```
   public_html/
   ├── config/
   ├── assets/
   ├── modules/
   ├── admin/
   ├── database/
   ├── index.php
   ├── login.php
   ├── logout.php
   └── dashboard.php
   ```

**Via cPanel File Manager:**
1. Zip semua folder yang perlu diupload
2. Upload zip ke `public_html/`
3. Extract di hosting
4. Hapus file zip

### **STEP 4: Set Permission**

```
Folder: 755
File PHP: 644
config/config.php: 600 (lebih aman)
config/.htaccess: 644
```

### **STEP 5: Testing**

1. Akses: `https://namadomain.com`
2. Login dengan akun default:
   - Username: `admin`
   - Password: `password` atau `admin` atau `Tsel2025`
3. Test semua fitur

---

## 🎯 Keuntungan Struktur Baru

1. ✅ **Root directory lebih bersih** - Hanya 4 file utama
2. ✅ **Mudah maintenance** - File terkelompok berdasarkan fungsi
3. ✅ **Mudah deployment** - Folder _development tidak perlu diupload
4. ✅ **Lebih profesional** - Struktur standar industri
5. ✅ **Mudah backup** - Bisa backup per modul

---

## 📋 Checklist Upload

```
☐ Database sudah dibuat di hosting
☐ database/database.sql sudah di-import
☐ config/config.php sudah diedit
☐ config/.htaccess sudah diedit
☐ Folder config/ sudah di-upload
☐ Folder assets/ sudah di-upload
☐ Folder modules/ sudah di-upload
☐ Folder admin/ sudah di-upload
☐ File root (index.php, login.php, dll) sudah di-upload
☐ Permission file sudah di-set
☐ Website bisa diakses
☐ Login berhasil
☐ Semua fitur berfungsi
☐ Password default sudah diganti
```

---

## 🔒 Keamanan Production

1. **Ganti password default** segera
2. **Disable error display** di config/.htaccess
3. **Set permission yang benar** (config.php = 600)
4. **Aktifkan SSL/HTTPS**
5. **Backup rutin** database & file

---

## 🆘 Troubleshooting

### **Error: "config.php not found"**
- Pastikan folder `config/` ada di root
- Cek path di file PHP: `require_once 'config/config.php'`

### **CSS tidak muncul**
- Cek folder `assets/css/` sudah ter-upload
- Clear browser cache
- Cek path di HTML: `<link href="assets/css/styles.css">`

### **Module tidak bisa diakses**
- Pastikan folder `modules/` sudah ter-upload lengkap
- Cek permission folder (755)

---

## 📞 Support

Jika ada masalah:
1. Cek error log di cPanel
2. Pastikan PHP version minimal 7.4
3. Pastikan semua folder ter-upload dengan struktur yang benar

---

**Struktur baru ini lebih rapi dan profesional! 🚀**
