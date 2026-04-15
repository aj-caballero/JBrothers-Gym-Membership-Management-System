-- ============================================================
--  MangoPay Simulation Migration
--  Run once in phpMyAdmin or MySQL CLI on gym_db
-- ============================================================

ALTER TABLE payments
    MODIFY COLUMN payment_method ENUM('Cash', 'GCash', 'Card', 'MangoPay') NOT NULL;

ALTER TABLE payments
    MODIFY COLUMN status ENUM('Paid', 'Pending', 'Cancelled') DEFAULT 'Paid';

ALTER TABLE payments
    ADD COLUMN requested_plan_id INT NULL AFTER member_id,
    ADD COLUMN gateway VARCHAR(50) NULL AFTER payment_method,
    ADD COLUMN gateway_transaction_id VARCHAR(80) NULL AFTER gateway,
    ADD COLUMN gateway_status VARCHAR(30) NULL AFTER gateway_transaction_id,
    ADD COLUMN gateway_payload TEXT NULL AFTER gateway_status;

ALTER TABLE payments
    ADD CONSTRAINT fk_payments_requested_plan
    FOREIGN KEY (requested_plan_id) REFERENCES membership_plans(id)
    ON DELETE SET NULL;
