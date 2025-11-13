# 💾 Cara Backup Database via phpMyAdmin

## ⚡ Quick Guide (2 Menit)

### 1️⃣ Login ke phpMyAdmin
```
Hostinger hPanel → Databases → pilih database → "Enter phpMyAdmin"
```

### 2️⃣ Pilih Database
Klik nama database Anda di sidebar kiri

### 3️⃣ Klik Tab "Export"
Tab ada di bagian atas, setelah Structure, SQL, Search, dll

### 4️⃣ Pilih Method: **Quick**
- ✅ Lebih cepat untuk backup rutin
- ✅ Sudah include semua tabel
- Format: SQL

### 5️⃣ Klik "Go"
File .sql akan otomatis terdownload

### ✅ Selesai!
File backup tersimpan di folder Downloads Anda

---

## 🔧 Backup Method: Quick vs Custom

### ✅ Method: Quick (DISARANKAN untuk backup sebelum reset)
```
✓ Cepat dan mudah
✓ Include semua tabel
✓ Include semua data
✓ Format: SQL
✓ Ukuran: Normal
✓ Waktu: 10-30 detik
```

**Kapan Pakai:**
- Backup rutin harian/mingguan
- Sebelum reset database
- Sebelum update major
- Backup darurat

---

### 🔧 Method: Custom (Untuk advanced backup)

Pilih **Custom** jika butuh:

#### A. Output Settings
```
☑ Save output to a file
Format: SQL
☐ Compression: None (atau pilih gzip untuk file lebih kecil)
```

#### B. Format-specific Options
```
Structure:
☑ Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER
☑ Add CREATE PROCEDURE / FUNCTION / EVENT
☑ Add IF NOT EXISTS

Data:
☑ Complete inserts (lebih aman untuk restore)
☑ Extended inserts (file lebih kecil)
☐ Maximal length of created query: 50000
```

#### C. Object Creation Options
```
☑ Add CREATE DATABASE / USE statement
```

#### D. Klik "Go"

---

## 📋 Backup Settings Terbaik

### Untuk Backup Sebelum Reset:

```
Method: Custom
☑ Structure: Add DROP TABLE
☑ Structure: Add CREATE
☑ Data: Complete inserts
☑ Object creation: Add CREATE DATABASE
Format: SQL
Compression: None (atau gzip jika file besar)
```

**Download file akan bernama seperti:**
```
namadatabase_2025-11-13.sql
atau
namadatabase_2025-11-13.sql.gz (jika pakai compression)
```

---

## 🗂️ Penamaan File Backup (Best Practice)

Format yang disarankan:
```
sintel_db_backup_YYYYMMDD_HHMM.sql

Contoh:
sintel_db_backup_20251113_1430.sql
sintel_db_backup_before_reset_20251113.sql
sintel_db_production_20251113.sql
```

**Tips Penamaan:**
- ✅ Include tanggal & waktu
- ✅ Include keterangan (before_reset, production, testing)
- ✅ Gunakan underscore, bukan spasi
- ✅ Lowercase semua

---

## 💡 Tips Backup

### 1. Backup Berlapis
```
1 backup → Komputer lokal (Downloads)
1 backup → Google Drive / Dropbox
1 backup → External HDD / USB
```

### 2. Naming Convention
```
[namadb]_[environment]_[tanggal]_[waktu].sql

Contoh:
sintel_production_20251113_1430.sql
sintel_testing_20251113_1445.sql
sintel_before_reset_20251113_1500.sql
```

### 3. Backup Schedule
```
🟢 Sebelum setiap perubahan besar: WAJIB
🟡 Backup harian: Disarankan (jam 00:00 malam)
🟡 Backup mingguan: Minimal (Minggu malam)
🔴 Backup bulanan: Archive / long-term storage
```

### 4. Test Restore
Sesekali test restore backup Anda di environment testing untuk memastikan backup bisa dipakai!

---

## 📦 Backup via Hostinger hPanel (Alternatif)

### Automatic Backup (Jika Ada)
```
1. Hostinger hPanel → Databases
2. Pilih database → Manage
3. Tab "Backup/Export"
4. Klik "Export" → Download
```

### Scheduled Backup
```
Beberapa plan Hostinger punya automatic backup:
- Business plan: Daily backup
- Premium plan: Weekly backup
- Bisa restore dari hPanel langsung
```

---

## 🔄 Cara Restore Backup

### Via phpMyAdmin:

