CREATE DATABASE IF NOT EXISTS gym_db;
USE gym_db;

CREATE TABLE gym_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gym_name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    address TEXT,
    currency VARCHAR(10) DEFAULT 'PHP',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO gym_settings (gym_name) VALUES ('Iron Forge Gym');

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    permissions VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin: admin@gym.com / password123
INSERT INTO users (full_name, email, password, role) 
VALUES ('System Admin', 'admin@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    join_date DATE NOT NULL,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Inactive',
    membership_id VARCHAR(30) UNIQUE,
    photo_path VARCHAR(255),
    password VARCHAR(255) NULL DEFAULT NULL COMMENT 'Legacy field - members no longer login via this system',
    deleted_at DATETIME DEFAULT NULL COMMENT 'Soft delete timestamp',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    deleted_at DATETIME DEFAULT NULL COMMENT 'Soft delete timestamp',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO membership_plans (plan_name, duration_days, price, description) VALUES 
('Monthly Pass', 30, 1000.00, 'Unlimited access for 30 days.'),
('3 Months Plan', 90, 2700.00, 'Unlimited access for 90 days.'),
('Annual Membership', 365, 10000.00, 'Unlimited access for a full year.');

CREATE TABLE memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Active', 'Expired', 'Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    requested_plan_id INT NULL,
    membership_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'GCash', 'Card', 'PayMongo') NOT NULL,
    gateway VARCHAR(50) NULL,
    gateway_transaction_id VARCHAR(80) NULL,
    gateway_status VARCHAR(30) NULL,
    gateway_payload TEXT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Paid', 'Pending', 'Cancelled') DEFAULT 'Paid',
    processed_by INT NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    time_in DATETIME NOT NULL,
    time_out DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('Expiry', 'Payment', 'System') NOT NULL,
    member_id INT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email_attempt VARCHAR(100) NOT NULL,
    ip_address VARCHAR(50),
    user_agent TEXT,
    status ENUM('Success', 'Failed') NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- SCHEMA COMPLETE
-- This script includes all migrations in a single consolidated file:
-- ✓ Soft delete support (deleted_at columns on members & plans)
-- ✓ Membership ID & photo support (membership_id, photo_path)
-- ✓ PayMongo payment gateway (gateway fields in payments table)
-- ✓ Staff-only portal (members table password marked as legacy)
-- ✓ Payment processing audit (processed_by in payments table)
-- ✓ Login activity tracking (login_logs table)
-- 
-- No additional migrations needed - this is the complete current schema.
-- ============================================================
