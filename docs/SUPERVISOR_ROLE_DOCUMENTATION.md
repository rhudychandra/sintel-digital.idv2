# Supervisor Role Implementation Documentation

## Overview
This document describes the implementation of the **Supervisor** role in the Sinar Telkom Dashboard System. The Supervisor role has been added to provide the same access level as Staff users, allowing for better role separation and management.

## Role Hierarchy

```
Administrator (Full Access)
    ↓
Admin (Branch-specific access)
    ↓
Manager (Management level)
    ↓
Supervisor (Staff-level access) ← NEW
    ↓
Staff (Staff-level access)
    ↓
Sales (Sales operations)
```

## Access Levels

### Supervisor Role Permissions
The Supervisor role has **identical permissions** to the Staff role:

✅ **Can Access:**
- Dashboard
- Inventory Management
  - Input Barang (Stock In)
  - Stock Keluar (Stock Out)
  - Input Penjualan (Sales Input)
  - Stock Monitoring
  - Sales Reports
- Can select branch for inventory operations
- View and manage inventory transactions

❌ **Cannot Access:**
- Administrator Panel (`admin/` directory)
- User Management
- Branch Management
- Product Management
- Reseller Management
- System Configuration

## Implementation Details

### 1. Database Changes

**File:** `database_add_supervisor_role.sql`

The users table role ENUM has been updated to include 'supervisor':

```sql
ALTER TABLE users 
MODIFY COLUMN role ENUM('administrator', 'admin', 'manager', 'sales', 'staff', 'supervisor') 
DEFAULT 'staff';
```

### 2. Helper Functions

**File:** `config.php`

Three new helper functions have been added for cleaner role checking:

```php
// Check if user has staff-level access (staff or supervisor)
function hasStaffAccess($role = null)

// Check if user has admin-level access (admin or higher)
function hasAdminAccess($role = null)

// Check if user is administrator
function isAdministrator($role = null)
```

**Usage Example:**
```php
// Old way
if ($user['role'] === 'staff') {
    // Do something
}

// New way (supports both staff and supervisor)
if (hasStaffAccess($user['role'])) {
    // Do something
}
```

### 3. User Management Interface

**File:** `admin/users.php`

The role dropdown in the user add/edit form now includes Supervisor:

```html
<select id="role" name="role" required>
    <option value="administrator">Administrator</option>
    <option value="admin">Admin</option>
    <option value="manager">Manager</option>
    <option value="supervisor">Supervisor</option>  <!-- NEW -->
    <option value="staff">Staff</option>
    <option value="sales">Sales</option>
</select>
```

### 4. Inventory Access Control

**File:** `inventory.php`

Updated to use the new helper function:

```php
// Branch selection for Administrator, Staff & Supervisor
if ($user['role'] === 'administrator' || hasStaffAccess($user['role'])) {
    // Show branch dropdown
}
```

## Installation Instructions

### Step 1: Run Database Migration

1. Open phpMyAdmin or MySQL client
2. Select the `sinar_telkom_dashboard` database
3. Run the SQL file: `database_add_supervisor_role.sql`

**Via phpMyAdmin:**
- Go to SQL tab
- Copy and paste the contents of `database_add_supervisor_role.sql`
- Click "Go"

**Via Command Line:**
```bash
mysql -u root -p sinar_telkom_dashboard < database_add_supervisor_role.sql
```

### Step 2: Verify Installation

Check if the role was added successfully:

```sql
SHOW COLUMNS FROM users LIKE 'role';
```

You should see 'supervisor' in the ENUM values.

### Step 3: Create Test Supervisor User

The migration script automatically creates a test user:
- **Username:** supervisor1
- **Password:** password
- **Email:** supervisor@sinartelkom.com
- **Role:** supervisor

Or create manually via Administrator Panel:
1. Login as Administrator
2. Go to Administrator → Users
3. Click "Tambah User"
4. Select "Supervisor" from Role dropdown
5. Fill in other details and save

## Testing Guide

