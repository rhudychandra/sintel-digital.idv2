# 🚀 Quick Guide: Reset Database di Hostinger

## ⚡ Cara Tercepat (5 Menit)

### 1️⃣ Backup Dulu!
```
Hostinger hPanel → Databases → Manage → Backup/Export → Download
```

### 2️⃣ Login phpMyAdmin
```
Hostinger hPanel → Databases → pilih database → "Enter phpMyAdmin"
```

### 3️⃣ Buka Tab SQL
Klik tab **"SQL"** di bagian atas

### 4️⃣ Copy-Paste Query Ini

```sql
-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Delete semua data transaksi
DELETE FROM detail_penjualan;
DELETE FROM penjualan;
DELETE FROM inventory;
DELETE FROM pelanggan;

-- Reset stock produk ke 0
UPDATE produk SET stok = 0;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verifikasi (harusnya semua 0)
SELECT 'PENJUALAN' as Tabel, COUNT(*) as Total FROM penjualan
UNION ALL
SELECT 'DETAIL_PENJUALAN', COUNT(*) FROM detail_penjualan
UNION ALL
SELECT 'INVENTORY', COUNT(*) FROM inventory
UNION ALL
SELECT 'PELANGGAN', COUNT(*) FROM pelanggan
UNION ALL
SELECT 'PRODUK_STOK_>_0', COUNT(*) FROM produk WHERE stok > 0;
```

### 5️⃣ Klik "Go"

Tunggu sampai selesai, lihat hasil verifikasi.

### ✅ Selesai!

Semua data transaksi terhapus, master data tetap aman.

---

## 📋 Apa yang Dihapus?
- ✅ Semua penjualan
- ✅ Semua detail penjualan
- ✅ Semua inventory records
- ✅ Semua pelanggan
- ✅ Stock produk reset ke 0

## 🔒 Apa yang Aman?
- ✅ Master produk
- ✅ Master cabang
- ✅ Master reseller
- ✅ User accounts
- ✅ Kategori produk

---

## 🆘 Troubleshooting

**Error: Foreign key constraint fails**
```sql
-- Jalankan ini dulu:
SET FOREIGN_KEY_CHECKS = 0;
```

**Ingin reset ID counter juga?**
```sql
ALTER TABLE penjualan AUTO_INCREMENT = 1;
ALTER TABLE detail_penjualan AUTO_INCREMENT = 1;
ALTER TABLE inventory AUTO_INCREMENT = 1;
ALTER TABLE pelanggan AUTO_INCREMENT = 1;
```

**Restore dari backup?**
```
phpMyAdmin → Import → Pilih file .sql backup → Go
```

---

## ⚠️ Checklist

- [ ] ✅ Sudah backup database
- [ ] ✅ Yakin 100% ingin reset
- [ ] ✅ Tim sudah dikonfirmasi
- [ ] ✅ Query sudah di-copy
- [ ] ✅ Klik "Go" di phpMyAdmin
- [ ] ✅ Verifikasi semua 0

---

**💡 Tips:** Simpan file SQL (`reset_all_data.sql`) di komputer, jadi kapan saja butuh reset tinggal buka file itu lagi.
