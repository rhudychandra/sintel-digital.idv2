# 📦 Inventory Feature Documentation

## Overview
Halaman Inventory menyediakan 2 fitur utama:
1. **Input Barang** - Untuk menambah stok barang masuk
2. **Input Penjualan** - Untuk mencatat penjualan ke reseller

## 🚀 Cara Menggunakan

### Akses Halaman Inventory
1. Login ke sistem
2. Di dashboard, klik hexagon **"Inventory"**
3. Atau akses langsung: `http://localhost/sinartelekomdashboardsystem/inventory.php`

## 📥 Fitur 1: Input Barang (Stok Masuk)

### Fungsi:
Menambah stok produk yang masuk ke gudang/inventory

### Form Fields:
1. **Tanggal** - Tanggal barang masuk (default: hari ini)
2. **Nama Produk** - Dropdown list produk aktif (menampilkan stok saat ini)
3. **Quantity** - Jumlah barang yang masuk
4. **Keterangan** - Catatan tambahan (opsional)

### Proses:
1. Pilih tanggal
2. Pilih produk dari dropdown
3. Masukkan quantity
4. Tambahkan keterangan jika perlu
5. Klik **"💾 Simpan Stok Masuk"**

### Yang Terjadi di Backend:
- ✅ Stok produk di tabel `produk` bertambah
- ✅ Record baru di tabel `inventory` dengan tipe_transaksi = 'masuk'
- ✅ Mencatat stok_sebelum dan stok_sesudah
- ✅ Mencatat user yang melakukan input

### Contoh:
```
Tanggal: 2024-01-20
Produk: Internet 20 Mbps (Stok: 99)
Quantity: 50
Keterangan: Stok bulanan Januari

Hasil:
- Stok Internet 20 Mbps menjadi: 149
- Record inventory tercatat
```

## 💰 Fitur 2: Input Penjualan

### Fungsi:
Mencatat penjualan produk ke reseller

### Form Fields:
1. **Tanggal** - Tanggal penjualan (default: hari ini)
2. **Nama Reseller** - Dropdown list reseller aktif
3. **Nama Produk** - Dropdown list produk (menampilkan harga dan stok)
4. **Quantity** - Jumlah yang dijual
5. **Total Harga** - Otomatis terhitung (harga × quantity)

### Proses:
1. Pilih tanggal
2. Pilih reseller
3. Pilih produk (akan tampil harga dan stok)
4. Masukkan quantity
5. Total harga otomatis terhitung
6. Klik **"💳 Proses Penjualan"**

### Yang Terjadi di Backend:
- ✅ Generate nomor invoice otomatis (INV-YYYYMMDD-XXXX)
- ✅ Create/get pelanggan berdasarkan nama reseller
- ✅ Insert record ke tabel `penjualan`
- ✅ Insert detail ke tabel `detail_penjualan`
- ✅ Stok produk berkurang
- ✅ Record inventory dengan tipe_transaksi = 'keluar'
- ✅ Status pembayaran otomatis 'paid'

### Validasi:
- ❌ Quantity tidak boleh melebihi stok tersedia
- ❌ Semua field wajib diisi
- ✅ Alert jika quantity > stok

### Contoh:
```
Tanggal: 2024-01-20
Reseller: Toko Elektronik Jaya
Produk: Internet 20 Mbps - Rp 350,000 (Stok: 149)
Quantity: 10

Total Harga: Rp 3,500,000

Hasil:
- Invoice: INV-20240120-1234
- Stok Internet 20 Mbps menjadi: 139
- Penjualan tercatat dengan total Rp 3,500,000
- Detail penjualan tercatat
- Inventory record tercatat
```

## 🎨 Fitur UI/UX

### Auto-Calculate Total
- Total harga otomatis terhitung saat:
  - Memilih produk
  - Mengubah quantity
  - Real-time calculation

### Stock Validation
- Alert otomatis jika quantity > stok
- Quantity otomatis di-set ke maksimal stok
- Mencegah overselling

