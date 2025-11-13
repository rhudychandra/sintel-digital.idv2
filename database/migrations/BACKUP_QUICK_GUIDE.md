# 💾 Quick Backup Guide - phpMyAdmin

## 🚀 Cara Tercepat (1 Menit)

### Step 1: Login phpMyAdmin
```
Hostinger hPanel → Databases → [Pilih Database] → "Enter phpMyAdmin"
```

### Step 2: Klik Tab "Export"
```
Tab ada di atas: Structure | SQL | Search | Query | Export ← (klik ini)
```

### Step 3: Pilih Quick Method
```
○ Quick - display only the minimal options
● Custom - display all possible options

Pilih: Quick ✅
Format: SQL ✅
```

### Step 4: Klik "Go"
```
File akan otomatis download dengan nama:
[namadatabase].sql
```

### ✅ Selesai!
```
File backup ada di folder Downloads Anda
Rename jadi: sintel_backup_20251113.sql
Upload ke Google Drive / Dropbox
```

---

## 📸 Visual Guide

```
┌─────────────────────────────────────────┐
│  phpMyAdmin                              │
├─────────────────────────────────────────┤
│  📁 Database: sintel_db                 │
├─────────────────────────────────────────┤
│  [Structure] [SQL] [Search] [Export] ← KLIK INI
├─────────────────────────────────────────┤
│                                          │
│  Export method:                          │
│  ⚪ Quick - display minimal options   ← PILIH INI
│  ⚫ Custom - display all options         │
│                                          │
│  Format: SQL ✓                           │
│                                          │
│  [ Go ] ← KLIK INI                       │
│                                          │
└─────────────────────────────────────────┘
         ↓
    💾 Download
         ↓
📥 sintel_db.sql (tersimpan di Downloads)
```

---

## ⚠️ PENTING Sebelum Reset Database

### Checklist:
```
☑ 1. Backup database (cara di atas)
☑ 2. Download file backup
☑ 3. Cek ukuran file (pastikan tidak 0 KB)
☑ 4. Rename file: sintel_before_reset_20251113.sql
☑ 5. Upload ke Google Drive atau cloud storage
☑ 6. (Optional) Kirim copy via email ke diri sendiri
☑ 7. Yakin 100% file backup sudah aman
☑ 8. Baru jalankan reset!
```

---

## 🔄 Cara Restore (Jika Perlu)

```
1. phpMyAdmin → Pilih database
2. Klik tab "Import"
3. Klik "Choose File" → pilih file .sql
4. Scroll ke bawah
5. Klik "Go"
6. ✅ Database ter-restore!
```

---

## 💡 Tips

### Penamaan File:
```
✅ BAGUS:
   sintel_backup_20251113_1430.sql
   sintel_before_reset_20251113.sql
   
❌ BURUK:
   database.sql
   backup.sql
   new.sql
```

### Penyimpanan Aman:
```
1️⃣ Komputer lokal (Downloads)
2️⃣ Google Drive / Dropbox
3️⃣ External HDD / USB
```

### Ukuran File Normal:
```
Small database: 1-10 MB
Medium database: 10-50 MB
Large database: 50-500 MB
```

**Jika file 0 KB = backup gagal, ulangi!**

---

## 🆘 Troubleshooting

### File terlalu besar?
```
Gunakan Custom method dengan compression:
Method: Custom
Compression: gzip
```

### Timeout saat backup?
```
Export per tabel:
1. Pilih tabel (centang)
2. Export selected tables
3. Ulangi untuk tabel lain
```

### Lupa backup?
```
Cek automatic backup Hostinger:
hPanel → Databases → Backups
(jika plan support auto backup)
```

---

## ✅ Ready to Reset?

```
Setelah backup selesai:

1. ✅ File backup sudah download
2. ✅ File size tidak 0 KB
3. ✅ Sudah upload ke cloud
4. ✅ Yakin 100%

→ Sekarang aman untuk jalankan reset_all_data.sql! 🚀
```

---

**🎯 Ingat: BACKUP DULU, RESET KEMUDIAN!**
