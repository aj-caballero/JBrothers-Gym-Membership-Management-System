-- ============================================================
--  Soft Delete Migration
--  Run this once in phpMyAdmin or MySQL CLI
-- ============================================================

ALTER TABLE members
    ADD COLUMN deleted_at DATETIME DEFAULT NULL;

ALTER TABLE membership_plans
    ADD COLUMN deleted_at DATETIME DEFAULT NULL;
