# 🧪 Testing Guide: Metode Pembayaran & Status Pembayaran

## ⚠️ PENTING: Jalankan SQL Script Terlebih Dahulu!

Sebelum testing, **WAJIB** jalankan SQL script ini:

### Langkah 1: Jalankan SQL Script

1. Buka **phpMyAdmin**
2. Pilih database **sinar_telkom_dashboard**
3. Klik tab **SQL**
4. Copy-paste seluruh isi file `update_payment_fields.sql`
5. Klik **Go**
6. Pastikan muncul pesan: "✅ Database updated successfully!"

---

## 📝 Test Case 1: Input Penjualan dengan Finpay & TOP

### Steps:
1. Buka `http://localhost/sinartelekomdashboardsystem/inventory.php?page=input_penjualan`
2. Isi form:
   - **Tanggal**: Hari ini
   - **Reseller**: Pilih reseller manapun
   - **Produk**: Pilih 1 produk, qty 1
   - **Metode Pembayaran**: Pilih **Finpay**
   - **Status Pembayaran**: Pilih **TOP**
3. Klik "💰 Proses Penjualan"

### Expected Result:
- ✅ Form berhasil disubmit
- ✅ Muncul pesan sukses dengan nomor invoice
- ✅ Data tersimpan ke database

### Verify:
1. Scroll ke bawah ke tabel "Laporan Penjualan"
2. Cari invoice yang baru dibuat
3. **Kolom Metode** harus menampilkan: **Finpay**
4. **Kolom Status** harus menampilkan badge biru: **TOP**

---

## 📝 Test Case 2: Input Penjualan dengan Budget Komitmen & Paid

### Steps:
1. Buat penjualan baru
2. Isi form:
   - **Metode Pembayaran**: Pilih **Budget Komitmen**
   - **Status Pembayaran**: Pilih **Paid**
3. Submit form

### Expected Result:
- ✅ **Kolom Metode**: Budget Komitmen
- ✅ **Kolom Status**: Badge hijau "Paid"

---

## 📝 Test Case 3: Input Penjualan dengan Transfer & Pending

### Steps:
1. Buat penjualan baru
2. Isi form:
   - **Metode Pembayaran**: Pilih **Transfer**
   - **Status Pembayaran**: Pilih **Pending**
3. Submit form

### Expected Result:
- ✅ **Kolom Metode**: Transfer
- ✅ **Kolom Status**: Badge orange "Pending"

---

## 📝 Test Case 4: Input Penjualan dengan Cash & Cancelled

### Steps:
1. Buat penjualan baru
2. Isi form:
   - **Metode Pembayaran**: Pilih **Cash**
   - **Status Pembayaran**: Pilih **Cancelled**
3. Submit form

### Expected Result:
- ✅ **Kolom Metode**: Cash
- ✅ **Kolom Status**: Badge merah "Cancelled"

---

## 📝 Test Case 5: Validasi Required Field

### Steps:
1. Buat penjualan baru
2. Isi semua field KECUALI Metode Pembayaran
3. Coba submit form

### Expected Result:
- ❌ Form tidak bisa disubmit
- ✅ Browser menampilkan error: "Please fill out this field"

### Steps (Part 2):
1. Isi Metode Pembayaran
2. Kosongkan Status Pembayaran
3. Coba submit form

### Expected Result:
- ❌ Form tidak bisa disubmit
- ✅ Browser menampilkan error: "Please fill out this field"

---

## 📝 Test Case 6: Badge Colors Verification

Setelah membuat beberapa penjualan dengan status berbeda, verifikasi warna badge:

| Status | Warna Badge | Background | Text Color |
|--------|-------------|------------|------------|
| **Paid** | 🟢 Green | #d4edda | #27ae60 |
| **Pending** | 🟠 Orange | #fff3cd | #f39c12 |
| **TOP** | 🔵 Blue | #d1ecf1 | #3498db |
| **Cancelled** | 🔴 Red | #f8d7da | #e74c3c |

### Verification:
1. Buka laporan penjualan
2. Lihat kolom Status
3. Pastikan setiap status memiliki warna yang benar

---

## 📝 Test Case 7: Export Excel

