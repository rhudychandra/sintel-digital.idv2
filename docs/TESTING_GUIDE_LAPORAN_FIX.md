# 🧪 Testing Guide: Laporan Penjualan Fix

## 📋 Testing Checklist

### Phase 1: Database Fix (WAJIB DILAKUKAN DULU)

#### Step 1.1: Backup Database
```sql
-- Buka phpMyAdmin
-- Pilih database: sinar_telekom_dashboard
-- Klik tab "Export"
-- Klik "Go" untuk download backup
```
**Status:** ⬜ Belum | ✅ Selesai

#### Step 1.2: Run SQL Fix untuk Data Lama
```sql
-- Buka phpMyAdmin → SQL tab
-- Copy-paste dan jalankan:

USE sinar_telkom_dashboard;

-- Update penjualan dengan cabang_id dari reseller
UPDATE penjualan p
INNER JOIN reseller r ON p.reseller_id = r.reseller_id
SET p.cabang_id = r.cabang_id
WHERE p.cabang_id IS NULL;

-- Fallback untuk yang masih NULL
UPDATE penjualan SET cabang_id = 1 WHERE cabang_id IS NULL;

-- Verify
SELECT 
    penjualan_id,
    no_invoice,
    tanggal_penjualan,
    cabang_id,
    total
FROM penjualan
ORDER BY penjualan_id DESC
LIMIT 10;
```
**Expected Result:** Semua penjualan sekarang punya cabang_id (tidak ada NULL)

**Status:** ⬜ Belum | ✅ Selesai

---

### Phase 2: Verify Data Fix

#### Step 2.1: Check dengan Diagnostic Tool
1. Buka: `http://localhost/sinartelekomdashboardsystem/check_latest_penjualan.php`
2. Periksa bagian "Latest 10 Penjualan"
3. **Expected:** Semua data berwarna HIJAU (cabang_id sesuai dengan user)

**Hasil:**
- [ ] Semua data punya cabang_id (tidak ada NULL)
- [ ] Data yang sesuai cabang user berwarna hijau
- [ ] Jumlah transaksi untuk cabang user > 0

**Status:** ⬜ Pass | ❌ Fail

**Screenshot/Notes:**
```
[Paste hasil di sini]
```

---

### Phase 3: Test Input Penjualan Baru

#### Step 3.1: Input Penjualan Baru
1. Buka: `http://localhost/sinartelekomdashboardsystem/inventory.php?page=input_penjualan`
2. Isi form:
   - Tanggal: [Hari ini]
   - Reseller: [Pilih reseller dari cabang Anda]
   - Produk: [Pilih 1-2 produk]
   - Qty: [Masukkan jumlah]
   - Metode Pembayaran: Transfer
   - Status: Paid
3. Klik "Proses Penjualan"

**Expected Result:** 
- ✅ Muncul pesan sukses dengan nomor invoice
- ✅ Invoice format: INV-YYYYMMDD-XXXX

**Hasil:**
- [ ] Penjualan berhasil disimpan
- [ ] Dapat nomor invoice: _______________

**Status:** ⬜ Pass | ❌ Fail

#### Step 3.2: Verify Data Baru di Database
1. Buka phpMyAdmin
2. Jalankan query:
```sql
SELECT 
    penjualan_id,
    no_invoice,
    tanggal_penjualan,
    cabang_id,
    reseller_id,
    total,
    created_at
FROM penjualan
ORDER BY penjualan_id DESC
LIMIT 1;
```

**Expected Result:**
- ✅ Data terbaru ada
- ✅ cabang_id TIDAK NULL
- ✅ cabang_id sesuai dengan reseller yang dipilih

**Hasil cabang_id:** _______________

**Status:** ⬜ Pass | ❌ Fail

---

### Phase 4: Test Laporan Penjualan

#### Step 4.1: Check Laporan dengan Filter Default
1. Buka: `http://localhost/sinartelekomdashboardsystem/inventory_laporan.php`
2. Lihat filter default (bulan ini)
3. Periksa apakah data baru muncul

**Expected Result:**
- ✅ Data penjualan baru MUNCUL di tabel
- ✅ Stats cards menampilkan angka yang benar
- ✅ Grafik menampilkan data (jika ada)

**Hasil:**
- [ ] Data baru muncul di tabel
- [ ] Total Penjualan: Rp _______________
- [ ] Total Transaksi: _______________

**Status:** ⬜ Pass | ❌ Fail

#### Step 4.2: Test Filter Tanggal
1. Ubah filter tanggal ke "Hari Ini"
2. Klik "Terapkan Filter"

**Expected Result:**
- ✅ Hanya menampilkan transaksi hari ini
- ✅ Data baru yang tadi diinput MUNCUL

**Status:** ⬜ Pass | ❌ Fail

#### Step 4.3: Test Quick Date Presets
Test masing-masing preset:
- [ ] Hari Ini - Data hari ini muncul
- [ ] Minggu Ini - Data minggu ini muncul
- [ ] Bulan Ini - Data bulan ini muncul
- [ ] Bulan Lalu - Data bulan lalu muncul (jika ada)

**Status:** ⬜ Pass | ❌ Fail

---

