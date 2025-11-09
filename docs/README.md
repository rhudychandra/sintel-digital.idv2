# Sinar Telkom Dashboard System

Website perusahaan dengan sistem dashboard yang modern dan responsif, terintegrasi dengan database MySQL.

## 🚀 Fitur Utama

### 1. Sistem Autentikasi Database
- ✅ Login menggunakan database MySQL
- ✅ Multiple user accounts dengan role berbeda
- ✅ Session management yang aman
- ✅ Password verification
- ✅ Last login tracking

### 2. Dashboard Utama
- ✅ 3 menu berbentuk bulat (circle) tersusun horizontal di tengah
- ✅ Font Lexend yang modern
- ✅ Menu items:
  - Performance Cluster
  - Inventory
  - Sinar Telekom Info

### 3. Performance Cluster Submenu
- ✅ 3 menu dengan kotak rounded yang tersusun vertikal di tengah
- ✅ Menu items:
  - Fundamental Cluster
  - KPI Sales Force
  - KPI Direct Sales

### 4. Database Management
- ✅ 6 tabel utama (users, produk, pelanggan, penjualan, detail_penjualan, inventory)
- ✅ 4 views untuk reporting
- ✅ 2 stored procedures
- ✅ Sample data untuk testing

## 📋 Cara Menggunakan

### Quick Start

1. **Import Database**
   ```bash
   # Via phpMyAdmin atau command line
   mysql -u root -p < database.sql
   ```

2. **Akses Website**
   ```
   http://localhost/sinartelekomdashboardsystem/
   ```

3. **Login**
   - Username: `admin`
   - Password: `password`

### Login Credentials (Demo Accounts)

| Username | Password | Role | Access Level |
|----------|----------|------|--------------|
| admin | password | Admin | Full access |
| manager1 | password | Manager | Management |
| sales1 | password | Sales | Sales operations |
| sales2 | password | Sales | Sales operations |
| staff1 | password | Staff | Limited access |

### Setup Lengkap

Lihat **SETUP_GUIDE.md** untuk panduan instalasi lengkap step-by-step.

### Navigasi
1. Login menggunakan credentials di atas
2. Setelah login, Anda akan melihat 3 menu hexagon
3. Klik "Performance Cluster" untuk melihat submenu
4. Gunakan tombol "Kembali" untuk kembali ke dashboard
5. Gunakan tombol "Logout" untuk keluar

## 📁 Struktur File

```
sinartelekomdashboardsystem/
├── config.php                    # Konfigurasi database & session
├── login.php                     # Halaman login (entry point)
├── dashboard.php                 # Dashboard utama
├── performance-cluster.php       # Submenu Performance Cluster
├── logout.php                    # Logout handler
├── styles.css                    # Styling untuk semua halaman
├── database.sql                  # Database SQL lengkap
├── .htaccess                     # Apache configuration
├── README.md                     # Dokumentasi utama
├── SETUP_GUIDE.md               # Panduan setup lengkap
├── DATABASE_README.md           # Dokumentasi database
└── TODO.md                       # Development checklist

# Legacy HTML files (untuk referensi)
├── index.html
├── dashboard.html
├── performance-cluster.html
└── script.js
```

## 🛠️ Teknologi yang Digunakan

### Frontend
- **HTML5** - Struktur halaman
- **CSS3** - Styling dan animasi
  - Flexbox untuk layout
  - CSS animations
  - Gradient backgrounds
  - Circle shapes dengan border-radius
  - Responsive design
  - Font Lexend dari Google Fonts

### Backend
- **PHP 7.4+** - Server-side logic
  - Session management
  - Database operations
  - Authentication & authorization
  - Prepared statements (SQL injection prevention)

### Database
- **MySQL 5.7+** - Data storage
  - Relational database design
  - Foreign keys & constraints
  - Views untuk reporting
  - Stored procedures
  - Indexes untuk performance

## Fitur Teknis

- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Modern UI dengan gradient dan animasi
- ✅ Session management
- ✅ Form validation
- ✅ Smooth transitions dan hover effects
- ✅ Clean dan professional design
- ✅ Sans-serif typography

## Browser Support

Website ini kompatibel dengan browser modern:
- Chrome (recommended)
- Firefox
- Edge
- Safari

## 🔒 Keamanan

### Implemented:
- ✅ Session-based authentication
- ✅ Prepared statements (SQL injection prevention)
- ✅ Password hashing support
- ✅ XSS protection
- ✅ Protected config files
- ✅ Role-based access control

### Untuk Production:
- ⚠️ Ganti semua password default
- ⚠️ Gunakan HTTPS/SSL
- ⚠️ Implement rate limiting
- ⚠️ Add CSRF protection
- ⚠️ Enable logging system
- ⚠️ Regular security audits
- ⚠️ Database backup strategy

## Customization

Untuk mengubah warna atau styling:
1. Buka file `styles.css`
2. Ubah variabel warna di bagian gradient:
   - `#667eea` - Warna primary (biru)
   - `#764ba2` - Warna secondary (ungu)
3. Sesuaikan dengan brand colors perusahaan

## 📚 Dokumentasi

- **README.md** (file ini) - Overview dan quick start
- **SETUP_GUIDE.md** - Panduan instalasi lengkap step-by-step
- **DATABASE_README.md** - Dokumentasi database lengkap
- **TODO.md** - Development progress tracker

## 🐛 Troubleshooting

### Database Connection Error
```
Solution: Check config.php, pastikan MySQL running
```

### Login Failed
```
Solution: Pastikan database sudah diimport, gunakan credentials yang benar
```

### Page Not Found
```
Solution: Akses via http://localhost/sinartelekomdashboardsystem/
```

Lihat **SETUP_GUIDE.md** untuk troubleshooting lengkap.

## 🚀 Next Steps

1. ✅ Import database
2. ✅ Test login dengan berbagai user
3. 📝 Customize dashboard sesuai kebutuhan
4. 📝 Tambah fitur baru (CRUD, reporting, dll)
5. 📝 Deploy ke production server

## 📞 Support

Untuk pertanyaan atau bantuan:
- Review dokumentasi di folder project
- Check kode sumber untuk understanding
- Hubungi tim development

---

**Sinar Telkom Dashboard System** v2.0  
Professional Dashboard Solution with Database Integration  
**Status:** ✅ Ready for Development
