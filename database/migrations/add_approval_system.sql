-- =====================================================
-- ADD APPROVAL SYSTEM TO INVENTORY
-- =====================================================
-- This migration adds approval system for stock transactions
-- Status: pending, approved, rejected
-- =====================================================

-- Add status_approval column to inventory table
ALTER TABLE inventory 
ADD COLUMN status_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER keterangan;

-- Update existing records to 'approved' (since they were already processed)
UPDATE inventory SET status_approval = 'approved' WHERE status_approval IS NULL OR status_approval = 'pending';

-- Add index for better performance
ALTER TABLE inventory ADD INDEX idx_status_approval (status_approval);

-- =====================================================
-- VERIFICATION
-- =====================================================
SELECT 'Approval system column added successfully!' as status;
SELECT COUNT(*) as total_pending FROM inventory WHERE status_approval = 'pending';
SELECT COUNT(*) as total_approved FROM inventory WHERE status_approval = 'approved';
SELECT COUNT(*) as total_rejected FROM inventory WHERE status_approval = 'rejected';
