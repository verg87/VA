<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// This script will set up the database and initial tables.

try {
    $pdo = DBI;
    $dbname = $pdo->config["database"];

    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    
    $pdo->exec("USE $dbname;");

    $createTableSql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone_number VARCHAR(20) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($createTableSql);
} catch (\PDOException $e) {
    exit();
}
