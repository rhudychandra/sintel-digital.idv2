# Setup Guide - Sinar Telkom Dashboard System

## Panduan Instalasi dan Konfigurasi

### Prasyarat
- XAMPP (Apache + MySQL + PHP)
- Browser modern (Chrome, Firefox, Edge)
- Text editor (VS Code, Notepad++, dll)

---

## Langkah 1: Setup Database

### A. Import Database via phpMyAdmin

1. **Start XAMPP Services**
   - Buka XAMPP Control Panel
   - Start **Apache** dan **MySQL**

2. **Buka phpMyAdmin**
   - Buka browser
   - Akses: `http://localhost/phpmyadmin`

3. **Import Database**
   - Klik tab **"Import"**
   - Klik **"Choose File"**
   - Pilih file `database.sql` dari folder project
   - Klik **"Go"**
   - Database `sinar_telkom_dashboard` akan otomatis dibuat

### B. Import Database via Command Line (Alternatif)

```bash
# Buka Command Prompt/Terminal
cd c:\xampp\htdocs\sinartelekomdashboardsystem

# Import database
c:\xampp\mysql\bin\mysql -u root -p < database.sql

# Atau jika tidak ada password
c:\xampp\mysql\bin\mysql -u root < database.sql
```

### C. Verifikasi Database

1. Buka phpMyAdmin
2. Pilih database `sinar_telkom_dashboard`
3. Pastikan tabel-tabel berikut ada:
   - users
   - produk
   - pelanggan
   - penjualan
   - detail_penjualan
   - inventory

---

## Langkah 2: Konfigurasi Database Connection

File `config.php` sudah dikonfigurasi dengan setting default XAMPP:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sinar_telkom_dashboard');
```

**Jika MySQL Anda menggunakan password:**
1. Buka file `config.php`
2. Ubah `DB_PASS` sesuai password MySQL Anda:
   ```php
   define('DB_PASS', 'password_anda');
   ```

---

## Langkah 3: Akses Website

### A. Via XAMPP (Recommended)

1. Pastikan Apache dan MySQL sudah running di XAMPP
2. Buka browser
3. Akses: `http://localhost/sinartelekomdashboardsystem/`
4. Anda akan otomatis diarahkan ke halaman login

### B. File Structure

```
sinartelekomdashboardsystem/
├── config.php              # Konfigurasi database
├── login.php               # Halaman login (entry point)
├── dashboard.php           # Dashboard utama
├── performance-cluster.php # Submenu Performance Cluster
├── logout.php              # Logout handler
├── styles.css              # Styling
├── database.sql            # Database SQL
├── .htaccess              # Apache configuration
└── README files...
```

---

## Langkah 4: Login ke Sistem

### Akun Demo yang Tersedia:

| Username | Password | Role | Deskripsi |
|----------|----------|------|-----------|
| admin | password | Admin | Full access |
| manager1 | password | Manager | Manager access |
| sales1 | password | Sales | Sales access |
| sales2 | password | Sales | Sales access |
| staff1 | password | Staff | Staff access |

### Cara Login:

1. Buka `http://localhost/sinartelekomdashboardsystem/`
2. Masukkan username: `admin`
3. Masukkan password: `password`
4. Klik **Login**
5. Anda akan masuk ke dashboard

---

## Fitur Sistem

### ✅ Autentikasi Database
- Login menggunakan data dari database MySQL
- Session management untuk keamanan
- Password verification
- Update last login timestamp

### ✅ Role-Based Access
- Admin: Full access
- Manager: Management access
- Sales: Sales operations
- Staff: Limited access

### ✅ Dashboard
- 3 Menu utama berbentuk bulat (circle)
- Performance Cluster
- Inventory
- Sinar Telekom Info

### ✅ Performance Cluster Submenu
- Fundamental Cluster
- KPI Sales Force
- KPI Direct Sales

### ✅ Security Features
- Session-based authentication
- SQL injection prevention (prepared statements)
- XSS protection
- Protected config files via .htaccess