### Dropdown Information
- **Produk dropdown** menampilkan:
  - Nama produk
  - Harga (untuk penjualan)
  - Stok tersedia
  
- **Reseller dropdown** menampilkan:
  - Nama reseller

### Success/Error Messages
- ✅ Success message (hijau) jika berhasil
- ❌ Error message (merah) jika gagal
- Menampilkan nomor invoice untuk penjualan

## 📊 Database Tables Affected

### Input Barang:
1. **produk** - Update stok
2. **inventory** - Insert record (tipe: masuk)

### Input Penjualan:
1. **pelanggan** - Create/get pelanggan
2. **penjualan** - Insert transaksi
3. **detail_penjualan** - Insert detail item
4. **produk** - Update stok (kurang)
5. **inventory** - Insert record (tipe: keluar)

## 🔐 Security

### Authentication:
- ✅ Require login (requireLogin())
- ✅ User harus authenticated

### Data Validation:
- ✅ Server-side validation
- ✅ Prepared statements (SQL injection prevention)
- ✅ Input sanitization

### User Tracking:
- ✅ Setiap transaksi mencatat user_id
- ✅ Audit trail di tabel inventory

## 📱 Responsive Design

- ✅ Desktop: 2 kolom side-by-side
- ✅ Mobile: 1 kolom stacked
- ✅ Touch-friendly buttons
- ✅ Readable on all screen sizes

## 🎯 Business Logic

### Stok Management:
```
Stok Masuk:
stok_sesudah = stok_sebelum + quantity

Stok Keluar (Penjualan):
stok_sesudah = stok_sebelum - quantity
```

### Invoice Generation:
```
Format: INV-YYYYMMDD-XXXX
Example: INV-20240120-0001

YYYY = Year
MM = Month
DD = Day
XXXX = Random 4-digit number
```

### Pricing:
```
Total = Harga Satuan × Quantity
No discount applied (can be added later)
```

## 🔄 Integration

### With Other Features:
- **Admin Panel** - Data penjualan tampil di admin
- **Dashboard** - Statistik updated real-time
- **Reports** - Data untuk reporting

### Database Views:
- `view_admin_dashboard` - Total penjualan updated
- `view_sales_per_cabang` - Sales per branch
- `view_stok_produk` - Stock levels

## 🚀 Future Enhancements

### Possible Additions:
1. ✨ Discount/promo support
2. ✨ Multiple products per transaction
3. ✨ Print invoice
4. ✨ Export to PDF
5. ✨ Stock alerts (low stock notification)
6. ✨ Batch import (Excel)
7. ✨ Barcode scanning
8. ✨ Return/refund handling

## 📝 Testing Checklist

### Input Barang:
- [ ] Form validation works
- [ ] Stock increases correctly
- [ ] Inventory record created
- [ ] Success message displays
- [ ] Error handling works

### Input Penjualan:
- [ ] Form validation works
- [ ] Total auto-calculates
- [ ] Stock validation works
- [ ] Invoice generated
- [ ] Stock decreases correctly
- [ ] All tables updated
- [ ] Success message with invoice number

### UI/UX:
- [ ] Responsive on mobile
- [ ] Dropdowns load correctly
- [ ] Auto-calculate works
- [ ] Alerts display properly
- [ ] Back button works

## 🐛 Troubleshooting

### Issue: Dropdown kosong
**Solution:** Check database - pastikan ada produk/reseller dengan status 'active'

### Issue: Total tidak terhitung
**Solution:** Check JavaScript console untuk errors

### Issue: Stok tidak update
**Solution:** Check database permissions dan query execution

### Issue: Error saat submit
**Solution:** Check PHP error log dan database connection

## 📞 Support

Untuk bantuan lebih lanjut:
- Check `config.php` untuk database connection
- Check browser console untuk JavaScript errors
- Check PHP error log untuk server errors
- Verify database tables exist

---

**File:** `inventory.php`  
**Created:** 2024  
**Status:** ✅ Production Ready  
**Version:** 1.0
