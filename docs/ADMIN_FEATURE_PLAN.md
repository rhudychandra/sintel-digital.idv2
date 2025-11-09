# Administrator Feature - Development Plan

## Objective
Menambahkan role Administrator dengan kemampuan CRUD (Create, Read, Update, Delete) untuk mengelola:
- Produk
- Cabang
- Users
- Reseller
- Melihat semua penjualan, stock, dan grafik dari semua cabang

## Plan Details

### 1. Database Changes
- [x] Add 'administrator' role to users table
- [ ] Create 'cabang' table (branches)
- [ ] Create 'reseller' table
- [ ] Update existing tables if needed
- [ ] Add sample data for administrator user

### 2. Backend PHP Files to Create
- [ ] admin/index.php - Admin dashboard with menu
- [ ] admin/produk.php - Manage products (CRUD)
- [ ] admin/cabang.php - Manage branches (CRUD)
- [ ] admin/users.php - Manage users (CRUD)
- [ ] admin/reseller.php - Manage resellers (CRUD)
- [ ] admin/penjualan.php - View all sales
- [ ] admin/stock.php - View all stock/inventory
- [ ] admin/grafik.php - View charts/graphs
- [ ] admin/api/ - API endpoints for CRUD operations

### 3. UI Components
- [ ] Add "Administrator" menu button below logout (only visible for administrator role)
- [ ] Create admin panel layout with sidebar
- [ ] Create data tables with search, sort, pagination
- [ ] Create forms for add/edit operations
- [ ] Create delete confirmation modals
- [ ] Add charts/graphs using Chart.js or similar

### 4. Features per Module

#### Produk (Products)
- [ ] List all products with search & filter
- [ ] Add new product
- [ ] Edit product
- [ ] Delete product
- [ ] View product details

#### Cabang (Branches)
- [ ] List all branches
- [ ] Add new branch
- [ ] Edit branch
- [ ] Delete branch
- [ ] View branch details & statistics

#### Users
- [ ] List all users
- [ ] Add new user
- [ ] Edit user
- [ ] Delete/Deactivate user
- [ ] Change user role
- [ ] Reset password

#### Reseller
- [ ] List all resellers
- [ ] Add new reseller
- [ ] Edit reseller
- [ ] Delete reseller
- [ ] View reseller performance

#### Penjualan (Sales)
- [ ] View all sales from all branches
- [ ] Filter by date, branch, product
- [ ] Export to Excel/PDF
- [ ] Sales statistics

#### Stock/Inventory
- [ ] View all inventory from all branches
- [ ] Low stock alerts
- [ ] Stock movement history
- [ ] Export reports

#### Grafik (Charts)
- [ ] Sales trend chart
- [ ] Product performance chart
- [ ] Branch comparison chart
- [ ] Revenue chart

### 5. Security & Access Control
- [ ] Check if user has 'administrator' role
- [ ] Protect all admin pages
- [ ] Audit log for admin actions
- [ ] Input validation & sanitization

### 6. Styling
- [ ] Responsive admin panel design
- [ ] Data tables styling
- [ ] Form styling
- [ ] Chart styling
- [ ] Use existing color scheme

## Implementation Order
1. Update database schema
2. Create admin user with administrator role
3. Add Administrator menu button to dashboard
4. Create admin panel layout
5. Implement Produk module (as template)
6. Implement other modules (Cabang, Users, Reseller)
7. Implement reporting (Penjualan, Stock, Grafik)
8. Testing & refinement

## Estimated Files to Create
- Database: 1 SQL update file
- PHP Backend: ~15-20 files
- CSS: 1 admin.css file
- JavaScript: 1 admin.js file for interactivity
- Documentation: Update existing docs

## Technologies
- PHP 7.4+ for backend
- MySQL for database
- HTML5/CSS3 for frontend
- JavaScript (Vanilla) for interactivity
- Chart.js for graphs (optional)
- DataTables.js for tables (optional)