---

## Troubleshooting

### Problem 1: "Connection failed"
**Solusi:**
- Pastikan MySQL service running di XAMPP
- Check username/password di `config.php`
- Pastikan database `sinar_telkom_dashboard` sudah diimport

### Problem 2: "Username atau password salah"
**Solusi:**
- Gunakan username dan password yang benar (lihat tabel akun demo)
- Pastikan database sudah diimport dengan benar
- Check tabel `users` di phpMyAdmin

### Problem 3: "Page not found" atau 404
**Solusi:**
- Pastikan Apache service running
- Check URL: `http://localhost/sinartelekomdashboardsystem/`
- Pastikan semua file PHP ada di folder yang benar

### Problem 4: Blank page atau error
**Solusi:**
- Check PHP error log di XAMPP
- Pastikan PHP version minimal 7.0
- Enable error reporting di `php.ini`

### Problem 5: Session tidak tersimpan
**Solusi:**
- Check folder session PHP writable
- Restart Apache service
- Clear browser cookies

---

## File Penting

### 1. config.php
Konfigurasi database dan fungsi helper:
- Database connection
- Session management
- Authentication functions

### 2. login.php
Halaman login dengan fitur:
- Form login
- Validasi input
- Database authentication
- Session creation
- Error handling

### 3. dashboard.php
Dashboard utama dengan:
- Session check
- User info display
- 3 menu circle
- Logout button

### 4. performance-cluster.php
Submenu dengan:
- Protected access
- 3 rounded menu items
- Back button
- User info

### 5. logout.php
Logout handler:
- Session destroy
- Redirect to login

---

## Menambah User Baru

### Via phpMyAdmin:

1. Buka phpMyAdmin
2. Pilih database `sinar_telkom_dashboard`
3. Klik tabel `users`
4. Klik **"Insert"**
5. Isi data:
   - username: (username baru)
   - password: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi` (hash untuk "password")
   - full_name: (nama lengkap)
   - email: (email)
   - role: (pilih: admin/manager/sales/staff)
   - status: active
6. Klik **"Go"**

### Via SQL Query:

```sql
INSERT INTO users (username, password, full_name, email, phone, role, status) 
VALUES (
    'newuser',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'New User Name',
    'newuser@email.com',
    '081234567890',
    'staff',
    'active'
);
```

---

## Security Best Practices

### Untuk Production:

1. **Ganti Password Default**
   - Jangan gunakan password "password"
   - Gunakan password yang kuat
   - Hash password dengan `password_hash()`

2. **Database Security**
   - Buat user database khusus (jangan gunakan root)
   - Set privilege minimal yang diperlukan
   - Gunakan password yang kuat

3. **File Security**
   - Protect config.php dari direct access
   - Set proper file permissions
   - Jangan commit config.php ke git

4. **HTTPS**
   - Gunakan SSL certificate
   - Force HTTPS untuk semua pages
   - Secure cookies

5. **Input Validation**
   - Validasi semua input dari user
   - Gunakan prepared statements
   - Sanitize output

---

## Development vs Production

### Development (Current Setup):
- ✅ Error reporting enabled
- ✅ Simple password check
- ✅ Default credentials
- ✅ No SSL required

### Production (Recommended):
- ❌ Disable error reporting
- ✅ Strong password hashing
- ✅ Unique credentials
- ✅ SSL/HTTPS required
- ✅ Rate limiting
- ✅ Logging system
- ✅ Backup strategy

---

## Next Steps

1. ✅ Import database
2. ✅ Configure config.php
3. ✅ Test login
4. ✅ Explore dashboard
5. 📝 Customize as needed
6. 📝 Add more features
7. 📝 Deploy to production

---

## Support

Untuk bantuan lebih lanjut:
- Check DATABASE_README.md untuk dokumentasi database
- Check README.md untuk dokumentasi website
- Review kode di file PHP untuk understanding

---

**Version:** 1.0  
**Last Updated:** 2024-01-20  
**Status:** Ready for Development