```
1. Login phpMyAdmin
2. Pilih database
3. Klik tab "Import"
4. Klik "Choose File" → pilih file backup .sql
5. Scroll ke bawah → Klik "Go"
6. Tunggu sampai selesai
7. ✅ Database ter-restore!
```

### Via Hostinger hPanel:

```
1. Databases → Manage → Import
2. Upload file .sql
3. Execute import
4. ✅ Done!
```

---

## 📊 Ukuran File Backup

| Data Size | Backup Size (SQL) | Backup Size (gzip) | Download Time |
|-----------|-------------------|---------------------|---------------|
| Small (< 10MB) | < 10MB | < 2MB | 5-10 detik |
| Medium (10-100MB) | 10-100MB | 5-20MB | 15-30 detik |
| Large (100MB-1GB) | 100MB-1GB | 20-200MB | 1-5 menit |
| Very Large (> 1GB) | > 1GB | > 200MB | 5-15 menit |

**Rekomendasi:**
- Jika file > 50MB, gunakan **gzip compression**
- Jika file > 500MB, pertimbangkan backup via SSH atau FTP langsung

---

## 🆘 Troubleshooting

### Error: "Script timeout"
```
Solusi:
1. Gunakan compression (gzip)
2. Export per tabel (bukan sekaligus)
3. Gunakan SSH jika available
4. Contact Hostinger support untuk backup via ticket
```

### Error: "Maximum execution time exceeded"
```
Solusi:
1. Export data in smaller chunks
2. Export structure first, then data
3. Use Hostinger automatic backup feature
```

### File .gz tidak bisa dibuka
```
Solusi:
Windows: Gunakan 7-Zip atau WinRAR
Mac: Buka Terminal, ketik: gunzip namafile.sql.gz
Online: Gunakan gunzip.org (upload & extract online)
```

---

## ✅ Checklist Backup Lengkap

### Sebelum Backup:
- [ ] Pastikan tidak ada transaksi aktif
- [ ] Catat jumlah total records di tabel utama
- [ ] Siapkan tempat penyimpanan (min 3x size database)

### Saat Backup:
- [ ] Login phpMyAdmin
- [ ] Pilih database yang benar
- [ ] Method: Quick atau Custom
- [ ] Setting sesuai kebutuhan
- [ ] Klik "Go" dan tunggu
- [ ] File berhasil terdownload

### Setelah Backup:
- [ ] Cek ukuran file (jangan 0 bytes!)
- [ ] Rename file sesuai convention
- [ ] Upload ke cloud storage (Google Drive/Dropbox)
- [ ] Copy ke external HDD/USB
- [ ] Catat lokasi backup di dokumentasi
- [ ] (Optional) Test restore di local/testing

---

## 🎯 Backup Sebelum Reset - Step by Step

```
1. Login phpMyAdmin di Hostinger
2. Pilih database di sidebar kiri
3. Klik tab "Export"
4. Method: Quick
5. Format: SQL
6. Klik "Go"
7. File download otomatis
8. Rename file: sintel_before_reset_20251113.sql
9. Upload ke Google Drive
10. ✅ Aman untuk jalankan reset!
```

---

## 📞 Bantuan Lebih Lanjut

**Hostinger Support:**
- Live Chat 24/7
- Bisa request manual backup via ticket

**phpMyAdmin Documentation:**
- https://docs.phpmyadmin.net/

**Restore Gagal?**
- Check error message
- Pastikan database kosong dulu (atau gunakan DROP TABLE)
- Import manual per tabel jika perlu

---

## 🔐 Security Tips

- ❌ Jangan share file backup di public
- ❌ Jangan upload backup ke repository GitHub
- ✅ Simpan di private cloud storage
- ✅ Encrypt file jika berisi data sensitif
- ✅ Set permission cloud storage ke private
- ✅ Delete old backups dari server (simpan di local)

---

**💡 Pro Tip:** Buat folder khusus untuk backup dengan struktur:
```
Backups/
  ├── Daily/
  │   ├── sintel_20251113.sql
  │   ├── sintel_20251112.sql
  │   └── sintel_20251111.sql
  ├── Weekly/
  │   ├── sintel_week46_2025.sql
  │   └── sintel_week45_2025.sql
  ├── Before_Changes/
  │   ├── sintel_before_reset_20251113.sql
  │   └── sintel_before_migration_20251110.sql
  └── Archive/
      └── sintel_monthly_202511.sql
```

**🎯 INGAT: Backup bukan opsional, tapi WAJIB!**
