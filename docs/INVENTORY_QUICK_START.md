# 🚀 Quick Start Guide - Inventory System

## 📌 Akses Cepat

### Main Menu:
```
http://localhost/sinartelekomdashboardsystem/inventory.php
```

### Direct Access:
- **Stock Keluar:** `inventory_stock_keluar.php`
- **Stock Monitoring:** `inventory_stock.php`
- **Laporan Penjualan:** `inventory_laporan.php`

---

## 🎯 Menu Overview

### 1. 📊 Dashboard
**Fungsi:** Overview penjualan per reseller
- Grafik penjualan
- Summary per reseller
- Filter period (daily/weekly/monthly)

### 2. 📥 Input Barang
**Fungsi:** Input stok masuk (pembelian dari supplier)
- Form input sederhana
- Auto update stok
- Catat ke inventory

### 3. 📤 Stock Keluar ⭐ NEW
**Fungsi:** Catat pengeluaran stok non-penjualan
- Rusak/Expired
- Hilang
- Promosi/Sample
- Internal Use
- Return ke Supplier
- Lainnya

### 4. 💰 Input Penjualan
**Fungsi:** Input penjualan ke reseller
- Multiple products per invoice
- Auto generate invoice number
- Auto update stok
- Laporan basic

### 5. 📦 Stock ⭐ NEW
**Fungsi:** Monitoring stok real-time
- Summary statistics
- Filter cabang/kategori
- Search produk
- Status badges (Low/Medium/Good)

### 6. 📋 Laporan Penjualan ⭐ NEW
**Fungsi:** Laporan penjualan lengkap
- Summary statistics
- Filter lengkap
- Detail per invoice
- Grand total

---

## 👤 Role-Based Access

### Admin (Cabang):
- ✅ Akses semua menu inventory
- ✅ Data terbatas pada cabangnya
- ✅ Cabang otomatis terisi
- ❌ Tidak bisa pilih cabang lain

### Administrator:
- ✅ Akses semua menu inventory
- ✅ Bisa pilih cabang mana saja
- ✅ Lihat data semua cabang
- ✅ Full control

### Staff:
- ✅ Akses semua menu inventory
- ✅ Bisa pilih cabang
- ✅ Lihat data semua cabang
- ✅ Input & monitoring

---

## 📝 Common Tasks

### Task 1: Input Stok Masuk
1. Klik menu "Input Barang"
2. Isi tanggal, cabang (jika perlu), produk, qty
3. Klik "💾 Simpan"
4. ✅ Done!

### Task 2: Catat Stock Keluar (Rusak)
1. Klik menu "Stock Keluar"
2. Isi form, pilih alasan "Rusak"
3. Klik "💾 Simpan Stock Keluar"
4. ✅ Done!

### Task 3: Input Penjualan
1. Klik menu "Input Penjualan"
2. Pilih tanggal & reseller
3. Tambah produk (bisa multiple)
4. Klik "💰 Proses Penjualan"
5. ✅ Done!

### Task 4: Cek Stock Produk
1. Klik menu "Stock"
2. Gunakan filter jika perlu
3. Lihat status badges
4. ✅ Done!

### Task 5: Lihat Laporan Penjualan
1. Klik menu "Laporan Penjualan"
2. Set filter tanggal/reseller
3. Klik "🔍 Terapkan Filter"
4. Lihat summary & detail
5. ✅ Done!

---

## 🎨 UI Elements

### Color Codes:
- 🟢 **Green** - Success, Good Stock, Paid
- 🟡 **Yellow** - Warning, Low Stock, Pending
- 🔴 **Red** - Danger, Out of Stock, Cancelled
- 🔵 **Blue** - Info, Medium Stock
- 🟣 **Purple** - Primary actions

### Icons:
- 📊 Dashboard
- 📥 Input/Masuk
- 📤 Output/Keluar
- 💰 Penjualan
- 📦 Stock
- 📋 Laporan
- ✅ Success
- ❌ Error/Cancel
- ⚠️ Warning

---

## 💡 Tips & Tricks

### Tip 1: Quick Navigation
Gunakan sidebar menu untuk navigasi cepat antar halaman.

### Tip 2: Filter Efektif
Kombinasikan multiple filter untuk hasil lebih spesifik.

### Tip 3: Monitor Low Stock
Check menu "Stock" secara berkala untuk produk low stock.

### Tip 4: Export Data (Coming Soon)
Fitur export ke Excel akan ditambahkan di update berikutnya.

### Tip 5: Keyboard Shortcuts
- Tab: Pindah antar field
- Enter: Submit form (di field terakhir)
- Esc: Cancel/Close (di beberapa form)

---

## 🆘 Troubleshooting

### Problem: Tidak bisa login
**Solution:** Check username & password, atau hubungi administrator

### Problem: Menu tidak muncul
**Solution:** Refresh browser (Ctrl+F5) atau clear cache

### Problem: Data tidak tersimpan
**Solution:** Check koneksi database, lihat error message

### Problem: Stok tidak update
**Solution:** Check apakah transaksi berhasil, verify di database

### Problem: Filter tidak bekerja
**Solution:** Reset filter, coba lagi dengan parameter berbeda

---

## 📞 Support

Jika mengalami masalah:
1. Check dokumentasi ini
2. Check INVENTORY_TESTING_GUIDE.md
3. Check error message di halaman
4. Hubungi IT support/developer

---

## 🔄 Updates & Changelog

### Version 1.0 (Current)
- ✅ Stock Keluar feature
- ✅ Stock Monitoring feature
- ✅ Enhanced Laporan Penjualan
- ✅ Modular file structure
- ✅ Responsive design

### Planned (Future):
- 📅 Export to Excel/PDF
- 📅 Print functionality
- 📅 Email notifications
- 📅 Barcode scanner
- 📅 Stock forecasting

---

**Created:** <?php echo date('Y-m-d H:i:s'); ?>  
**For:** All Users (Admin, Administrator, Staff)  
**System:** Sinar Telekom Dashboard - Inventory Module
