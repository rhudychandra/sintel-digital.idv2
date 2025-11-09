# Inventory Update Plan

## Perubahan yang Diminta:

### 1. Input Barang - Tambah Field Cabang
**Requirement:**
- Administrator & Staff: Dropdown pilih cabang
- Admin: Auto-fill cabang sesuai cabang user (readonly)

**Implementation:**
```php
// Di bagian form Input Barang
<?php if ($user['role'] === 'administrator' || $user['role'] === 'staff'): ?>
    <div class="form-group">
        <label>Cabang</label>
        <select name="cabang_id" required>
            <option value="">-- Pilih Cabang --</option>
            <?php while ($c = $cabang_list->fetch_assoc()): ?>
                <option value="<?php echo $c['cabang_id']; ?>">
                    <?php echo $c['nama_cabang']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
<?php else: ?>
    <div class="form-group">
        <label>Cabang</label>
        <input type="text" value="[Nama Cabang User]" readonly>
    </div>
<?php endif; ?>
```

**Backend Processing:**
```php
if ($user['role'] === 'admin') {
    $cabang_id = $user['cabang_id'];
} else {
    $cabang_id = $_POST['cabang_id'];
}

// Insert ke inventory dengan cabang_id
INSERT INTO inventory (..., cabang_id) VALUES (..., ?)
```

### 2. Input Penjualan - Multiple Products
**Requirement:**
- Bisa input lebih dari 1 produk dalam 1 transaksi
- Setiap produk punya qty dan total masing-masing
- Total keseluruhan dihitung otomatis

**Implementation:**

**HTML Structure:**
```html
<form id="penjualanForm">
    <input type="date" name="tanggal">
    <select name="reseller_id">...</select>
    
    <div id="productContainer">
        <!-- Product Row 1 -->
        <div class="product-row">
            <select name="produk_id[]">...</select>
            <input type="number" name="qty[]">
            <input type="text" class="subtotal" readonly>
            <button type="button" onclick="removeProduct(this)">Hapus</button>
        </div>
    </div>
    
    <button type="button" onclick="addProduct()">+ Tambah Produk</button>
    
    <div class="total-display">
        <h3>Total Keseluruhan</h3>
        <div id="grandTotal">Rp 0</div>
    </div>
    
    <button type="submit">Proses Penjualan</button>
</form>
```

**JavaScript:**
```javascript
// Product data untuk harga
const productPrices = {
    <?php while ($p = $products->fetch_assoc()): ?>
        <?php echo $p['produk_id']; ?>: <?php echo $p['harga']; ?>,
    <?php endwhile; ?>
};

function addProduct() {
    // Clone product row template
    // Append to container
}

function removeProduct(btn) {
    // Remove product row
    // Recalculate total
}

function calculateSubtotal(row) {
    const produkId = row.querySelector('[name="produk_id[]"]').value;
    const qty = row.querySelector('[name="qty[]"]').value;
    const harga = productPrices[produkId];
    const subtotal = harga * qty;
    
    row.querySelector('.subtotal').value = 'Rp ' + subtotal.toLocaleString();
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.product-row').forEach(row => {
        const produkId = row.querySelector('[name="produk_id[]"]').value;
        const qty = row.querySelector('[name="qty[]"]').value;
        if (produkId && qty) {
            total += productPrices[produkId] * qty;
        }
    });
    
    document.getElementById('grandTotal').textContent = 'Rp ' + total.toLocaleString();
}
```

**Backend Processing:**
```php
$produk_ids = $_POST['produk_id']; // Array
$quantities = $_POST['qty']; // Array

$subtotal_all = 0;
$products_data = [];

// Validate & collect data
foreach ($produk_ids as $index => $produk_id) {
    $qty = $quantities[$index];
    
    // Get product info
    $stmt = $conn->prepare("SELECT nama_produk, harga, stok FROM produk WHERE produk_id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $produk = $stmt->get_result()->fetch_assoc();
    
    // Validate stock
    if ($produk['stok'] < $qty) {
        $error = "Stok tidak cukup!";
        break;
    }
    
    $subtotal = $produk['harga'] * $qty;
    $subtotal_all += $subtotal;
    
    $products_data[] = [
        'produk_id' => $produk_id,
        'nama_produk' => $produk['nama_produk'],
        'harga' => $produk['harga'],
        'qty' => $qty,
        'subtotal' => $subtotal,
        'stok_sebelum' => $produk['stok']
    ];
}

// Insert penjualan
$stmt = $conn->prepare("INSERT INTO penjualan (..., total) VALUES (..., ?)");
$stmt->bind_param("...d", ..., $subtotal_all);
$stmt->execute();
$penjualan_id = $conn->insert_id;

// Insert detail for each product
foreach ($products_data as $prod) {
    // Insert detail_penjualan
    $stmt = $conn->prepare("INSERT INTO detail_penjualan (penjualan_id, produk_id, nama_produk, harga_satuan, jumlah, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdid", $penjualan_id, $prod['produk_id'], $prod['nama_produk'], $prod['harga'], $prod['qty'], $prod['subtotal']);
    $stmt->execute();
    
    // Update stock
    $stok_sesudah = $prod['stok_sebelum'] - $prod['qty'];
    $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE produk_id = ?");
    $stmt->bind_param("ii", $stok_sesudah, $prod['produk_id']);
    $stmt->execute();
    
    // Insert inventory record
    // ...
}
```

## Files to Update:

1. **inventory.php** - Main file
   - Add cabang dropdown query
   - Update Input Barang form
   - Update Input Barang backend
   - Update Input Penjualan form (multiple products)
   - Update Input Penjualan backend
   - Add JavaScript for dynamic product rows

## Testing Checklist:

### Input Barang:
- [ ] Administrator: Can select cabang from dropdown
- [ ] Staff: Can select cabang from dropdown
- [ ] Admin: Cabang auto-filled and readonly
- [ ] Data saved with correct cabang_id

### Input Penjualan:
- [ ] Can add multiple product rows
- [ ] Can remove product rows
- [ ] Subtotal calculated correctly for each product
- [ ] Grand total calculated correctly
- [ ] Stock validation for all products
- [ ] All products saved to detail_penjualan
- [ ] Stock updated for all products
- [ ] Inventory records created for all products

## Implementation Steps:

1. Backup current inventory.php
2. Add cabang query at top
3. Update Input Barang form HTML
4. Update Input Barang backend processing
5. Update Input Penjualan form HTML with dynamic rows
6. Add JavaScript for product management
7. Update Input Penjualan backend processing
8. Test all scenarios
9. Deploy

## Database Schema Check:

```sql
-- Ensure inventory table has cabang_id
ALTER TABLE inventory ADD COLUMN cabang_id INT AFTER user_id;
ALTER TABLE inventory ADD FOREIGN KEY (cabang_id) REFERENCES cabang(cabang_id);
