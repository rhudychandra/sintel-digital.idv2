# Database Documentation - Sinar Telkom Dashboard System

## Overview
Database ini dirancang untuk mengelola sistem dashboard Sinar Telkom yang mencakup manajemen user, produk, pelanggan, penjualan, dan inventory.

## Database Information
- **Nama Database:** `sinar_telkom_dashboard`
- **Engine:** InnoDB
- **Charset:** utf8mb4_unicode_ci
- **Collation:** utf8mb4_unicode_ci

---

## Struktur Tabel

### 1. Tabel: `users`
Menyimpan data pengguna sistem.

**Kolom:**
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| user_id | INT (PK, AI) | ID unik user |
| username | VARCHAR(50) | Username untuk login (unique) |
| password | VARCHAR(255) | Password terenkripsi |
| full_name | VARCHAR(100) | Nama lengkap user |
| email | VARCHAR(100) | Email user (unique) |
| phone | VARCHAR(20) | Nomor telepon |
| role | ENUM | Role: admin, manager, sales, staff |
| status | ENUM | Status: active, inactive |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |
| last_login | TIMESTAMP | Waktu login terakhir |

**Sample Data:**
- Username: `admin`, Password: `password`, Role: `admin`
- Username: `manager1`, Password: `password`, Role: `manager`
- Username: `sales1`, Password: `password`, Role: `sales`

---

### 2. Tabel: `produk`
Menyimpan data produk/layanan Telkom.

**Kolom:**
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| produk_id | INT (PK, AI) | ID unik produk |
| kode_produk | VARCHAR(50) | Kode produk (unique) |
| nama_produk | VARCHAR(200) | Nama produk |
| kategori | ENUM | internet, tv_cable, phone, paket_bundling, enterprise |
| deskripsi | TEXT | Deskripsi produk |
| harga | DECIMAL(15,2) | Harga normal |
| harga_promo | DECIMAL(15,2) | Harga promo (nullable) |
| stok | INT | Jumlah stok |
| satuan | VARCHAR(20) | Satuan (unit, bulan, dll) |
| status | ENUM | active, inactive, discontinued |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |

**Kategori Produk:**
- `internet`: Paket internet (10 Mbps, 20 Mbps, 50 Mbps, 100 Mbps)
- `tv_cable`: TV Cable (Basic, Premium)
- `phone`: Telepon rumah
- `paket_bundling`: Triple Play (Internet + TV + Phone)
- `enterprise`: Layanan korporat

---

### 3. Tabel: `pelanggan`
Menyimpan data pelanggan.

**Kolom:**
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| pelanggan_id | INT (PK, AI) | ID unik pelanggan |
| kode_pelanggan | VARCHAR(50) | Kode pelanggan (unique) |
| nama_pelanggan | VARCHAR(200) | Nama pelanggan |
| email | VARCHAR(100) | Email pelanggan |
| phone | VARCHAR(20) | Nomor telepon |
| alamat | TEXT | Alamat lengkap |
| kota | VARCHAR(100) | Kota |
| provinsi | VARCHAR(100) | Provinsi |
| kode_pos | VARCHAR(10) | Kode pos |
| tipe_pelanggan | ENUM | individual, corporate |
| status | ENUM | active, inactive, suspended |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |

---

### 4. Tabel: `penjualan`
Menyimpan data transaksi penjualan (header).

**Kolom:**
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| penjualan_id | INT (PK, AI) | ID unik penjualan |
| no_invoice | VARCHAR(50) | Nomor invoice (unique) |
| tanggal_penjualan | DATE | Tanggal transaksi |
| pelanggan_id | INT (FK) | ID pelanggan |
| user_id | INT (FK) | ID sales/user |
| subtotal | DECIMAL(15,2) | Subtotal sebelum diskon |
| diskon | DECIMAL(15,2) | Total diskon |
| pajak | DECIMAL(15,2) | Total pajak (PPN) |
| total | DECIMAL(15,2) | Total akhir |
| metode_pembayaran | ENUM | cash, transfer, credit_card, debit_card, e-wallet |
| status_pembayaran | ENUM | pending, paid, cancelled, refunded |
| status_pengiriman | ENUM | pending, processing, shipped, delivered, cancelled |
| catatan | TEXT | Catatan tambahan |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |

