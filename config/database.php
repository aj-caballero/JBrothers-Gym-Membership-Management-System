<?php
// C:/Users/Kyle/GYM MEMBERSHIP/config/database.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Assuming default XAMPP/WAMP
define('DB_PASS', '');
define('DB_NAME', 'gym_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fetch attributes as objects by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch(PDOException $e) {
    die("ERROR: Could not connect to database. " . $e->getMessage());
}
?>