### Phase 5: Test Role-Based Access

#### Step 5.1: Test sebagai Staff (Current User)
**User:** admin_pagaralam (staff, cabang_id: 1)

1. Buka laporan
2. Periksa data yang ditampilkan

**Expected Result:**
- ✅ Hanya menampilkan data dengan cabang_id = 1
- ✅ Tidak ada dropdown filter cabang
- ✅ Semua data yang muncul adalah dari cabang Pagar Alam

**Hasil:**
- [ ] Filter cabang TIDAK muncul (correct untuk staff)
- [ ] Semua data dari cabang yang sama
- [ ] Jumlah transaksi: _______________

**Status:** ⬜ Pass | ❌ Fail

#### Step 5.2: Test sebagai Administrator/Manager (Optional)
**Note:** Jika Anda punya user dengan role administrator atau manager

1. Logout dari user staff
2. Login dengan user administrator/manager
3. Buka laporan

**Expected Result:**
- ✅ Dropdown filter cabang MUNCUL
- ✅ Bisa melihat data dari SEMUA cabang
- ✅ Bisa filter per cabang

**Status:** ⬜ Pass | ❌ Fail | ⬜ Skip (tidak ada user admin)

---

### Phase 6: Test Export Functions

#### Step 6.1: Test Export Excel
1. Di halaman laporan, klik tombol "📊 Export Excel"
2. File .xls akan terdownload

**Expected Result:**
- ✅ File berhasil didownload
- ✅ File bisa dibuka di Excel/LibreOffice
- ✅ Data sesuai dengan yang di layar

**Status:** ⬜ Pass | ❌ Fail

#### Step 6.2: Test Export PDF
1. Klik tombol "📄 Export PDF"
2. File .pdf akan terdownload

**Expected Result:**
- ✅ File berhasil didownload
- ✅ File bisa dibuka
- ✅ Data sesuai dengan yang di layar

**Status:** ⬜ Pass | ❌ Fail

#### Step 6.3: Test Print
1. Klik tombol "🖨️ Print"
2. Dialog print browser muncul

**Expected Result:**
- ✅ Print preview muncul
- ✅ Layout print-friendly (tanpa sidebar, filter, dll)

**Status:** ⬜ Pass | ❌ Fail

---

### Phase 7: Test Charts & Visualizations

#### Step 7.1: Sales Trend Chart
1. Pastikan ada data dalam range tanggal yang dipilih
2. Periksa chart "Trend Penjualan"

**Expected Result:**
- ✅ Chart muncul (tidak error)
- ✅ Data points sesuai dengan transaksi
- ✅ Tooltip menampilkan nilai yang benar

**Status:** ⬜ Pass | ❌ Fail | ⬜ No Data

#### Step 7.2: Payment Distribution Chart
1. Periksa chart "Distribusi Metode Pembayaran"

**Expected Result:**
- ✅ Chart muncul
- ✅ Menampilkan breakdown per metode pembayaran
- ✅ Warna berbeda untuk tiap metode

**Status:** ⬜ Pass | ❌ Fail | ⬜ No Data

---

### Phase 8: Test Search & Filters

#### Step 8.1: Test Search
1. Masukkan nomor invoice di search box
2. Klik filter

**Expected Result:**
- ✅ Hanya menampilkan invoice yang dicari
- ✅ Search case-insensitive

**Status:** ⬜ Pass | ❌ Fail

#### Step 8.2: Test Filter Reseller
1. Pilih reseller tertentu dari dropdown
2. Klik "Terapkan Filter"

**Expected Result:**
- ✅ Hanya menampilkan transaksi dari reseller tersebut

**Status:** ⬜ Pass | ❌ Fail

#### Step 8.3: Test Filter Status
1. Pilih status "Paid"
2. Klik "Terapkan Filter"

**Expected Result:**
- ✅ Hanya menampilkan transaksi dengan status Paid

**Status:** ⬜ Pass | ❌ Fail

---

### Phase 9: Regression Testing

#### Step 9.1: Test Input Penjualan Lagi
1. Input 1 penjualan baru lagi
2. Verify muncul di laporan

**Status:** ⬜ Pass | ❌ Fail

#### Step 9.2: Test dengan Multiple Products
1. Input penjualan dengan 3+ produk
2. Verify semua produk tercatat
3. Verify total benar

**Status:** ⬜ Pass | ❌ Fail

#### Step 9.3: Test Stock Update
1. Cek stok produk sebelum penjualan
2. Input penjualan
3. Verify stok berkurang dengan benar

**Status:** ⬜ Pass | ❌ Fail

---

## 📊 Test Summary

### Results:
- **Total Tests:** 25
- **Passed:** _____ / 25
- **Failed:** _____ / 25
- **Skipped:** _____ / 25

### Critical Issues Found:
```
[List any critical issues here]
```

### Minor Issues Found:
```
[List any minor issues here]
```

### Recommendations:
```
[Any recommendations for improvement]
```

---

## ✅ Sign-off

**Tested By:** _____________________
**Date:** _____________________
**Status:** ⬜ PASS | ❌ FAIL | ⬜ PASS WITH ISSUES

**Notes:**
```
[Additional notes]
