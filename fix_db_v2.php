<?php
require_once 'config/db.php';

try {
  
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT 'default_agent.jpg' AFTER email");
        echo "Column 'profile_image' added successfully.<br>";
    } else {
        echo "Column 'profile_image' already exists.<br>";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'otp_code'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN otp_code VARCHAR(10) DEFAULT NULL, ADD COLUMN otp_expiry DATETIME DEFAULT NULL, ADD COLUMN is_verified TINYINT(1) DEFAULT 0, ADD COLUMN account_status ENUM('active', 'blocked') DEFAULT 'active'");
        echo "Tactical security columns added.<br>";
    }

    echo "Database Intelligence synchronized.";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>