### Test 1: User Creation
1. Login as Administrator
2. Navigate to Users management
3. Create a new user with Supervisor role
4. Verify user is created successfully

### Test 2: Login
1. Logout from Administrator account
2. Login with Supervisor credentials
3. Verify successful login
4. Check that role is displayed correctly in header

### Test 3: Dashboard Access
1. As Supervisor, access main dashboard
2. Verify all dashboard features are accessible
3. Check that "Administrator" button is NOT visible

### Test 4: Inventory Access
1. Navigate to Inventory section
2. Test Input Barang (Stock In)
   - Verify can select branch
   - Add stock successfully
3. Test Stock Keluar (Stock Out)
   - Verify can select branch
   - Remove stock successfully
4. Test Input Penjualan (Sales)
   - Create sales transaction
   - Verify transaction is recorded

### Test 5: Admin Panel Restriction
1. Try to access `admin/index.php` directly
2. Verify redirect to dashboard
3. Confirm no access to admin features

## Code Examples

### Creating a Supervisor User Programmatically

```php
$username = 'supervisor_test';
$password = password_hash('password123', PASSWORD_DEFAULT);
$full_name = 'Test Supervisor';
$email = 'test.supervisor@example.com';
$role = 'supervisor';
$cabang_id = 1; // Branch ID
$status = 'active';

$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, cabang_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $username, $password, $full_name, $email, $role, $cabang_id, $status);
$stmt->execute();
```

### Checking Supervisor Access in Code

```php
// Check if user is supervisor
if ($user['role'] === 'supervisor') {
    // Supervisor-specific logic
}

// Check if user has staff-level access (includes supervisor)
if (hasStaffAccess()) {
    // Both staff and supervisor can access
}

// Check if user should NOT access admin panel
if (!isAdministrator()) {
    header('Location: dashboard.php');
    exit();
}
```

## Troubleshooting

### Issue: Cannot see Supervisor option in dropdown
**Solution:** Make sure you've run the database migration and refreshed the page.

### Issue: Supervisor can access admin panel
**Solution:** Check that admin panel files have proper access control:
```php
if ($user['role'] !== 'administrator') {
    header('Location: ../dashboard.php');
    exit();
}
```

### Issue: Supervisor cannot access inventory features
**Solution:** Verify that inventory files use `hasStaffAccess()` function instead of hardcoded role checks.

### Issue: Database error when creating supervisor user
**Solution:** Ensure the ENUM has been updated in the database:
```sql
ALTER TABLE users 
MODIFY COLUMN role ENUM('administrator', 'admin', 'manager', 'sales', 'staff', 'supervisor') 
DEFAULT 'staff';
```

## Future Enhancements

Potential improvements for the Supervisor role:

1. **Custom Permissions:** Add granular permissions system
2. **Supervisor Dashboard:** Create dedicated dashboard for supervisors
3. **Team Management:** Allow supervisors to manage their team members
4. **Reporting:** Add supervisor-specific reports
5. **Approval Workflow:** Implement approval system for certain operations

## Rollback Instructions

If you need to remove the Supervisor role:

```sql
-- 1. Update existing supervisor users to staff
UPDATE users SET role = 'staff' WHERE role = 'supervisor';

-- 2. Remove supervisor from ENUM
ALTER TABLE users 
MODIFY COLUMN role ENUM('administrator', 'admin', 'manager', 'sales', 'staff') 
DEFAULT 'staff';
```

## Support

For issues or questions regarding the Supervisor role implementation:
1. Check this documentation
2. Review the TODO_SUPERVISOR_ROLE.md file
3. Check the database migration file for SQL details
4. Review config.php for helper function implementations

## Changelog

### Version 1.0 (Current)
- ✅ Added Supervisor role to database ENUM
- ✅ Created helper functions for role checking
- ✅ Updated user management interface
- ✅ Updated inventory access control
- ✅ Created comprehensive documentation
- ✅ Added test user in migration script

---

**Last Updated:** 2024
**Status:** ✅ Implementation Complete - Ready for Testing