---

### 5. Tabel: `detail_penjualan`
Menyimpan detail item per transaksi penjualan.

**Kolom:**
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| detail_id | INT (PK, AI) | ID unik detail |
| penjualan_id | INT (FK) | ID penjualan |
| produk_id | INT (FK) | ID produk |
| nama_produk | VARCHAR(200) | Nama produk (snapshot) |
| harga_satuan | DECIMAL(15,2) | Harga per unit |
| jumlah | INT | Jumlah item |
| diskon | DECIMAL(15,2) | Diskon per item |
| subtotal | DECIMAL(15,2) | Subtotal item |
| created_at | TIMESTAMP | Waktu pembuatan |

---

### 6. Tabel: `inventory`
Menyimpan riwayat pergerakan stok.

**Kolom:**
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| inventory_id | INT (PK, AI) | ID unik inventory |
| produk_id | INT (FK) | ID produk |
| tanggal | DATE | Tanggal transaksi |
| tipe_transaksi | ENUM | masuk, keluar, adjustment, return |
| jumlah | INT | Jumlah perubahan |
| stok_sebelum | INT | Stok sebelum transaksi |
| stok_sesudah | INT | Stok setelah transaksi |
| referensi | VARCHAR(100) | Nomor referensi (PO, Invoice, dll) |
| keterangan | TEXT | Keterangan tambahan |
| user_id | INT (FK) | ID user yang melakukan |
| created_at | TIMESTAMP | Waktu pembuatan |

---

## Views (Laporan)

### 1. `view_laporan_penjualan`
Laporan penjualan lengkap dengan informasi pelanggan dan sales.

**Kolom:**
- penjualan_id, no_invoice, tanggal_penjualan
- kode_pelanggan, nama_pelanggan
- sales_person (nama sales)
- subtotal, diskon, pajak, total
- metode_pembayaran, status_pembayaran, status_pengiriman

**Query:**
```sql
SELECT * FROM view_laporan_penjualan 
WHERE tanggal_penjualan BETWEEN '2024-01-01' AND '2024-12-31';
```

---

### 2. `view_stok_produk`
Monitoring stok produk dengan status stok.

**Kolom:**
- produk_id, kode_produk, nama_produk, kategori
- harga, harga_promo, stok, status
- status_stok (Low/Medium/High Stock)

**Query:**
```sql
SELECT * FROM view_stok_produk WHERE status_stok = 'Low Stock';
```

---

### 3. `view_top_selling_products`
Produk terlaris berdasarkan revenue.

**Kolom:**
- produk_id, kode_produk, nama_produk, kategori
- jumlah_transaksi, total_terjual, total_revenue

**Query:**
```sql
SELECT * FROM view_top_selling_products LIMIT 10;
```

---

### 4. `view_sales_performance`
Performa sales berdasarkan total penjualan.

**Kolom:**
- user_id, username, full_name, role
- total_transaksi, total_penjualan
- rata_rata_transaksi, transaksi_terbesar

**Query:**
```sql
SELECT * FROM view_sales_performance ORDER BY total_penjualan DESC;
```

---

## Stored Procedures

### 1. `sp_tambah_penjualan`
Menambah transaksi penjualan baru.

**Parameters:**
- IN: no_invoice, tanggal_penjualan, pelanggan_id, user_id, subtotal, diskon, pajak, total, metode_pembayaran
- OUT: penjualan_id

**Contoh Penggunaan:**
```sql
CALL sp_tambah_penjualan(
    'INV-2024-0006', '2024-01-20', 1, 3,
    500000.00, 50000.00, 45000.00, 495000.00, 'transfer',
    @penjualan_id
);
SELECT @penjualan_id;
```

---

### 2. `sp_update_stok`
Update stok produk dan catat di inventory.

**Parameters:**
- IN: produk_id, jumlah, tipe_transaksi, referensi, keterangan, user_id

