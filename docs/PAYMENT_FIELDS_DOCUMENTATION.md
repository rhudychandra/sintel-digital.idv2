# Dokumentasi: Metode Pembayaran dan Status Pembayaran

## 📋 Overview

Fitur ini menambahkan kemampuan untuk mencatat **Metode Pembayaran** dan **Status Pembayaran** pada setiap transaksi penjualan di sistem inventory.

---

## ✨ Fitur yang Ditambahkan

### 1. **Metode Pembayaran**
Pilihan metode pembayaran yang tersedia:
- **Transfer** - Pembayaran via transfer bank
- **Cash** - Pembayaran tunai
- **Budget Komitmen** - Pembayaran menggunakan budget komitmen
- **Finpay** - Pembayaran via Finpay

### 2. **Status Pembayaran**
Pilihan status pembayaran yang tersedia:
- **Paid** (Lunas) - Pembayaran sudah diterima
- **Pending** (Menunggu) - Menunggu pembayaran
- **TOP** (Term of Payment) - Pembayaran dengan termin
- **Cancelled** (Dibatalkan) - Transaksi dibatalkan

---

## 🔧 Perubahan Teknis

### A. Database Update

**File:** `update_payment_fields.sql`

```sql
-- Update ENUM values untuk metode_pembayaran
ALTER TABLE penjualan 
MODIFY COLUMN metode_pembayaran ENUM('Transfer', 'Cash', 'Budget Komitmen', 'Finpay', 'transfer', 'cash', 'credit_card', 'debit_card', 'e-wallet') NOT NULL;

-- Update ENUM values untuk status_pembayaran
ALTER TABLE penjualan 
MODIFY COLUMN status_pembayaran ENUM('Paid', 'Pending', 'TOP', 'Cancelled', 'paid', 'pending', 'cancelled', 'refunded') DEFAULT 'Pending';
```

**Cara Menjalankan:**
1. Buka phpMyAdmin
2. Pilih database `sinar_telkom_dashboard`
3. Klik tab "SQL"
4. Copy-paste isi file `update_payment_fields.sql`
5. Klik "Go"

### B. Form Input Penjualan

**Lokasi:** `inventory.php?page=input_penjualan`

**Perubahan:**
1. Menambahkan section "💳 Informasi Pembayaran" setelah daftar produk
2. Dropdown Metode Pembayaran (required)
3. Dropdown Status Pembayaran (required)

**Kode yang Ditambahkan:**
```html
<h3 style="color: #2c3e50; margin-bottom: 15px;">💳 Informasi Pembayaran</h3>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
    <div class="form-group">
        <label>Metode Pembayaran</label>
        <select name="metode_pembayaran" required>
            <option value="">-- Pilih Metode --</option>
            <option value="Transfer">Transfer</option>
            <option value="Cash">Cash</option>
            <option value="Budget Komitmen">Budget Komitmen</option>
            <option value="Finpay">Finpay</option>
        </select>
    </div>
    
    <div class="form-group">
        <label>Status Pembayaran</label>
        <select name="status_pembayaran" required>
            <option value="">-- Pilih Status --</option>
            <option value="Paid">Paid (Lunas)</option>
            <option value="Pending">Pending (Menunggu)</option>
            <option value="TOP">TOP (Term of Payment)</option>
            <option value="Cancelled">Cancelled (Dibatalkan)</option>
        </select>
    </div>
</div>
```

### C. Backend Processing

**Perubahan pada POST Handler:**

**Sebelum:**
```php
$stmt = $conn->prepare("INSERT INTO penjualan (...) VALUES (?, ?, ?, ?, ?, ?, ?, 'transfer', 'paid')");
$stmt->bind_param("ssiiidd", ...);
```

**Sesudah:**
```php
$metode_pembayaran = $_POST['metode_pembayaran'];
$status_pembayaran = $_POST['status_pembayaran'];

$stmt = $conn->prepare("INSERT INTO penjualan (...) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssiiiddss", ..., $metode_pembayaran, $status_pembayaran);
```

### D. Laporan Penjualan

**Perubahan pada Query SELECT:**

**Sebelum:**
```sql
SELECT 
    p.penjualan_id,
    p.no_invoice,
    ...
    p.total as total_invoice
FROM penjualan p
```

**Sesudah:**
```sql
SELECT 
    p.penjualan_id,
    p.no_invoice,
    ...
    p.total as total_invoice,
    p.metode_pembayaran,
    p.status_pembayaran
FROM penjualan p
```

**Perubahan pada Tabel:**

1. **Header Tabel:**
   - Menambahkan kolom "Metode"
   - Menambahkan kolom "Status"

