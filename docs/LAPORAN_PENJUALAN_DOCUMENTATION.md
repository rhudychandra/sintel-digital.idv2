# 📋 Dokumentasi Laporan Penjualan - Enhanced Version

## 🎯 Overview

Halaman Laporan Penjualan telah di-upgrade dengan fitur-fitur modern dan profesional, termasuk:
- ✅ Filter berdasarkan cabang (role-based access)
- ✅ Visualisasi data dengan grafik interaktif
- ✅ Export ke Excel dan PDF
- ✅ Desain modern dan responsif
- ✅ Pencarian dan filter lanjutan

---

## 🚀 Fitur Utama

### 1. **Role-Based Branch Filtering**

#### Untuk Administrator & Manager:
- Dapat melihat data **semua cabang**
- Dropdown filter cabang tersedia
- Dapat memilih cabang spesifik atau melihat semua

#### Untuk Role Lainnya (Staff, Supervisor, Finance, Sales):
- Otomatis ter-filter ke **cabang mereka sendiri**
- Tidak dapat melihat data cabang lain
- Dropdown cabang tidak ditampilkan

### 2. **Filter & Pencarian Lanjutan**

#### Filter Tersedia:
- **Tanggal Mulai & Akhir**: Pilih range tanggal laporan
- **Cabang**: (Hanya untuk Admin/Manager)
- **Reseller**: Filter berdasarkan reseller tertentu
- **Status Pembayaran**: Paid, Pending, atau Cancelled
- **Pencarian**: Cari berdasarkan invoice, nama reseller, atau cabang

#### Quick Date Presets:
- 📅 Hari Ini
- 📅 Minggu Ini
- 📅 Bulan Ini
- 📅 Bulan Lalu
- 📅 Tahun Ini

### 3. **Summary Statistics**

Menampilkan 4 kartu statistik utama:
- 💰 **Total Penjualan**: Total nilai penjualan dalam periode
- 📊 **Total Transaksi**: Jumlah transaksi
- 📈 **Rata-rata per Transaksi**: Nilai rata-rata
- 📦 **Total Produk Terjual**: Jumlah item terjual

### 4. **Data Visualization**

#### Sales Trend Chart (Line Chart):
- Menampilkan trend penjualan harian
- Interaktif dengan tooltip
- Responsive design

#### Payment Distribution Chart (Doughnut Chart):
- Distribusi metode pembayaran
- Warna-warni untuk setiap metode
- Menampilkan persentase dan nilai

### 5. **Export Functionality**

#### 📊 Export to Excel:
- Format: .xls
- Includes: Header, data lengkap, grand total
- Otomatis download

#### 📄 Export to PDF:
- Format: PDF (via print)
- Layout: Landscape A4
- Professional design dengan header dan footer

#### 🖨️ Print:
- Print-friendly layout
- Hides unnecessary elements (sidebar, filters, charts)
- Optimized for paper

### 6. **Modern UI/UX**

- **Gradient Filter Box**: Purple gradient yang eye-catching
- **Enhanced Badges**: Status badges dengan gradient dan shadow
- **Hover Effects**: Table rows dengan smooth hover animation
- **Responsive Design**: Works on desktop, tablet, dan mobile
- **Professional Icons**: Emoji icons untuk visual appeal

---

## 📁 File Structure

```
sinartelekomdashboardsystem/
├── inventory_laporan.php              # Main report page (ENHANCED)
├── inventory_laporan_enhanced.php     # Backup version
├── laporan_styles.css                 # Modern CSS styling
├── laporan_sidebar.php                # Sidebar component
├── laporan_filter.php                 # Filter form component
├── laporan_stats.php                  # Statistics cards component
├── laporan_charts.php                 # Charts component
├── laporan_table.php                  # Data table component
├── laporan_info.php                   # Info box component
├── export_laporan_excel.php           # Excel export handler
└── export_laporan_pdf.php             # PDF export handler
```

---

## 🔧 Cara Penggunaan

### Akses Halaman

```
http://localhost/sinartelekomdashboardsystem/inventory_laporan.php
```

### Langkah-langkah:

#### 1. **Login**
   - Login dengan user yang memiliki akses ke inventory

#### 2. **Pilih Filter**
   - Tentukan tanggal mulai dan akhir
   - (Admin/Manager) Pilih cabang jika perlu
   - Pilih reseller jika ingin filter spesifik
   - Pilih status pembayaran jika perlu
   - Gunakan search box untuk pencarian cepat

#### 3. **Gunakan Quick Presets** (Optional)
   - Klik salah satu preset untuk quick select
   - Contoh: "Bulan Ini" untuk data bulan berjalan

#### 4. **Klik "Terapkan Filter"**
   - Data akan di-refresh sesuai filter

#### 5. **Analisis Data**
   - Lihat summary statistics di bagian atas
   - Analisis trend di grafik
   - Review detail transaksi di tabel

#### 6. **Export Data** (Optional)
   - Klik "Export Excel" untuk download .xls
   - Klik "Export PDF" untuk print/save PDF
   - Klik "Print" untuk print langsung

---

## 🎨 Design Features

