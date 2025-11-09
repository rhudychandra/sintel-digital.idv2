# Panduan Implementasi Update Inventory

## Ringkasan Perubahan

### 1. Input Barang - Tambah Field Cabang
- Administrator & Staff: Dropdown pilih cabang
- Admin: Auto-fill cabang (readonly)

### 2. Input Penjualan - Multiple Products
- Bisa input multiple produk dalam 1 transaksi
- Auto-calculate subtotal per produk
- Auto-calculate grand total
- Dynamic add/remove product rows

## Langkah Implementasi

### Step 1: Update Database (Jika Belum Ada)

```sql
-- Cek apakah kolom cabang_id sudah ada di tabel inventory
DESCRIBE inventory;

-- Jika belum ada, tambahkan:
ALTER TABLE inventory ADD COLUMN cabang_id INT AFTER user_id;
ALTER TABLE inventory ADD CONSTRAINT fk_inventory_cabang 
    FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id);
```

### Step 2: Backup File Lama

```bash
copy inventory.php inventory_backup.php
```

### Step 3: Update PHP Backend (Bagian Atas File)

Tambahkan setelah line `$resellers = $conn->query(...)`:

```php
// Get cabang for dropdown (for administrator/staff)
$cabang_list = $conn->query("SELECT cabang_id, nama_cabang FROM cabang WHERE status = 'active' ORDER BY nama_cabang");
```

### Step 4: Update Handler Input Barang

Ganti handler `input_barang` dengan:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'input_barang') {
    $tanggal = $_POST['tanggal'];
    $produk_id = $_POST['produk_id'];
    $qty = $_POST['qty'];
    $keterangan = $_POST['keterangan'] ?? '';
    
    // Get cabang_id based on user role
    if ($user['role'] === 'admin') {
        $cabang_id = $user['cabang_id'];
    } else {
        $cabang_id = $_POST['cabang_id'];
    }
    
    // Get current stock
    $stmt = $conn->prepare("SELECT stok FROM produk WHERE produk_id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();
    $stok_sebelum = $produk['stok'];
    $stok_sesudah = $stok_sebelum + $qty;
    
    // Update product stock
    $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE produk_id = ?");
    $stmt->bind_param("ii", $stok_sesudah, $produk_id);
    $stmt->execute();
    
    // Insert inventory record WITH cabang_id
    $stmt = $conn->prepare("INSERT INTO inventory (produk_id, tanggal, tipe_transaksi, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id, cabang_id) VALUES (?, ?, 'masuk', ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiissii", $produk_id, $tanggal, $qty, $stok_sebelum, $stok_sesudah, $keterangan, $user['user_id'], $cabang_id);
    
    if ($stmt->execute()) {
        $message = "Stok barang berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan stok: " . $conn->error;
    }
}
```

### Step 5: Update Form Input Barang

Ganti form Input Barang dengan:

```php
<div class="form-container">
    <h2>📥 Form Input Barang</h2>
    <form method="POST">
        <input type="hidden" name="action" value="input_barang">
        
        <div class="form-group">
            <label>Tanggal</label>
            <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <?php if ($user['role'] === 'administrator' || $user['role'] === 'staff'): ?>
        <!-- Dropdown Cabang untuk Administrator & Staff -->
        <div class="form-group">
            <label>Cabang</label>
            <select name="cabang_id" required>
                <option value="">-- Pilih Cabang --</option>
                <?php 
                if ($cabang_list) {
                    while ($c = $cabang_list->fetch_assoc()): 
                ?>
                    <option value="<?php echo $c['cabang_id']; ?>">
                        <?php echo htmlspecialchars($c['nama_cabang']); ?>
                    </option>
                <?php endwhile; } ?>
            </select>
        </div>
        <?php else: ?>
        <!-- Readonly Cabang untuk Admin -->
        <div class="form-group">
            <label>Cabang</label>
            <input type="text" value="<?php 
                $stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE cabang_id = ?");
                $stmt->bind_param("i", $user['cabang_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $cabang = $result->fetch_assoc();
                echo htmlspecialchars($cabang['nama_cabang']);
            ?>" readonly style="background: #f8f9fa;">
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>Produk</label>
            <select name="produk_id" required>
                <option value="">-- Pilih Produk --</option>
                <?php 
                if ($products) {
                    $products->data_seek(0);
                    while ($p = $products->fetch_assoc()): 
                ?>
                    <option value="<?php echo $p['produk_id']; ?>">
                        <?php echo htmlspecialchars($p['nama_produk']); ?> (Stok: <?php echo $p['stok']; ?>)
                    </option>
                <?php endwhile; } ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="qty" min="1" required>
        </div>
        
        <div class="form-group">
            <label>Keterangan</label>
            <textarea name="keterangan"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">💾 Simpan</button>
            <a href="?page=dashboard" class="btn-cancel">❌ Batal</a>
        </div>
    </form>
</div>
```

### Step 6: Update Handler Input Penjualan (Multiple Products)

Ganti handler `input_penjualan` dengan kode lengkap di file terpisah: `inventory_penjualan_backend.php`

### Step 7: Update Form Input Penjualan

Karena form panjang dengan JavaScript, lihat file terpisah: `inventory_penjualan_frontend.txt`

### Step 8: Testing

1. **Test Input Barang:**
   - Login sebagai Administrator → Harus ada dropdown cabang
   - Login sebagai Staff → Harus ada dropdown cabang
   - Login sebagai Admin → Cabang readonly, auto-filled
   - Submit form → Check database inventory table, cabang_id terisi

2. **Test Input Penjualan:**
   - Klik "Tambah Produk" → Row baru muncul
   - Pilih produk & qty → Subtotal auto-calculate
   - Multiple products → Grand total auto-calculate
   - Klik "Hapus" → Row terhapus, total recalculate
   - Submit → Check database:
     - penjualan table: 1 record dengan total keseluruhan
     - detail_penjualan table: multiple records (sesuai jumlah produk)
     - produk table: stok berkurang untuk semua produk
     - inventory table: multiple records (1 per produk)

## File Pendukung

Saya sudah membuat file-file pendukung:
1. `INVENTORY_UPDATE_PLAN.md` - Rencana lengkap
2. `check_inventory_table.sql` - SQL untuk cek/update table
3. `inventory_penjualan_form.html` - Template form (perlu disesuaikan)

## Catatan Penting

1. **Backup dulu** file inventory.php yang sekarang
2. **Test di development** dulu sebelum production
3. **Cek database** pastikan kolom cabang_id ada
4. **Test semua role** (administrator, staff, admin)
5. **Validasi stok** untuk semua produk sebelum save

## Jika Ada Masalah

1. Cek error di browser console (F12)
2. Cek error di PHP (enable error_reporting)
3. Cek database query (print SQL statement)
4. Test satu fitur dulu (Input Barang), baru Input Penjualan

## Alternatif: Implementasi Bertahap

Jika terlalu kompleks, bisa implementasi bertahap:

**Phase 1:** Input Barang dengan Cabang
- Update handler input_barang
- Update form input_barang
- Test & deploy

**Phase 2:** Input Penjualan Multiple Products
- Update handler input_penjualan
- Update form input_penjualan dengan JavaScript
- Test & deploy

Dengan cara ini, lebih mudah troubleshoot jika ada masalah.
