# ✅ CHECKLIST LENGKAP: Backup & Reset Database

## 📋 Workflow Lengkap

```
BACKUP → VERIFIKASI → RESET → KONFIRMASI
```

---

## TAHAP 1: BACKUP DATABASE 💾

### A. Login ke phpMyAdmin
```
□ Buka Hostinger hPanel
□ Klik "Databases"
□ Pilih database Anda
□ Klik "Enter phpMyAdmin"
□ Berhasil masuk phpMyAdmin
```

### B. Export Database
```
□ Klik database di sidebar kiri
□ Klik tab "Export" di atas
□ Pilih method: "Quick"
□ Format: "SQL"
□ Klik "Go"
□ File berhasil terdownload
```

### C. Verifikasi File Backup
```
□ Cek folder Downloads
□ File ada: [namadb].sql
□ Ukuran file > 0 KB (bukan 0!)
□ Rename file: sintel_before_reset_YYYYMMDD.sql
   Contoh: sintel_before_reset_20251113.sql
```

### D. Backup File ke Multiple Locations
```
□ Simpan di komputer lokal (Downloads)
□ Upload ke Google Drive / Dropbox
□ (Optional) Copy ke external HDD/USB
□ (Optional) Kirim via email ke diri sendiri
```

---

## TAHAP 2: PERSIAPAN RESET 🔧

### A. Buka File SQL
```
□ Buka file: reset_all_data.sql (di folder database/migrations/)
□ Baca isi file, pastikan paham yang akan dihapus
□ Copy SEMUA isi file
```

### B. Konfirmasi Tim
```
□ Informasikan tim akan ada reset database
□ Pastikan tidak ada transaksi aktif
□ Catat waktu eksekusi reset
□ Siapkan waktu maintenance (jika perlu)
```

---

## TAHAP 3: EKSEKUSI RESET 🚀

### A. Kembali ke phpMyAdmin
```
□ Masih di phpMyAdmin (atau login lagi)
□ Database sudah terpilih
□ Tidak ada query yang sedang running
```

### B. Jalankan Reset Query
```
□ Klik tab "SQL"
□ Paste query dari file reset_all_data.sql
□ Double-check query yang akan dijalankan
□ Klik "Go"
```

### C. Tunggu Proses
```
□ Jangan refresh atau close browser
□ Tunggu sampai muncul pesan sukses
□ Lihat hasil verifikasi di bawah
```

---

## TAHAP 4: VERIFIKASI HASIL ✅

### A. Cek Output Query
```
□ Semua query executed successfully (hijau)
□ Tidak ada error message (merah)
□ Tabel verifikasi muncul di bawah
```

### B. Verifikasi Data (Harusnya Semua 0)
```
□ PENJUALAN: 0
□ DETAIL_PENJUALAN: 0
□ INVENTORY: 0
□ PELANGGAN: 0
□ PRODUK_STOK_>_0: 0
```

### C. Cek Manual di Website
```
□ Login ke dashboard admin
□ Buka "Laporan Setoran Global"
□ Semua nilai menunjukkan 0 atau Rp 0
□ Buka "Inventory" → Stock semua 0
□ Buka "Penjualan" → Tidak ada data
```

### D. Cek Master Data (Harusnya Tetap Ada)
```
□ Master Produk masih ada
□ Master Cabang masih ada
□ Master Reseller masih ada
□ User accounts masih ada
□ Kategori produk masih ada
```

---

## TAHAP 5: DOKUMENTASI 📝

### A. Catat Detail Reset
```
□ Tanggal & waktu reset: _______________
□ User yang melakukan: _______________
□ Backup file location: _______________
□ Jumlah data dihapus:
   - Penjualan: ___ records
   - Detail penjualan: ___ records
   - Inventory: ___ records
   - Pelanggan: ___ records
```

### B. Informasikan Tim
```
□ Email/chat tim: "Database reset selesai"
□ Sistem ready untuk input data baru
□ Share backup location (jika diperlukan)
```

---

## 🆘 EMERGENCY: Jika Ada Masalah

### Jika Reset Gagal
```
1. □ JANGAN panic
2. □ Screenshot error message
3. □ JANGAN jalankan query lagi
4. □ Restore dari backup:
   - phpMyAdmin → Import
   - Pilih file backup
   - Klik Go
5. □ Contact support/developer
```

### Jika Salah Database
```
1. □ SEGERA STOP
2. □ Restore dari backup IMMEDIATELY
3. □ Verifikasi database name sebelum retry
```

### Jika Backup Tidak Ada
```
1. □ Cek Hostinger automatic backup
2. □ hPanel → Databases → Backups
3. □ Restore dari automatic backup
4. □ (Pelajari lesson: ALWAYS backup!)
```

---

## 📊 REFERENCE CEPAT

### File Penting
```
📁 database/migrations/
  ├── reset_all_data.sql           → Query reset
  ├── RESET_QUICK_GUIDE.md         → Panduan reset
  ├── BACKUP_QUICK_GUIDE.md        → Panduan backup
  ├── CARA_BACKUP_DATABASE.md      → Backup lengkap
  └── CHECKLIST.md                 → File ini
```

### Waktu Estimasi
```
Backup database: 1-3 menit
Persiapan reset: 2-5 menit
Eksekusi reset: 10-30 detik
Verifikasi: 2-5 menit
─────────────────────────────
Total: ~10-15 menit
```

### Data Terhapus vs Tersimpan
```
DIHAPUS ❌:
- Penjualan
- Detail penjualan
- Inventory records
- Pelanggan
- Stock produk (reset ke 0)

AMAN ✅:
- Master produk
- Master cabang
- Master reseller
- User accounts
- Kategori produk
```

---

## 🎯 FINAL CHECK SEBELUM RESET

```
Saya sudah:
□ Backup database
□ Verifikasi file backup (not 0 KB)
□ Upload backup ke cloud storage
□ Baca dan paham query yang akan dijalankan
□ Konfirmasi dengan tim
□ Yakin 100% ingin reset
□ Siap dengan konsekuensinya

→ ✅ READY TO RESET!
```

---

## 📞 Kontak Darurat

```
Hostinger Support:
- Live Chat 24/7 di hPanel
- https://www.hostinger.com/cpanel-login

Developer:
- [Isi contact developer di sini]

Backup Location:
- Local: [path di komputer]
- Cloud: [link Google Drive/Dropbox]
```

---

**🚨 PENTING: Jangan skip BACKUP! Ini safety net Anda!**

**✅ Selamat melakukan reset database dengan aman!**