2. **Body Tabel:**
   - Menampilkan metode pembayaran
   - Menampilkan status pembayaran dengan badge berwarna:
     - **Paid**: Green badge (#27ae60 / #d4edda)
     - **Pending**: Orange badge (#f39c12 / #fff3cd)
     - **TOP**: Blue badge (#3498db / #d1ecf1)
     - **Cancelled**: Red badge (#e74c3c / #f8d7da)

**Kode Badge Status:**
```php
<?php 
$status = $row['status_pembayaran'];
$badge_color = '';
$badge_bg = '';

switch($status) {
    case 'Paid':
    case 'paid':
        $badge_color = '#27ae60';
        $badge_bg = '#d4edda';
        break;
    case 'Pending':
    case 'pending':
        $badge_color = '#f39c12';
        $badge_bg = '#fff3cd';
        break;
    case 'TOP':
        $badge_color = '#3498db';
        $badge_bg = '#d1ecf1';
        break;
    case 'Cancelled':
    case 'cancelled':
        $badge_color = '#e74c3c';
        $badge_bg = '#f8d7da';
        break;
    default:
        $badge_color = '#7f8c8d';
        $badge_bg = '#e9ecef';
}
?>
<span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;">
    <?php echo htmlspecialchars($status); ?>
</span>
```

---

## 📸 Screenshot Fitur

### 1. Form Input Penjualan
```
┌─────────────────────────────────────────────────┐
│ 💳 Informasi Pembayaran                         │
├─────────────────────────────────────────────────┤
│ Metode Pembayaran    │ Status Pembayaran        │
│ [Transfer ▼]         │ [Paid (Lunas) ▼]        │
└─────────────────────────────────────────────────┘
```

### 2. Laporan Penjualan
```
┌────┬──────────┬──────────┬──────────┬────────┬────────┬──────────┬──────────┬────────┐
│ No │ Tanggal  │ Invoice  │ Reseller │ Cabang │ Produk │ Metode   │ Status   │ Total  │
├────┼──────────┼──────────┼──────────┼────────┼────────┼──────────┼──────────┼────────┤
│ 1  │ 01/01/24 │ INV-001  │ ABC      │ Jkt    │ Prod A │ Transfer │ [Paid]   │ 100K   │
│ 2  │ 02/01/24 │ INV-002  │ XYZ      │ Bdg    │ Prod B │ Cash     │ [Pending]│ 200K   │
└────┴──────────┴──────────┴──────────┴────────┴────────┴──────────┴──────────┴────────┘
```

---

## 🚀 Cara Penggunaan

### 1. Input Penjualan Baru

1. Buka halaman **Inventory** → **Input Penjualan**
2. Isi form penjualan:
   - Tanggal
   - Reseller
   - Daftar Produk
3. **Pilih Metode Pembayaran** (wajib)
4. **Pilih Status Pembayaran** (wajib)
5. Klik "💰 Proses Penjualan"

### 2. Melihat Laporan

1. Scroll ke bawah pada halaman **Input Penjualan**
2. Lihat tabel **Laporan Penjualan**
3. Kolom **Metode** menampilkan metode pembayaran
4. Kolom **Status** menampilkan status dengan badge berwarna

### 3. Filter Laporan

1. Gunakan filter tanggal untuk melihat periode tertentu
2. Klik "🔍 Filter"
3. Data akan ditampilkan sesuai periode yang dipilih

---

## 📊 Export Data

Laporan penjualan dapat di-export dengan metode pembayaran dan status pembayaran:

1. **Export Excel** - Klik tombol "📊 Export Excel"
2. **Export CSV** - Klik tombol "📄 Export CSV"

Kedua kolom (Metode dan Status) akan ikut ter-export.

---

## ⚠️ Catatan Penting

### Backward Compatibility

Script SQL sudah dirancang untuk mendukung data lama:
- Nilai lama seperti 'transfer', 'cash', 'paid', 'pending' masih didukung
- Data lama akan otomatis diupdate ke format baru (huruf kapital)

### Validasi

- Kedua field (Metode Pembayaran dan Status Pembayaran) adalah **REQUIRED**
- Form tidak dapat disubmit jika salah satu field kosong
- Browser akan menampilkan pesan error jika field tidak diisi

### Badge Colors

Status pembayaran ditampilkan dengan warna yang intuitif:
- 🟢 **Green** (Paid) - Transaksi sudah lunas
- 🟠 **Orange** (Pending) - Menunggu pembayaran
- 🔵 **Blue** (TOP) - Pembayaran dengan termin
- 🔴 **Red** (Cancelled) - Transaksi dibatalkan

---

## 🔍 Testing

### Test Case 1: Input Penjualan dengan Transfer & Paid
1. Buat penjualan baru
2. Pilih Metode: Transfer
3. Pilih Status: Paid
4. Submit form
5. ✅ Verifikasi data tersimpan dengan benar

### Test Case 2: Input Penjualan dengan Cash & Pending
1. Buat penjualan baru
2. Pilih Metode: Cash
3. Pilih Status: Pending
4. Submit form
5. ✅ Verifikasi badge berwarna orange

### Test Case 3: Input Penjualan dengan Budget Komitmen & TOP
1. Buat penjualan baru
2. Pilih Metode: Budget Komitmen
3. Pilih Status: TOP
4. Submit form
5. ✅ Verifikasi badge berwarna biru

### Test Case 4: Validasi Required Field
1. Buat penjualan baru
2. Kosongkan Metode Pembayaran
3. Coba submit form
4. ✅ Browser menampilkan error "Please fill out this field"

---

## 📝 Changelog

### Version 1.0 (Current)
- ✅ Tambah field Metode Pembayaran pada form
- ✅ Tambah field Status Pembayaran pada form
- ✅ Update database ENUM values
- ✅ Tampilkan Metode dan Status pada laporan
- ✅ Badge berwarna untuk status pembayaran
- ✅ Support backward compatibility

---

## 🛠️ Troubleshooting

### Problem: Error saat submit form
**Solution:** Pastikan database sudah diupdate dengan menjalankan `update_payment_fields.sql`

### Problem: Badge tidak berwarna
**Solution:** Clear browser cache dan refresh halaman

### Problem: Data lama tidak muncul
**Solution:** Jalankan query UPDATE pada `update_payment_fields.sql` untuk migrasi data lama

---

## 👥 Support

Jika ada pertanyaan atau masalah, silakan hubungi tim development.

---

**Last Updated:** 2024
**Version:** 1.0
**Status:** ✅ Production Ready
