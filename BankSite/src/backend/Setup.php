<?php

declare(strict_types=1);

namespace App;

require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->overload(__DIR__ . "\\..\\..\\.env", __DIR__ . "\\..\\..\\.dev.env");

// This script will set up the database and initial tables.

try {
    $pdo = new DB((new Config($_ENV))->config);
    $dbname = $pdo->config["database"];

    $pdo->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    
    $pdo->exec("USE $dbname;");

    $createUsersTableSql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name CHAR(50) NOT NULL,
        last_name CHAR(50) NOT NULL,
        phone_number CHAR(20) NOT NULL UNIQUE,
        password CHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $createRefreshSessionsTableSql = "CREATE TABLE refresh_sessions (
        id INT auto_increment PRIMARY KEY,
        user_id INT NOT NULL,
        jti CHAR(36) UNIQUE NOT NULL,
        user_agent CHAR(255) NOT NULL,
        ip_address CHAR(45) NOT NULL,
        expires_at INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        CONSTRAINT fk_user_sessions 
            FOREIGN KEY (user_id) REFERENCES users(id) 
            ON DELETE CASCADE,
            
        UNIQUE INDEX idx_jti (jti)
    )";

    $pdo->exec($createUsersTableSql);
    $pdo->exec($createRefreshSessionsTableSql);
} catch (\PDOException $e) {
    var_dump($e->getMessage());
    exit();
}
