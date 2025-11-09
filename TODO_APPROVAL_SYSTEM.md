# TODO: Sistem Approval Stock Masuk/Keluar

## Status: ✅ COMPLETED - READY FOR TESTING

### ✅ Completed:
1. ✅ Database migration - kolom `status_approval` ditambahkan & executed
2. ✅ inventory.php - Input Barang & Stock Keluar set status='pending', tidak update stock langsung
3. ✅ inventory_stock_masuk.php - Sistem approval lengkap:
   - Button Approve/Reject per row
   - Role-based permission (cabang tujuan, admin, manager)
   - Filter by status (pending/approved/rejected)
   - Summary box dengan count per status
   - Auto update stock saat approved (stock bertambah)
   - Cabang asal detection
4. ✅ inventory_stock_keluar.php - Sistem approval lengkap:
   - Button Approve/Reject per row
   - Role-based permission (cabang asal, admin, manager)
   - Filter by status
   - Summary box dengan count per status
   - Auto update stock saat approved (stock berkurang)

### ⏳ Pending:
5. Testing approval flow - PERLU TESTING USER

---

## Approval Rules:

### Who Can Approve:
- User cabang tujuan (staff, admin, supervisor, finance)
- Administrator (all branches)
- Manager (all branches)

### Approval Flow:

**Stock Masuk (Input Barang):**
```
1. Input → status='pending', stock TIDAK bertambah
2. Approve → status='approved', stock bertambah di cabang tujuan
3. Reject → status='rejected', stock tidak berubah
```

**Stock Keluar (Pindah Gudang/Pengeluaran):**
```
1. Input → status='pending', stock TIDAK berkurang di cabang asal
2. Approve → status='approved', stock berkurang di cabang asal
3. Reject → status='rejected', stock tidak berubah
```

---

## Files to Update:

1. ✅ database/migrations/add_approval_system.sql
2. ✅ database/migrations/run_add_approval_system.php
3. ✅ modules/inventory/inventory.php
4. 🔄 modules/inventory/inventory_stock_masuk.php
5. ⏳ modules/inventory/inventory_stock_keluar.php

---

## Next Steps:
1. Implement approval buttons & logic in inventory_stock_masuk.php
2. Update inventory_stock_keluar.php to show status
3. Test complete flow
