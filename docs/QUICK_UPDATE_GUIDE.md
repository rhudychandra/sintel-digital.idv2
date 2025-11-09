# 🚀 Panduan Cepat Update Path File

## ✅ Progress: 50% Selesai

### File yang Sudah Diupdate:
1. ✅ index.php
2. ✅ login.php
3. ✅ logout.php
4. ✅ dashboard.php
5. ✅ modules/inventory/inventory.php
6. ✅ modules/inventory/inventory_stock.php
7. ✅ modules/inventory/inventory_stock_keluar.php

### File yang Masih Perlu Update (17 file):

#### Modules - Inventory (2 file):
- [ ] modules/inventory/inventory_laporan.php
- [ ] modules/inventory/inventory_laporan_enhanced.php

#### Modules - Laporan (8 file):
- [ ] modules/laporan/laporan_sidebar.php
- [ ] modules/laporan/laporan_filter.php
- [ ] modules/laporan/laporan_stats.php
- [ ] modules/laporan/laporan_charts.php
- [ ] modules/laporan/laporan_table.php
- [ ] modules/laporan/laporan_info.php
- [ ] modules/laporan/export_laporan_excel.php
- [ ] modules/laporan/export_laporan_pdf.php

#### Modules - Performance (2 file):
- [ ] modules/performance/performance-cluster.php
- [ ] modules/performance/performance-cluster.html

#### Admin Panel (10 file):
- [ ] admin/index.php
- [ ] admin/users.php
- [ ] admin/produk.php
- [ ] admin/kategori.php
- [ ] admin/cabang.php
- [ ] admin/penjualan.php
- [ ] admin/inventory.php
- [ ] admin/stock.php
- [ ] admin/reseller.php
- [ ] admin/grafik.php

---

## 📝 Pattern Update yang Konsisten:

### Untuk File di modules/ (2 level dari root):
```php
// GANTI INI:
require_once 'config.php';
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="admin/admin-styles.css">
<a href="dashboard.php">
<a href="logout.php">

// MENJADI INI:
require_once '../../config/config.php';
<link rel="stylesheet" href="../../assets/css/styles.css">
<link rel="stylesheet" href="../../assets/css/admin-styles.css">
<a href="../../dashboard.php">
<a href="../../logout.php">
```

### Untuk File di admin/ (1 level dari root):
```php
// GANTI INI:
require_once '../config.php';
<link rel="stylesheet" href="admin-styles.css">
<a href="../dashboard.php">
<a href="../logout.php">

// MENJADI INI:
require_once '../config/config.php';
<link rel="stylesheet" href="../assets/css/admin-styles.css">
<a href="../dashboard.php">
<a href="../logout.php">
```

---

## 🔧 Cara Update Manual (Recommended):

### Opsi 1: Edit Manual Satu Per Satu
1. Buka file yang perlu diupdate
2. Tekan `Ctrl+H` (Find & Replace)
3. Ganti path sesuai pattern di atas
4. Save file

### Opsi 2: Find & Replace di VSCode (Batch)
1. Tekan `Ctrl+Shift+H` (Find & Replace in Files)
2. **Untuk modules/inventory/**:
   - Files to include: `modules/inventory/*.php`
   - Find: `require_once 'config.php'`
   - Replace: `require_once '../../config/config.php'`
   - Replace All
3. **Untuk modules/laporan/**:
   - Files to include: `modules/laporan/*.php`
   - Find: `require_once 'config.php'`
   - Replace: `require_once '../../config/config.php'`
   - Replace All
4. **Untuk admin/**:
   - Files to include: `admin/*.php`
   - Find: `require_once '../config.php'`
   - Replace: `require_once '../config/config.php'`
   - Replace All

---

## ⚡ Script Otomatis (Advanced):

Jika Anda familiar dengan command line, bisa gunakan script ini:

### Windows (PowerShell):
```powershell
# Update modules/inventory/
Get-ChildItem -Path "modules/inventory/*.php" | ForEach-Object {
    (Get-Content $_.FullName) -replace "require_once 'config.php'", "require_once '../../config/config.php'" | Set-Content $_.FullName
    (Get-Content $_.FullName) -replace 'href="styles.css"', 'href="../../assets/css/styles.css"' | Set-Content $_.FullName
}

# Update modules/laporan/
Get-ChildItem -Path "modules/laporan/*.php" | ForEach-Object {
    (Get-Content $_.FullName) -replace "require_once 'config.php'", "require_once '../../config/config.php'" | Set-Content $_.FullName
    (Get-Content $_.FullName) -replace 'href="styles.css"', 'href="../../assets/css/styles.css"' | Set-Content $_.FullName
}

# Update admin/
Get-ChildItem -Path "admin/*.php" | ForEach-Object {
    (Get-Content $_.FullName) -replace "require_once '../config.php'", "require_once '../config/config.php'" | Set-Content $_.FullName
    (Get-Content $_.FullName) -replace 'href="admin-styles.css"', 'href="../assets/css/admin-styles.css"' | Set-Content $_.FullName
}
```

---

## ✅ Checklist Setelah Update:

- [ ] Semua file sudah diupdate
- [ ] Test login
- [ ] Test dashboard
- [ ] Test inventory module
- [ ] Test laporan module
- [ ] Test admin panel
- [ ] CSS tampil dengan benar
- [ ] Tidak ada error 404

---

## 🎯 Setelah Semua Selesai:

1. **Test Lokal** - Pastikan semua fitur berfungsi
2. **Edit config.php** - Ganti kredensial database untuk hosting
3. **Edit config/.htaccess** - Disable error display
4. **Upload ke Hosting** - Ikuti panduan di DEPLOYMENT_GUIDE_NEW_STRUCTURE.md

---

**Estimasi Waktu:** 30-60 menit untuk menyelesaikan semua update manual
