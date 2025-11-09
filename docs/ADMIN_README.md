# Administrator Panel - Sinar Telkom Dashboard System

## 📋 Overview

Panel Administrator adalah fitur khusus untuk user dengan role **administrator** yang memiliki akses penuh untuk mengelola semua data sistem dari semua cabang.

## 🔐 Akses Administrator

### Login Credentials
- **Username:** administrator
- **Password:** password (atau admin)

### Cara Akses
1. Login dengan credentials administrator
2. Klik tombol **"Administrator"** (warna ungu) di dashboard
3. Anda akan masuk ke Admin Panel

## 🎯 Fitur Administrator

### 1. Dashboard Administrator
- **Statistik Real-time:**
  - Total Cabang
  - Total Reseller
  - Total Users
  - Total Produk
  - Total Penjualan
  - Total Stock
- **Quick Actions:** Tambah data dengan cepat
- **Menu Grid:** Akses cepat ke semua modul

### 2. Kelola Produk (CRUD)
**Fitur:**
- ✅ Lihat semua produk dari semua cabang
- ✅ Tambah produk baru
- ✅ Edit produk existing
- ✅ Hapus produk
- ✅ Assign produk ke cabang tertentu

**Data yang Dikelola:**
- Nama Produk
- Kategori
- Harga
- Deskripsi
- Cabang

### 3. Kelola Cabang (CRUD)
**Fitur:**
- ✅ Lihat semua cabang
- ✅ Tambah cabang baru
- ✅ Edit data cabang
- ✅ Hapus cabang
- ✅ Aktifkan/Non-aktifkan cabang

**Data yang Dikelola:**
- Kode Cabang
- Nama Cabang
- Alamat Lengkap
- Kota & Provinsi
- Telepon & Email
- Nama Manager
- Status (Active/Inactive)

### 4. Kelola Users (CRUD)
**Fitur:**
- ✅ Lihat semua users
- ✅ Tambah user baru
- ✅ Edit user existing
- ✅ Hapus user
- ✅ Ubah role user
- ✅ Reset password
- ✅ Assign user ke cabang

**Data yang Dikelola:**
- Username
- Password (hashed)
- Nama Lengkap
- Email & Telepon
- Role (administrator, admin, manager, sales, staff)
- Cabang
- Status (Active/Inactive)

### 5. Kelola Reseller (CRUD)
**Fitur:**
- ✅ Lihat semua reseller
- ✅ Tambah reseller baru
- ✅ Edit data reseller
- ✅ Hapus reseller
- ✅ Assign reseller ke cabang

**Data yang Dikelola:**
- Kode Reseller
- Nama Reseller
- Nama Perusahaan
- Alamat Lengkap
- Kota & Provinsi
- Telepon & Email
- Contact Person
- Cabang
- Status (Active/Inactive)

### 6. Data Penjualan (View Only)
**Fitur:**
- ✅ Lihat semua penjualan dari semua cabang
- ✅ Filter by date, cabang, status
- ✅ Export data (future feature)

**Informasi yang Ditampilkan:**
- ID Penjualan
- Tanggal
- Cabang
- Pelanggan
- Sales Person
- Reseller
- Total Harga
- Status Pembayaran

### 7. Data Stock (View Only)
**Fitur:**
- ✅ Lihat semua stock dari semua cabang
- ✅ Low stock alerts
- ✅ Nilai stock per produk
- ✅ Status stock (Low/Medium/Good)

**Informasi yang Ditampilkan:**
- Produk & Kategori
- Cabang
- Jumlah Stock
- Harga Satuan
- Nilai Total Stock
- Status Stock

### 8. Grafik & Laporan
**Fitur:**
- ✅ Performance penjualan per cabang
- ✅ Total transaksi per cabang
- ✅ Jumlah reseller per cabang
- 📊 Grafik visualisasi (dapat dikembangkan)

## 🗂️ Struktur File

```
admin/
├── index.php              # Dashboard administrator
├── produk.php            # CRUD Produk
├── cabang.php            # CRUD Cabang
├── users.php             # CRUD Users
├── reseller.php          # CRUD Reseller
├── penjualan.php         # View Penjualan
├── stock.php             # View Stock
├── grafik.php            # Grafik & Laporan
└── admin-styles.css      # Styling khusus admin panel
```

## 🎨 Design Features

### Layout
- **Sidebar Navigation:** Menu tetap di kiri
- **Main Content Area:** Konten dinamis di kanan
- **Responsive Design:** Mobile-friendly

