# 🧪 Testing Guide - Delete Blank Screen Fix

## 📋 Prerequisites

### 1. Setup Database
Pastikan database sudah disetup dengan benar:

```bash
# Step 1: Import database utama
mysql -u root -p < database.sql

# Step 2: Import update admin
mysql -u root -p < database_update_admin.sql
```

### 2. Verify Tables Exist
Login ke MySQL dan check:

```sql
USE sinar_telkom_dashboard;

-- Check tables
SHOW TABLES;

-- Should include:
-- - cabang
-- - reseller
-- - users (with cabang_id column)
-- - produk (with cabang_id column)
-- - inventory (with cabang_id column)
-- - penjualan (with cabang_id and reseller_id columns)
```

### 3. Verify Administrator User
```sql
SELECT username, role, status FROM users WHERE role = 'administrator';

-- Should return:
-- username: administrator
-- role: administrator
-- status: active
```

---

## 🧪 Test Cases

### Test 1: Delete Produk

**Steps:**
1. Login sebagai administrator (username: `administrator`, password: `password`)
2. Klik tombol "Administrator" di header
3. Klik menu "Produk" di sidebar
4. Scroll ke tabel produk
5. Klik tombol "Hapus" pada salah satu produk
6. Confirm dialog "Yakin ingin menghapus produk ini?"
7. Klik OK

**Expected Result:**
- ✅ Page redirect ke `admin/produk.php`
- ✅ Muncul success message hijau: "Produk berhasil dihapus!"
- ✅ Produk hilang dari tabel
- ✅ TIDAK ADA blank screen
- ✅ URL bersih (tidak ada POST data)

**Test Refresh:**
8. Tekan F5 atau refresh browser

**Expected Result:**
- ✅ Page reload normal
- ✅ Success message hilang
- ✅ TIDAK submit delete lagi
- ✅ Data tetap terhapus

---

### Test 2: Delete Cabang

**Steps:**
1. Dari admin panel, klik menu "Cabang"
2. Klik tombol "Hapus" pada salah satu cabang
3. Confirm dialog
4. Klik OK

**Expected Result:**
- ✅ Page redirect ke `admin/cabang.php`
- ✅ Muncul success message: "Cabang berhasil dihapus!"
- ✅ Cabang hilang dari tabel
- ✅ TIDAK ADA blank screen

**Test Refresh:**
5. Refresh browser

**Expected Result:**
- ✅ Page reload normal
- ✅ Success message hilang
- ✅ TIDAK submit delete lagi

---

### Test 3: Delete Users

**Steps:**
1. Dari admin panel, klik menu "Users"
2. Klik tombol "Hapus" pada salah satu user (JANGAN hapus user yang sedang login!)
3. Confirm dialog
4. Klik OK

**Expected Result:**
- ✅ Page redirect ke `admin/users.php`
- ✅ Muncul success message: "User berhasil dihapus!"
- ✅ User hilang dari tabel
- ✅ TIDAK ADA blank screen

**Test Refresh:**
5. Refresh browser

**Expected Result:**
- ✅ Page reload normal
- ✅ Success message hilang
- ✅ TIDAK submit delete lagi

---

### Test 4: Delete Reseller

**Steps:**
1. Dari admin panel, klik menu "Reseller"
2. Klik tombol "Hapus" pada salah satu reseller
3. Confirm dialog
4. Klik OK

**Expected Result:**
- ✅ Page redirect ke `admin/reseller.php`
- ✅ Muncul success message: "Reseller berhasil dihapus!"
- ✅ Reseller hilang dari tabel
- ✅ TIDAK ADA blank screen

**Test Refresh:**
5. Refresh browser

**Expected Result:**
- ✅ Page reload normal
- ✅ Success message hilang
- ✅ TIDAK submit delete lagi

---

## 🎯 Additional Tests

### Test 5: Add Operations

**Test Add Produk:**
1. Klik menu "Produk"
2. Klik tombol "➕ Tambah Produk"
3. Isi form:
   - Nama Produk: "Test Product"
   - Kategori: "test"
   - Harga: 100000
   - Cabang: Pilih salah satu
   - Deskripsi: "Test description"
4. Klik "Tambah Produk"

**Expected Result:**
- ✅ Redirect ke `admin/produk.php`
- ✅ Success message: "Produk berhasil ditambahkan!"
- ✅ Produk baru muncul di tabel
- ✅ TIDAK ADA blank screen

**Repeat for:**
- Add Cabang
- Add User
- Add Reseller

---

### Test 6: Edit Operations

