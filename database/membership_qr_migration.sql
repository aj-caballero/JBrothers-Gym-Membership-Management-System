-- ============================================================
--  Membership ID + Photo Migration
--  Run AFTER soft_delete_migration.sql
--  Execute once in phpMyAdmin > SQL tab on gym_db
-- ============================================================

ALTER TABLE members
    ADD COLUMN membership_id VARCHAR(30) UNIQUE DEFAULT NULL,
    ADD COLUMN photo_path    VARCHAR(255)       DEFAULT NULL;

-- Backfill existing members with auto-generated IDs
UPDATE members
SET    membership_id = CONCAT('GYM-', YEAR(join_date), '-', LPAD(id, 5, '0'))
WHERE  membership_id IS NULL;