### Color Scheme
- **Primary:** Purple gradient (#667eea - #764ba2)
- **Success:** Green (#27ae60)
- **Warning:** Orange (#f39c12)
- **Danger:** Red (#e74c3c)
- **Info:** Blue (#3498db)

### Components
- **Statistics Cards:** Kartu statistik dengan icon
- **Data Tables:** Tabel dengan search & sort
- **Forms:** Form input yang user-friendly
- **Action Buttons:** Edit, Delete, View
- **Status Badges:** Active/Inactive indicators

## 🔒 Security Features

### Access Control
- ✅ Role-based access (hanya administrator)
- ✅ Session validation
- ✅ Redirect non-administrator users

### Data Protection
- ✅ SQL Injection prevention (prepared statements)
- ✅ XSS protection (htmlspecialchars)
- ✅ Password hashing (bcrypt)
- ✅ Input validation

### Audit Trail
- 📝 Audit log table tersedia (future implementation)
- 📝 Track user actions
- 📝 Record changes

## 📊 Database Views

### view_admin_dashboard
Statistik untuk dashboard administrator:
- total_cabang
- total_reseller
- total_users
- total_produk
- total_penjualan
- total_stok

### view_sales_per_cabang
Performance penjualan per cabang:
- kode_cabang, nama_cabang
- total_transaksi
- total_penjualan
- jumlah_reseller

### view_stock_per_cabang
Stock inventory per cabang:
- kode_cabang, nama_cabang
- jumlah_produk
- total_stok
- nilai_stok

### view_reseller_performance
Performance reseller:
- kode_reseller, nama_reseller
- nama_cabang
- total_transaksi
- total_pembelian

## 🚀 Setup & Installation

### 1. Import Database Update
```bash
# Via phpMyAdmin atau command line
mysql -u root -p < database_update_admin.sql
```

### 2. Verify Administrator User
```sql
SELECT * FROM users WHERE role = 'administrator';
```

### 3. Login & Test
1. Login dengan username: `administrator`
2. Password: `password`
3. Klik tombol "Administrator" di dashboard
4. Test semua fitur CRUD

## 📝 Usage Examples

### Menambah Produk Baru
1. Klik menu **Produk** di sidebar
2. Klik tombol **"Tambah Produk"**
3. Isi form:
   - Nama Produk: "Router WiFi 6"
   - Kategori: "Networking"
   - Harga: 1500000
   - Cabang: Pilih cabang
   - Deskripsi: Detail produk
4. Klik **"Tambah Produk"**

### Menambah Cabang Baru
1. Klik menu **Cabang** di sidebar
2. Klik tombol **"Tambah Cabang"**
3. Isi form dengan data lengkap
4. Klik **"Tambah Cabang"**

### Menambah User Baru
1. Klik menu **Users** di sidebar
2. Klik tombol **"Tambah User"**
3. Isi form:
   - Username: unique username
   - Password: strong password
   - Role: pilih role yang sesuai
   - Cabang: assign ke cabang
4. Klik **"Tambah User"**

### Melihat Laporan Penjualan
1. Klik menu **Penjualan** di sidebar
2. Lihat semua transaksi dari semua cabang
3. Filter by date atau cabang (future feature)

### Monitoring Stock
1. Klik menu **Stock** di sidebar
2. Lihat status stock semua produk
3. Perhatikan produk dengan status "Low Stock"

## 🔧 Customization

### Menambah Field Baru
1. Update database table
2. Update form di file PHP
3. Update query INSERT/UPDATE
4. Update tampilan tabel

### Menambah Menu Baru
1. Buat file PHP baru di folder `admin/`
2. Copy struktur dari file existing
3. Update sidebar navigation
4. Implement fitur yang diinginkan

### Styling
Edit file `admin/admin-styles.css` untuk:
- Ubah warna theme
- Adjust layout
- Customize components

## 🐛 Troubleshooting

### Error: "Access Denied"
**Solution:** Pastikan login dengan user role 'administrator'

### Error: "Database Connection Failed"
**Solution:** 
- Check config.php
- Pastikan MySQL running
- Verify database credentials

### Error: "Table doesn't exist"
**Solution:** Import database_update_admin.sql

### Tombol Administrator tidak muncul
**Solution:** Pastikan user memiliki role 'administrator' di database

## 📈 Future Enhancements

### Planned Features
- [ ] Advanced search & filter
- [ ] Export to Excel/PDF
- [ ] Import data from CSV
- [ ] Interactive charts (Chart.js)
- [ ] Real-time notifications
- [ ] Audit log viewer
- [ ] Bulk operations
- [ ] Advanced reporting
- [ ] Email notifications
- [ ] API endpoints

### Performance Optimization
- [ ] Pagination for large datasets
- [ ] Caching mechanism
- [ ] Database indexing
- [ ] Query optimization
- [ ] Lazy loading

## 📞 Support

Untuk bantuan atau pertanyaan:
- Review dokumentasi lengkap
- Check source code
- Hubungi tim development

---

**Sinar Telkom Dashboard System - Administrator Panel**  
Version: 1.0  
Last Updated: 2024  
Status: ✅ Ready for Use
