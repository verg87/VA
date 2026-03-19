<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\Model;

class Users extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->createInitialDatabaseIfNotExists();
    }

    public function createInitialDatabaseIfNotExists(): void
    {
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->db->exec("CREATE DATABASE IF NOT EXISTS bank DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $this->db->exec("USE bank");
    }

    public function create(string $firstName, string $lastName, string $phoneNumber, string $password): bool
    {
        $this->createInitialTableIfNotExists();

        $pwdHash = password_hash($password, PASSWORD_DEFAULT, ["cost" => 12]);

        $stmt = $this->db->prepare(
            "INSERT INTO users (first_name, last_name, phone_number, password) VALUES (:first_name, :last_name, :phone_number, :password)"
        );

        $stmt->bindParam(":first_name", $firstName);
        $stmt->bindParam(":last_name", $lastName);
        $stmt->bindParam(":phone_number", $phoneNumber);
        $stmt->bindParam(":password", $pwdHash);

        return $stmt->execute();
    }

    public function createInitialTableIfNotExists(): void
    {
        $createTableSql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->db->exec($createTableSql);
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function get(string $name, string $lastName, string $password): array
    {
        $stmt = $this->db->prepare(
            "SELECT password FROM users WHERE first_name = :first_name AND last_name = :last_name"
        );

        $stmt->bindParam(":first_name", $name);
        $stmt->bindParam(":last_name", $lastName);

        $stmt->execute();
        $matchedPwd = $stmt->fetchAll();

        foreach ($matchedPwd as $pwd) {
            $hash = $pwd["password"];
            $valid = password_verify($password, $hash);

            if ($valid) {
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT, ["cost" => 12]);
                    // update password
                }
                // login user
            }
            else {
                // login data is invalid
            }
        }

        return [];
    }
}