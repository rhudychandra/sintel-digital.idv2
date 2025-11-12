# Import Database Order (phpMyAdmin)

Gunakan urutan ini jika database Anda kosong di phpMyAdmin. Semua file berada di folder `database/`.

Catatan penting:
- Tidak perlu install ulang XAMPP. Cukup import SQL secara berurutan.
- Jalankan 1 file per langkah. Jika ada error "table already exists" atau "column already exists", lanjutkan ke langkah berikutnya.
- Jangan jalankan dua migrasi yang saling bertentangan untuk payment fields. Pilih salah satu sesuai urutan di bawah.

## Langkah 0 – Pastikan MySQL berjalan
- Buka XAMPP Control Panel, Start Apache dan MySQL.
- phpMyAdmin: http://localhost/phpmyadmin

## Langkah 1 – Import skema dasar + sample
- File: `database/database.sql`
- Hasil: membuat database `sinar_telkom_dashboard` dan tabel dasar: `users`, `produk`, `pelanggan`, `penjualan`, `detail_penjualan`, `inventory`, plus beberapa VIEW/PROCEDURE dasar.

Verifikasi cepat (opsional, jalankan di SQL phpMyAdmin setelah import):
```
SHOW DATABASES LIKE 'sinar_telkom_dashboard';
USE sinar_telkom_dashboard;
SHOW TABLES;
```

## Langkah 2 – Tambah fitur Admin: cabang/reseller dan kolom relasi
- File: `database/migrations/database_update_admin.sql`
- Hasil: membuat tabel `cabang`, `reseller`, menambah kolom `cabang_id`/`reseller_id` di beberapa tabel dan seed contoh data.
- Catatan: script ini juga membuat VIEW yang mungkin masih pakai kolom lama (total_harga). Kita perbaiki di langkah 2b.

### Langkah 2b – Perbaiki VIEW agar sesuai struktur terbaru
- File: `database/migrations/create_views_only.sql`
- Hasil: override VIEW agar konsisten dengan kolom yang saat ini dipakai aplikasi.

Verifikasi cepat:
```
SHOW TABLES LIKE 'cabang';
SHOW TABLES LIKE 'reseller';
DESCRIBE users;  -- pastikan ada cabang_id
DESCRIBE penjualan; -- pastikan ada cabang_id, reseller_id
```

## Langkah 3 – Kategori produk modern
- File: `database/migrations/create_kategori_table.sql`
- File: `database/migrations/fix_kategori_mapping.sql` (opsional tapi disarankan)
- Hasil: buat tabel `kategori_produk`, ubah `produk.kategori` ke VARCHAR, dan petakan kategori default.

## Langkah 4 – Sistem approval inventory
- Pilih salah satu file (disarankan pilih yang ini saja):
  - Disarankan: `database/migrations/add_approval_system.sql`
  - Alternatif (jangan jalankan keduanya): `database/migrations/add_inventory_status_approval.sql`
- Hasil: menambah kolom `inventory.status_approval` + index.

Verifikasi cepat:
```
DESCRIBE inventory;  -- pastikan ada status_approval
```

## Langkah 5 – Standarisasi nilai pembayaran penjualan
- Pilih salah satu (disarankan file ENUM baru):
  - Disarankan: `database/migrations/2025-11-12_update_penjualan_payment_enums.sql`
  - Alternatif (JANGAN digabung): `database/migrations/update_payment_fields.sql` (mengubah ke VARCHAR bebas)
- Hasil: normalisasi `metode_pembayaran` dan `status_pembayaran` sesuai standar aplikasi terbaru.

## Langkah 6 – Tabel Finance: Setoran Harian & Evidence
- File: `database/migrations/create_setoran_harian_table.sql`
- File: `database/migrations/create_setoran_evidence_table.sql`
- File: `database/migrations/alter_setoran_evidence_add_nominal_bank_pengirim.sql`
- Hasil: membuat tabel `setoran_harian` dan `setoran_evidence` serta kolom tambahan `nominal`, `bank_pengirim`.

Verifikasi cepat:
```
SHOW TABLES LIKE 'setoran_harian';
SHOW TABLES LIKE 'setoran_evidence';
DESCRIBE setoran_evidence; -- pastikan nominal & bank_pengirim ada
```

## Langkah 7 – Kolom tambahan yang mungkin dibutuhkan
- File: `database/migrations/add_cabang_email_and_timestamps.sql`
- File: `database/migrations/add_reseller_missing_columns.sql`
- File: `database/migrations/fix_reseller_columns.sql`
- Hasil: melengkapi kolom opsional untuk konsistensi dengan aplikasi dan dump lama.

## Langkah 8 – Role tambahan (opsional tapi direkomendasikan)
- File: `database/migrations/add_administrator_role.sql`
- File: `database/migrations/database_add_supervisor_role.sql`
- Hasil: menambahkan role `administrator`, `supervisor`, `finance` pada tabel `users`.

## Langkah 9 – Seed user admin
- Pilih salah satu:
  - Jika BELUM menjalankan langkah 2: `database/seeds/add_admin_user.sql`
  - Jika SUDAH menjalankan langkah 2 (sudah ada cabang_id): `database/seeds/add_admin_user_with_cabang.sql`

Verifikasi login (info):
- Username: `rhudychandra`
- Password: `Tsel2025`

## Cek kesehatan akhir (opsional)
Jalankan query cepat ini untuk validasi:
```
-- Tabel wajib
SHOW TABLES WHERE Tables_in_sinar_telkom_dashboard IN (
  'users','produk','pelanggan','penjualan','detail_penjualan','inventory',
  'cabang','reseller','setoran_harian','setoran_evidence'
);

-- Kolom wajib
SHOW COLUMNS FROM users LIKE 'cabang_id';
SHOW COLUMNS FROM inventory LIKE 'status_approval';
SHOW COLUMNS FROM penjualan LIKE 'cabang_id';
SHOW COLUMNS FROM penjualan LIKE 'reseller_id';
```

## Troubleshooting
- Error "table already exists" atau "column already exists": aman untuk diabaikan, lanjut ke langkah berikut.
- Jika VIEW tampak salah atau error, jalankan ulang `create_views_only.sql`.
- Jika phpMyAdmin timeout pada file besar, impor per file satu per satu (bukan zip).

---
Dokumen ini memastikan Anda bisa men-setup database kosong sampai aplikasi berjalan sesuai fitur cabang/reseller, approval inventory, dan modul finance.
