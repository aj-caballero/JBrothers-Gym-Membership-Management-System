<?php
require_once __DIR__ . '/../config/database.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        email_attempt VARCHAR(100) NOT NULL,
        ip_address VARCHAR(50),
        user_agent TEXT,
        status ENUM('Success', 'Failed') NOT NULL,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "login_logs table created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
