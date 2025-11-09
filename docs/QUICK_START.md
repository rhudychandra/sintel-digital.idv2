# Quick Start Guide - Sinar Telkom Dashboard System

## 🚀 Setup Lengkap (Urutan Penting!)

### Step 1: Import Database Utama
```bash
# Import database utama terlebih dahulu
mysql -u root -p < database.sql

# Atau via phpMyAdmin:
1. Buka phpMyAdmin
2. Create database: sinar_telkom_dashboard
3. Import file: database.sql
```

**✅ Hasil:** Database dasar dengan tabel users, produk, inventory, penjualan, dll.

### Step 2: Import Database Update Admin (PENTING!)
```bash
# Import update untuk fitur administrator
mysql -u root -p sinar_telkom_dashboard < database_update_admin.sql

# Atau via phpMyAdmin:
1. Pilih database: sinar_telkom_dashboard
2. Tab "Import"
3. Pilih file: database_update_admin.sql
4. Klik "Go"
```

**✅ Hasil:** 
- Tabel cabang, reseller, audit_log ditambahkan
- Kolom cabang_id ditambahkan ke tabel existing
- Views dan stored procedures dibuat
- User administrator default dibuat

### Step 3: Import User Baru (rhudychandra)
```bash
# Import user administrator baru
mysql -u root -p sinar_telkom_dashboard < add_admin_user.sql

# Atau via phpMyAdmin:
1. Pilih database: sinar_telkom_dashboard
2. Tab "Import"
3. Pilih file: add_admin_user.sql
4. Klik "Go"
```

**✅ Hasil:** User rhudychandra dengan password Tsel2025 dibuat

## 🔐 Login Credentials

### Administrator Accounts:
1. **Default Admin:**
   - Username: `administrator`
   - Password: `password` atau `admin`

2. **Rhudy Chandra (Baru):**
   - Username: `rhudychandra`
   - Password: `Tsel2025`

### Regular Users:
- admin / password (Admin)
- manager1 / password (Manager)
- sales1 / password (Sales)
- sales2 / password (Sales)
- staff1 / password (Staff)

## 📋 Verifikasi Setup

### Check 1: Verify Tables
```sql
USE sinar_telkom_dashboard;

-- Check tabel utama
SHOW TABLES;

-- Harus ada tabel:
-- users, produk, inventory, penjualan, pelanggan
-- cabang, reseller, audit_log (dari update)
```

### Check 2: Verify Cabang_id Column
```sql
-- Check kolom cabang_id di tabel users
DESCRIBE users;

-- Harus ada kolom: cabang_id
```

### Check 3: Verify Users
```sql
-- Check user administrator
SELECT user_id, username, full_name, role, status 
FROM users 
WHERE role = 'administrator';

-- Harus ada 2 users:
-- 1. administrator
-- 2. rhudychandra
```

### Check 4: Verify Views
```sql
-- Check views
SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW';

-- Harus ada:
-- view_admin_dashboard
-- view_sales_per_cabang
-- view_stock_per_cabang
-- view_reseller_performance
```

## ⚠️ Troubleshooting

### Error: "Unknown column 'cabang_id'"
**Penyebab:** database_update_admin.sql belum diimport

**Solusi:**
```bash
# Import database_update_admin.sql
mysql -u root -p sinar_telkom_dashboard < database_update_admin.sql
```

### Error: "Table 'cabang' doesn't exist"
**Penyebab:** database_update_admin.sql belum diimport

**Solusi:** Import database_update_admin.sql terlebih dahulu

### Error: "Duplicate entry for key 'username'"
**Penyebab:** User rhudychandra sudah ada

**Solusi:**
```sql
-- Hapus user existing
DELETE FROM users WHERE username = 'rhudychandra';

-- Atau update password
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'rhudychandra';
```

### Error: "Access denied for user"
**Penyebab:** MySQL credentials salah

**Solusi:** Check config.php dan update credentials

## 🎯 Test Login

### Test 1: Login Regular User
1. Buka: `http://localhost/sinartelekomdashboardsystem/`
2. Login dengan: admin / password
3. Harus masuk ke dashboard biasa
4. Tidak ada tombol "Administrator"

### Test 2: Login Administrator
1. Logout dari user sebelumnya
2. Login dengan: rhudychandra / Tsel2025
3. Harus masuk ke dashboard
4. Ada tombol "Administrator" (warna ungu)
5. Klik tombol tersebut
6. Masuk ke Admin Panel

### Test 3: Test CRUD
1. Di Admin Panel, klik "Produk"
2. Klik "Tambah Produk"
3. Isi form dan submit
4. Harus berhasil tanpa error

## 📊 Database Structure

### Tabel Utama (dari database.sql):
- users
- produk
- inventory
- penjualan
- detail_penjualan
- pelanggan

### Tabel Tambahan (dari database_update_admin.sql):
- cabang (branches)
- reseller
- audit_log

### Views (dari database_update_admin.sql):
- view_admin_dashboard
- view_sales_per_cabang
- view_stock_per_cabang
- view_reseller_performance

## 🔄 Urutan Import yang Benar

```
1. database.sql              ← Database dasar
2. database_update_admin.sql ← Update untuk admin features
3. add_admin_user.sql        ← User rhudychandra
```

**PENTING:** Jangan skip Step 2! Tanpa database_update_admin.sql, fitur administrator tidak akan berfungsi.

## ✅ Checklist Setup

- [ ] Import database.sql
- [ ] Import database_update_admin.sql
- [ ] Import add_admin_user.sql
- [ ] Verify tabel cabang exists
- [ ] Verify kolom cabang_id exists di users
- [ ] Verify user rhudychandra exists
- [ ] Test login dengan rhudychandra / Tsel2025
- [ ] Verify tombol Administrator muncul
- [ ] Test akses Admin Panel
- [ ] Test CRUD Produk
- [ ] Test CRUD Cabang
- [ ] Test CRUD Users
- [ ] Test CRUD Reseller

## 📞 Need Help?

Jika masih ada error:
1. Check urutan import database
2. Verify semua tabel ada
3. Check PHP error log
4. Review config.php
5. Clear browser cache
6. Logout dan login kembali

---

**Setup Time:** ~5 minutes  
**Difficulty:** Easy  
**Status:** ✅ Ready to Use
