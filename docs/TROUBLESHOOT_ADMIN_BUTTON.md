# Troubleshooting: Tombol Administrator Tidak Muncul

## 🔍 Diagnosis Masalah

Jika tombol "Administrator" tidak muncul di dashboard untuk user **rhudychandra**, ikuti langkah-langkah berikut:

## ✅ Checklist Verifikasi

### 1. Verify User Role di Database
```sql
USE sinar_telkom_dashboard;

-- Check role user rhudychandra
SELECT user_id, username, full_name, role, status 
FROM users 
WHERE username = 'rhudychandra';

-- Expected result:
-- role: administrator (bukan admin, manager, atau lainnya)
-- status: active
```

**Jika role BUKAN 'administrator':**
```sql
-- Fix: Update role ke administrator
UPDATE users 
SET role = 'administrator' 
WHERE username = 'rhudychandra';
```

### 2. Verify Session
Setelah login, session harus menyimpan role yang benar.

**Cara Check:**
1. Logout dari sistem
2. Login kembali dengan rhudychandra / Tsel2025
3. Check di dashboard, seharusnya muncul: "Rhudy Chandra (administrator)"

**Jika masih menunjukkan role lain:**
- Clear browser cache
- Clear cookies
- Logout dan login kembali

### 3. Verify Dashboard.php Code
File dashboard.php harus memiliki kode ini:

```php
<?php if ($user['role'] === 'administrator'): ?>
<a href="admin/" class="admin-button">Administrator</a>
<?php endif; ?>
```

**Check:** Pastikan kondisi menggunakan `===` (strict comparison) dan string 'administrator' (lowercase)

### 4. Verify Folder admin/ Exists
```bash
# Check apakah folder admin/ ada
ls -la admin/

# Harus ada file:
# - index.php
# - produk.php
# - cabang.php
# - users.php
# - reseller.php
# - penjualan.php
# - stock.php
# - grafik.php
# - admin-styles.css
```

## 🔧 Quick Fix Script

Jalankan file SQL ini untuk fix semua masalah:

```bash
mysql -u root -p sinar_telkom_dashboard < fix_admin_access.sql
```

File `fix_admin_access.sql` akan:
1. Check status user saat ini
2. Update role ke 'administrator'
3. Update status ke 'active'
4. Verify hasil update

## 📋 Step-by-Step Manual Fix

### Step 1: Check Database
```sql
USE sinar_telkom_dashboard;

SELECT * FROM users WHERE username = 'rhudychandra';
```

**Expected Output:**
```
user_id: (any number)
username: rhudychandra
password: (hash)
full_name: Rhudy Chandra
email: rhudychandra@sinartelkom.com
phone: 081234567890
role: administrator  ← MUST BE 'administrator'
status: active       ← MUST BE 'active'
```

### Step 2: Fix Role if Wrong
```sql
UPDATE users 
SET role = 'administrator', 
    status = 'active'
WHERE username = 'rhudychandra';

-- Verify
SELECT username, role, status FROM users WHERE username = 'rhudychandra';
```

### Step 3: Clear Session & Re-login
1. Buka browser
2. Klik Logout
3. Clear browser cache (Ctrl+Shift+Delete)
4. Clear cookies untuk localhost
5. Close browser
6. Open browser baru
7. Login dengan rhudychandra / Tsel2025

### Step 4: Verify Button Appears
Setelah login, di header dashboard harus ada:
```
Rhudy Chandra (administrator) [Administrator] [Logout]
                                    ↑
                            Tombol ini harus muncul
```

## 🐛 Common Issues

### Issue 1: Role adalah 'admin' bukan 'administrator'
**Problem:** Database memiliki role 'admin' (tanpa 'istrator')

**Solution:**
```sql
UPDATE users SET role = 'administrator' WHERE username = 'rhudychandra';
```

### Issue 2: Status adalah 'inactive'
**Problem:** User status tidak active

**Solution:**
```sql
UPDATE users SET status = 'active' WHERE username = 'rhudychandra';
```

### Issue 3: Session Lama Masih Tersimpan
**Problem:** Browser masih menyimpan session lama dengan role berbeda

**Solution:**
1. Logout
2. Clear browser cache & cookies
3. Close browser
4. Login kembali

### Issue 4: Case Sensitivity
**Problem:** Role ditulis 'Administrator' (capital A)

**Solution:**
```sql
-- Role harus lowercase
UPDATE users SET role = 'administrator' WHERE username = 'rhudychandra';
```

### Issue 5: Folder admin/ Tidak Ada
**Problem:** Folder admin/ belum dibuat atau terhapus

**Solution:**
- Pastikan folder admin/ ada di root project
- Pastikan ada file index.php di dalamnya
- Check file permissions

## 🧪 Test Script

Buat file PHP untuk test session:

**test_session.php:**
```php
<?php
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Expected output:
// Array (
//     [user_id] => (number)
//     [username] => rhudychandra
//     [full_name] => Rhudy Chandra
//     [email] => rhudychandra@sinartelkom.com
//     [role] => administrator  ← Check this!
// )
?>
```

## ✅ Final Verification

Setelah semua fix, verify dengan checklist ini:

- [ ] Database: role = 'administrator' (lowercase)
- [ ] Database: status = 'active'
- [ ] Logout dari sistem
- [ ] Clear browser cache & cookies
- [ ] Login dengan rhudychandra / Tsel2025
- [ ] Header menunjukkan: "Rhudy Chandra (administrator)"
- [ ] Tombol "Administrator" (warna ungu) muncul
- [ ] Klik tombol Administrator
- [ ] Redirect ke admin/index.php
- [ ] Admin Panel terbuka dengan sidebar

## 📞 Still Not Working?

Jika setelah semua langkah di atas tombol masih tidak muncul:

### Debug Mode:
Edit dashboard.php, tambahkan di atas `<?php if ($user['role'] === 'administrator'): ?>`:

```php
<!-- DEBUG INFO -->
<div style="background: yellow; padding: 10px; margin: 10px;">
    <strong>DEBUG:</strong><br>
    Username: <?php echo $user['username']; ?><br>
    Role: <?php echo $user['role']; ?><br>
    Role Type: <?php echo gettype($user['role']); ?><br>
    Is Administrator: <?php echo ($user['role'] === 'administrator') ? 'YES' : 'NO'; ?>
</div>
```

Ini akan menampilkan informasi debug. Check:
- Apakah role benar-benar 'administrator'?
- Apakah ada spasi atau karakter tersembunyi?
- Apakah comparison menghasilkan YES?

## 🎯 Quick Command Summary

```bash
# 1. Fix database
mysql -u root -p sinar_telkom_dashboard < fix_admin_access.sql

# 2. Verify
mysql -u root -p sinar_telkom_dashboard -e "SELECT username, role, status FROM users WHERE username='rhudychandra';"

# 3. Expected output:
# rhudychandra | administrator | active
```

---

**Last Updated:** 2024  
**Status:** Ready to Use  
**Support:** Check QUICK_START.md for more info