**Test Edit Produk:**
1. Klik menu "Produk"
2. Klik tombol "Edit" pada salah satu produk
3. Ubah nama produk
4. Klik "Update Produk"

**Expected Result:**
- ✅ Redirect ke `admin/produk.php`
- ✅ Success message: "Produk berhasil diupdate!"
- ✅ Perubahan tersimpan
- ✅ TIDAK ADA blank screen

**Repeat for:**
- Edit Cabang
- Edit User
- Edit Reseller

---

## 🐛 Error Scenarios

### Test 7: Delete with Foreign Key Constraint

**Test Delete Cabang yang Masih Digunakan:**
1. Klik menu "Cabang"
2. Coba hapus cabang yang masih memiliki:
   - Users terkait
   - Produk terkait
   - Reseller terkait

**Expected Result:**
- ✅ Redirect ke `admin/cabang.php`
- ✅ Error message: "Error: Cannot delete..." (foreign key constraint)
- ✅ Cabang TIDAK terhapus
- ✅ TIDAK ADA blank screen

---

### Test 8: Cancel Delete

**Steps:**
1. Klik tombol "Hapus" pada data apapun
2. Pada confirm dialog, klik "Cancel"

**Expected Result:**
- ✅ Dialog tertutup
- ✅ Data TIDAK terhapus
- ✅ Tetap di halaman yang sama
- ✅ Tidak ada redirect

---

## 📊 Test Results Template

Copy template ini untuk mencatat hasil testing:

```
## Test Results - [Date]

### Test 1: Delete Produk
- [ ] Success message muncul
- [ ] Data terhapus
- [ ] No blank screen
- [ ] Refresh tidak submit ulang
- Notes: _______________

### Test 2: Delete Cabang
- [ ] Success message muncul
- [ ] Data terhapus
- [ ] No blank screen
- [ ] Refresh tidak submit ulang
- Notes: _______________

### Test 3: Delete Users
- [ ] Success message muncul
- [ ] Data terhapus
- [ ] No blank screen
- [ ] Refresh tidak submit ulang
- Notes: _______________

### Test 4: Delete Reseller
- [ ] Success message muncul
- [ ] Data terhapus
- [ ] No blank screen
- [ ] Refresh tidak submit ulang
- Notes: _______________

### Test 5: Add Operations
- [ ] Add Produk works
- [ ] Add Cabang works
- [ ] Add User works
- [ ] Add Reseller works
- Notes: _______________

### Test 6: Edit Operations
- [ ] Edit Produk works
- [ ] Edit Cabang works
- [ ] Edit User works
- [ ] Edit Reseller works
- Notes: _______________

### Test 7: Error Scenarios
- [ ] Foreign key constraint handled
- [ ] Error message displayed
- [ ] No blank screen on error
- Notes: _______________

### Test 8: Cancel Operations
- [ ] Cancel delete works
- [ ] No data changed
- Notes: _______________

## Overall Status
- [ ] All tests passed
- [ ] Some tests failed (see notes)
- [ ] Ready for production

## Issues Found:
1. _______________
2. _______________
3. _______________
```

---

## 🔍 Debugging Tips

### If Blank Screen Still Appears:

1. **Check PHP Error Log:**
   ```bash
   # Location varies by system
   tail -f /var/log/apache2/error.log
   # or
   tail -f /xampp/apache/logs/error.log
   ```

2. **Check Browser Console:**
   - Open Developer Tools (F12)
   - Check Console tab for JavaScript errors
   - Check Network tab for failed requests

3. **Enable PHP Error Display:**
   Add to top of PHP file:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

4. **Check Session:**
   Add debug code:
   ```php
   echo "<pre>";
   print_r($_SESSION);
   echo "</pre>";
   ```

5. **Check Headers:**
   Make sure no output before `header()` call
   - No echo, print, or HTML before `<?php`
   - No whitespace before `<?php`
   - No BOM in file encoding

---

## ✅ Success Criteria

All tests should pass with:
- ✅ No blank screens
- ✅ Success messages display correctly
- ✅ Data operations work as expected
- ✅ Refresh doesn't resubmit forms
- ✅ Error messages display properly
- ✅ URLs are clean after operations

---

## 📝 Notes

- Test on different browsers (Chrome, Firefox, Edge)
- Test with different screen sizes (desktop, tablet, mobile)
- Test with slow internet connection
- Test with multiple tabs open
- Test logout and login between operations

---

**Created:** 2024
**Version:** 1.0
**Status:** Ready for Testing
