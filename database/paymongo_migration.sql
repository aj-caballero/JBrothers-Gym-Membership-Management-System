-- ============================================================
--  PayMongo Migration
--  Run ONCE in phpMyAdmin or MySQL CLI on gym_db
--  This replaces the old mangopay_simulation_migration.sql
-- ============================================================

-- 1. Add 'PayMongo' to the payment method choices
ALTER TABLE payments
    MODIFY COLUMN payment_method ENUM('Cash', 'GCash', 'Card', 'PayMongo') NOT NULL;

-- 2. Add Pending / Cancelled statuses (safe to re-run)
ALTER TABLE payments
    MODIFY COLUMN status ENUM('Paid', 'Pending', 'Cancelled') DEFAULT 'Paid';

-- 3. Add the extra columns (skip if already present from old migration)
ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS requested_plan_id INT NULL AFTER member_id,
    ADD COLUMN IF NOT EXISTS gateway VARCHAR(50) NULL AFTER payment_method,
    ADD COLUMN IF NOT EXISTS gateway_transaction_id VARCHAR(80) NULL AFTER gateway,
    ADD COLUMN IF NOT EXISTS gateway_status VARCHAR(50) NULL AFTER gateway_transaction_id,
    ADD COLUMN IF NOT EXISTS gateway_payload TEXT NULL AFTER gateway_status;

-- 4. Foreign key on requested_plan_id (skip if already exists)
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'payments'
      AND CONSTRAINT_NAME = 'fk_payments_requested_plan'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE payments ADD CONSTRAINT fk_payments_requested_plan FOREIGN KEY (requested_plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- After running this, the PayMongo option will appear in the
-- "Record Payment" form automatically.
-- ============================================================