### Color Scheme:
- **Primary**: Purple gradient (#667eea to #764ba2)
- **Success**: Green (#27ae60)
- **Info**: Blue (#3498db)
- **Warning**: Yellow (#f39c12)
- **Danger**: Red (#e74c3c)

### Typography:
- **Font Family**: Lexend (Google Fonts)
- **Weights**: 300, 400, 500, 600, 700

### Components:
- **Cards**: White background, rounded corners, subtle shadow
- **Buttons**: Gradient backgrounds, hover effects
- **Badges**: Gradient backgrounds, rounded pills
- **Tables**: Striped rows, hover effects

---

## 🔐 Security & Access Control

### Role-Based Access:

| Role | Access Level | Branch Filter |
|------|-------------|---------------|
| Administrator | All branches | ✅ Dropdown available |
| Manager | All branches | ✅ Dropdown available |
| Admin | Own branch only | ❌ Auto-filtered |
| Staff | Own branch only | ❌ Auto-filtered |
| Supervisor | Own branch only | ❌ Auto-filtered |
| Finance | Own branch only | ❌ Auto-filtered |
| Sales | Own branch only | ❌ Auto-filtered |

### SQL Security:
- ✅ Prepared statements untuk semua queries
- ✅ Parameter binding untuk prevent SQL injection
- ✅ Role validation di backend
- ✅ Automatic filtering berdasarkan user's cabang_id

---

## 📊 Data Display

### Table Columns:
1. **No**: Sequential number
2. **Tanggal**: Transaction date (dd/mm/yyyy)
3. **No Invoice**: Invoice number (highlighted)
4. **Reseller**: Reseller name
5. **Cabang**: Branch name (badge style)
6. **Items**: Total items count
7. **Subtotal**: Subtotal amount
8. **Total**: Total amount (bold, green)
9. **Status**: Payment status (badge with icon)
10. **Metode**: Payment method (with icon)

### Status Badges:
- ✅ **Paid**: Green gradient
- ⏳ **Pending**: Yellow gradient
- ❌ **Cancelled**: Red gradient

### Payment Method Icons:
- 💵 Cash
- 🏦 Transfer
- 💳 Credit Card
- 💳 Debit Card
- 📱 E-Wallet

---

## 📈 Charts Configuration

### Sales Trend Chart:
- **Type**: Line Chart
- **Data**: Daily sales total
- **X-Axis**: Dates (dd/mm format)
- **Y-Axis**: Sales amount (in millions)
- **Features**: 
  - Smooth curve (tension: 0.4)
  - Fill area under line
  - Tooltip with formatted currency

### Payment Distribution Chart:
- **Type**: Doughnut Chart
- **Data**: Total sales per payment method
- **Features**:
  - Color-coded segments
  - Legend at bottom
  - Tooltip with currency format
  - Percentage display

---

## 🐛 Troubleshooting

### Issue: Charts tidak muncul
**Solution**: 
- Pastikan koneksi internet aktif (Chart.js dari CDN)
- Check browser console untuk errors
- Pastikan ada data dalam periode yang dipilih

### Issue: Export tidak berfungsi
**Solution**:
- Check file permissions untuk export files
- Pastikan PHP dapat write files
- Check browser pop-up blocker

### Issue: Filter cabang tidak muncul
**Solution**:
- Pastikan user role adalah 'administrator' atau 'manager'
- Check database: user harus punya role yang benar
- Refresh halaman

### Issue: Data tidak sesuai cabang
**Solution**:
- Check user's cabang_id di database
- Pastikan penjualan punya cabang_id yang benar
- Verify role-based filtering logic

---

## 🔄 Update & Maintenance

### Database Requirements:
```sql
-- Pastikan tabel penjualan punya cabang_id
ALTER TABLE penjualan ADD COLUMN cabang_id INT;
ALTER TABLE penjualan ADD FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id);

-- Pastikan users punya cabang_id
ALTER TABLE users ADD COLUMN cabang_id INT;
ALTER TABLE users ADD FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id);
```

### Browser Compatibility:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

### Mobile Responsive:
- ✅ Desktop (1920px+)
- ✅ Laptop (1366px - 1920px)
- ✅ Tablet (768px - 1366px)
- ✅ Mobile (< 768px)

---

## 📞 Support

Jika ada pertanyaan atau issue:
1. Check dokumentasi ini terlebih dahulu
2. Review TODO_LAPORAN_ENHANCEMENT.md untuk technical details
3. Check browser console untuk error messages
4. Verify database structure dan data

---

## 🎉 Changelog

### Version 2.0 (Enhanced) - Current
- ✅ Added branch filtering with role-based access
- ✅ Added Chart.js visualizations
- ✅ Added Excel export functionality
- ✅ Added PDF export functionality
- ✅ Added print functionality
- ✅ Added search functionality
- ✅ Added date presets
- ✅ Modern UI/UX redesign
- ✅ Modular file structure
- ✅ Responsive design improvements

### Version 1.0 (Original)
- Basic sales report
- Date range filter
- Reseller filter
- Status filter
- Simple table display

---

## 📝 Notes

- Semua fitur telah diimplementasikan dan siap digunakan
- Export Excel menggunakan HTML table format (compatible dengan Excel)
- Export PDF menggunakan browser print (no external library needed)
- Charts menggunakan Chart.js dari CDN (requires internet)
- Modular structure memudahkan maintenance dan updates

---

**Last Updated**: <?php echo date('d F Y'); ?>
**Version**: 2.0 Enhanced
**Status**: ✅ Production Ready