### Steps:
1. Buka laporan penjualan
2. Pastikan ada data dengan berbagai metode & status
3. Klik tombol "📊 Export Excel"

### Expected Result:
- ✅ File Excel terdownload
- ✅ File berisi kolom "Metode" dan "Status"
- ✅ Data metode & status muncul dengan benar

---

## 📝 Test Case 8: Export CSV

### Steps:
1. Buka laporan penjualan
2. Klik tombol "📄 Export CSV"

### Expected Result:
- ✅ File CSV terdownload
- ✅ File berisi kolom "Metode" dan "Status"
- ✅ Data metode & status muncul dengan benar

---

## 📝 Test Case 9: Filter Tanggal

### Steps:
1. Buat beberapa penjualan dengan tanggal berbeda
2. Gunakan filter tanggal untuk periode tertentu
3. Klik "🔍 Filter"

### Expected Result:
- ✅ Data yang ditampilkan sesuai periode
- ✅ Kolom Metode & Status tetap muncul
- ✅ Badge colors tetap bekerja

---

## 📝 Test Case 10: Multiple Products dalam 1 Invoice

### Steps:
1. Buat penjualan baru dengan 3 produk berbeda
2. Pilih Metode: Finpay
3. Pilih Status: TOP
4. Submit form

### Expected Result:
- ✅ Semua produk tersimpan dalam 1 invoice
- ✅ Setiap baris produk menampilkan Metode: Finpay
- ✅ Setiap baris produk menampilkan Status: TOP (badge biru)
- ✅ Subtotal invoice muncul dengan benar

---

## 🐛 Troubleshooting

### Problem: Metode/Status tidak muncul di laporan

**Solution:**
1. Pastikan SQL script sudah dijalankan
2. Cek di phpMyAdmin:
   ```sql
   DESCRIBE penjualan;
   ```
3. Pastikan kolom `metode_pembayaran` dan `status_pembayaran` bertipe `VARCHAR(50)`

### Problem: Badge tidak berwarna

**Solution:**
1. Clear browser cache (Ctrl + Shift + Delete)
2. Refresh halaman (Ctrl + F5)
3. Cek console browser untuk error JavaScript

### Problem: Data lama tidak muncul

**Solution:**
1. Jalankan query UPDATE di SQL script
2. Atau manual update via phpMyAdmin:
   ```sql
   UPDATE penjualan 
   SET metode_pembayaran = 'Transfer' 
   WHERE metode_pembayaran = 'transfer';
   ```

---

## ✅ Checklist Testing

Centang setiap test yang sudah dilakukan:

- [ ] SQL Script sudah dijalankan
- [ ] Test Case 1: Finpay & TOP ✅
- [ ] Test Case 2: Budget Komitmen & Paid ✅
- [ ] Test Case 3: Transfer & Pending ✅
- [ ] Test Case 4: Cash & Cancelled ✅
- [ ] Test Case 5: Validasi Required ✅
- [ ] Test Case 6: Badge Colors ✅
- [ ] Test Case 7: Export Excel ✅
- [ ] Test Case 8: Export CSV ✅
- [ ] Test Case 9: Filter Tanggal ✅
- [ ] Test Case 10: Multiple Products ✅

---

## 📸 Screenshot Checklist

Ambil screenshot untuk dokumentasi:

1. ✅ Form input dengan dropdown Metode & Status
2. ✅ Laporan dengan kolom Metode & Status
3. ✅ Badge Paid (hijau)
4. ✅ Badge Pending (orange)
5. ✅ Badge TOP (biru)
6. ✅ Badge Cancelled (merah)
7. ✅ Export Excel result
8. ✅ Export CSV result

---

## 🎯 Success Criteria

Testing dianggap berhasil jika:

1. ✅ Semua 4 metode pembayaran bisa dipilih dan tersimpan
2. ✅ Semua 4 status pembayaran bisa dipilih dan tersimpan
3. ✅ Data muncul dengan benar di laporan
4. ✅ Badge colors sesuai dengan status
5. ✅ Export Excel & CSV berfungsi dengan baik
6. ✅ Validasi required field bekerja
7. ✅ Tidak ada error di console browser
8. ✅ Tidak ada error di PHP error log

---

**Happy Testing! 🚀**
