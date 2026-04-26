<?php
require_once __DIR__ . '/../config/database.php';
try {
    $pdo->exec("ALTER TABLE payments ADD COLUMN processed_by INT NULL AFTER status");
    $pdo->exec("ALTER TABLE payments ADD CONSTRAINT fk_processed_by FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL");
    echo "Column added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
