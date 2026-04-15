-- Staff-Only Portal Migration
-- This migration updates the database schema to reflect removal of member login portal
-- Members are now managed only through staff/admin panel, not self-service login

-- Add member portal support fields if they don't exist
ALTER TABLE members ADD COLUMN IF NOT EXISTS membership_id VARCHAR(20) UNIQUE;
ALTER TABLE members ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255);

-- Rename and annotate the password column as legacy (no longer used for member login)
-- Note: We keep the column for backward compatibility but it's not actively used
-- Members no longer have login credentials - they're managed by staff only

-- Optional: If you want to completely remove the password column, uncomment the line below
-- ALTER TABLE members DROP COLUMN password;

-- Optional: If you want to clear all existing member passwords, uncomment below
-- UPDATE members SET password = NULL;

-- Add a notice to document the change
-- SELECT 'Staff-Only Migration Complete: Members no longer have login portal. Password column kept for backward compatibility but unused.' as migration_status;
