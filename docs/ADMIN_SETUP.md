# Quick Setup Guide - Administrator Feature

## 🚀 Langkah-Langkah Setup

### Step 1: Import Database Update
```bash
# Buka phpMyAdmin
# Atau via command line:
mysql -u root -p sinar_telkom_dashboard < database_update_admin.sql
```

**Apa yang dilakukan:**
- Menambah tabel `cabang` (branches)
- Menambah tabel `reseller`
- Menambah tabel `audit_log`
- Update tabel existing dengan kolom `cabang_id`
- Insert sample data untuk cabang dan reseller
- Menambah user dengan role `administrator`
- Create views untuk reporting
- Create stored procedures

### Step 2: Verify Database
```sql
-- Check tabel baru
SHOW TABLES LIKE '%cabang%';
SHOW TABLES LIKE '%reseller%';

-- Check user administrator
SELECT * FROM users WHERE role = 'administrator';

-- Check views
SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW';
```

### Step 3: Test Login Administrator
1. Buka: `http://localhost/sinartelekomdashboardsystem/`
2. Login dengan:
   - Username: `administrator`
   - Password: `password`
3. Setelah login, akan muncul tombol **"Administrator"** (warna ungu)
4. Klik tombol tersebut untuk masuk ke Admin Panel

### Step 4: Test Fitur CRUD
1. **Test Produk:**
   - Klik menu "Produk"
   - Tambah produk baru
   - Edit produk
   - Hapus produk

2. **Test Cabang:**
   - Klik menu "Cabang"
   - Lihat daftar cabang
   - Tambah cabang baru

3. **Test Users:**
   - Klik menu "Users"
   - Lihat semua users
   - Tambah user baru

4. **Test Reseller:**
   - Klik menu "Reseller"
   - Lihat daftar reseller
   - Tambah reseller baru

## ✅ Checklist Verifikasi

- [ ] Database update berhasil diimport
- [ ] Tabel `cabang` ada dan berisi data
- [ ] Tabel `reseller` ada dan berisi data
- [ ] User `administrator` ada di database
- [ ] Bisa login dengan user administrator
- [ ] Tombol "Administrator" muncul di dashboard
- [ ] Bisa akses Admin Panel
- [ ] Menu Produk berfungsi (CRUD)
- [ ] Menu Cabang berfungsi (CRUD)
- [ ] Menu Users berfungsi (CRUD)
- [ ] Menu Reseller berfungsi (CRUD)
- [ ] Menu Penjualan menampilkan data
- [ ] Menu Stock menampilkan data
- [ ] Menu Grafik menampilkan laporan

## 🔧 Troubleshooting

### Problem: Database import error
```
Solution:
1. Pastikan database sinar_telkom_dashboard sudah ada
2. Pastikan database.sql sudah diimport terlebih dahulu
3. Check MySQL version (minimal 5.7)
```

### Problem: User administrator tidak bisa login
```
Solution:
1. Check di database: SELECT * FROM users WHERE username='administrator';
2. Pastikan password di login.php accept 'password' atau 'admin'
3. Clear browser cache dan cookies
```

### Problem: Tombol Administrator tidak muncul
```
Solution:
1. Check role user di database
2. Pastikan dashboard.php sudah diupdate
3. Clear browser cache
4. Logout dan login kembali
```

### Problem: Error "Table doesn't exist"
```
Solution:
1. Import database_update_admin.sql
2. Verify dengan: SHOW TABLES;
3. Check apakah tabel cabang dan reseller ada
```

### Problem: CRUD tidak berfungsi
```
Solution:
1. Check PHP error log
2. Verify database connection di config.php
3. Check file permissions
4. Pastikan prepared statements berfungsi
```

## 📊 Sample Data

### Cabang (5 cabang)
- Jakarta Pusat (JKT001)
- Bandung (BDG001)
- Surabaya (SBY001)
- Medan (MDN001)
- Denpasar (DPS001)

### Reseller (5 reseller)
- Toko Elektronik Jaya (RSL001)
- Mega Telkom Store (RSL002)
- Surabaya Tech (RSL003)
- Medan Digital (RSL004)
- Bali Gadget Center (RSL005)

### Users
- administrator / password (Administrator)
- admin / password (Admin)
- manager1 / password (Manager)
- sales1 / password (Sales)
- sales2 / password (Sales)
- staff1 / password (Staff)

## 🎯 Quick Test Commands

### Test Database Views
```sql
-- Dashboard statistics
SELECT * FROM view_admin_dashboard;

-- Sales per cabang
SELECT * FROM view_sales_per_cabang;

-- Stock per cabang
SELECT * FROM view_stock_per_cabang;

-- Reseller performance
SELECT * FROM view_reseller_performance;
```

### Test Stored Procedure
```sql
-- Get sales by date range (all branches)
CALL sp_get_sales_by_daterange('2024-01-01', '2024-12-31', NULL);

-- Get sales by date range (specific branch)
CALL sp_get_sales_by_daterange('2024-01-01', '2024-12-31', 1);
```

## 📝 Next Steps

Setelah setup berhasil:

1. **Customize Data:**
   - Update sample data sesuai kebutuhan
   - Tambah cabang real
   - Tambah user real
   - Tambah reseller real

2. **Test Thoroughly:**
   - Test semua fitur CRUD
   - Test dengan berbagai role
   - Test error handling
   - Test validation

3. **Security:**
   - Ganti password default
   - Review access control
   - Enable audit logging
   - Setup backup

4. **Production:**
   - Deploy ke server production
   - Setup SSL/HTTPS
   - Configure firewall
   - Monitor performance

## 📞 Need Help?

Jika mengalami masalah:
1. Check ADMIN_README.md untuk dokumentasi lengkap
2. Review source code di folder admin/
3. Check PHP error log
4. Verify database structure
5. Hubungi tim development

---

**Setup Time:** ~10 minutes  
**Difficulty:** Easy  
**Status:** ✅ Ready to Use