**Contoh Penggunaan:**
```sql
CALL sp_update_stok(1, 50, 'masuk', 'PO-2024-002', 'Restock produk', 1);
```

---

## Cara Import Database

### Metode 1: Via phpMyAdmin
1. Buka phpMyAdmin di browser: `http://localhost/phpmyadmin`
2. Klik tab **"Import"**
3. Klik **"Choose File"** dan pilih `database.sql`
4. Klik **"Go"** untuk mengimport
5. Database `sinar_telkom_dashboard` akan otomatis dibuat

### Metode 2: Via Command Line
```bash
# Masuk ke direktori file
cd c:/xampp/htdocs/sinartelekomdashboardsystem

# Import database
mysql -u root -p < database.sql

# Atau jika tidak ada password
mysql -u root < database.sql
```

### Metode 3: Via MySQL Workbench
1. Buka MySQL Workbench
2. Connect ke server MySQL
3. File → Run SQL Script
4. Pilih file `database.sql`
5. Klik **"Run"**

---

## Konfigurasi Koneksi Database

### PHP (mysqli)
```php
<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'sinar_telkom_dashboard';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

### PHP (PDO)
```php
<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'sinar_telkom_dashboard';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

---

## Query Examples

### 1. Total Penjualan Per Bulan
```sql
SELECT 
    DATE_FORMAT(tanggal_penjualan, '%Y-%m') AS bulan,
    COUNT(*) AS jumlah_transaksi,
    SUM(total) AS total_penjualan
FROM penjualan
WHERE status_pembayaran = 'paid'
GROUP BY DATE_FORMAT(tanggal_penjualan, '%Y-%m')
ORDER BY bulan DESC;
```

### 2. Produk dengan Stok Menipis
```sql
SELECT 
    kode_produk,
    nama_produk,
    stok,
    kategori
FROM produk
WHERE stok <= 10 AND status = 'active'
ORDER BY stok ASC;
```

### 3. Top 5 Pelanggan
```sql
SELECT 
    p.nama_pelanggan,
    COUNT(pj.penjualan_id) AS total_transaksi,
    SUM(pj.total) AS total_belanja
FROM pelanggan p
JOIN penjualan pj ON p.pelanggan_id = pj.pelanggan_id
WHERE pj.status_pembayaran = 'paid'
GROUP BY p.pelanggan_id, p.nama_pelanggan
ORDER BY total_belanja DESC
LIMIT 5;
```

### 4. Penjualan Per Kategori Produk
```sql
SELECT 
    pr.kategori,
    COUNT(dp.detail_id) AS jumlah_item,
    SUM(dp.subtotal) AS total_revenue
FROM produk pr
JOIN detail_penjualan dp ON pr.produk_id = dp.produk_id
JOIN penjualan p ON dp.penjualan_id = p.penjualan_id
WHERE p.status_pembayaran = 'paid'
GROUP BY pr.kategori
ORDER BY total_revenue DESC;
```

---

## Security Notes

⚠️ **PENTING untuk Production:**

1. **Password Default:** 
   - Semua user menggunakan password: `password`
   - Hash: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`
   - **WAJIB diganti** untuk production!

2. **Database User:**
   - Jangan gunakan user `root` untuk aplikasi
   - Buat user khusus dengan privilege terbatas

3. **Backup:**
   - Lakukan backup database secara berkala
   - Simpan backup di lokasi yang aman

4. **SQL Injection:**
   - Gunakan prepared statements
   - Validasi semua input dari user

---

## Maintenance

### Backup Database
```bash
mysqldump -u root -p sinar_telkom_dashboard > backup_$(date +%Y%m%d).sql
```

### Restore Database
```bash
mysql -u root -p sinar_telkom_dashboard < backup_20240120.sql
```

### Optimize Tables
```sql
OPTIMIZE TABLE users, produk, pelanggan, penjualan, detail_penjualan, inventory;
```

---

## Support

Untuk pertanyaan atau bantuan terkait database, silakan hubungi tim development.

**Database Version:** 1.0  
**Last Updated:** 2024-01-20
