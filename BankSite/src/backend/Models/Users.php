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
        $this->useBank();
    }

    public function useBank(): void
    {
        $this->db->exec("USE bank");
    }

    public function create(string $firstName, string $lastName, string $phoneNumber, string $password): bool
    {
        $firstName = htmlspecialchars($firstName);
        $lastName = htmlspecialchars($lastName);
        $phoneNumber = htmlspecialchars($phoneNumber);

        if (!$firstName || !$lastName || !$phoneNumber) {
            return false;
        }

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

    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function get(string $phoneNumber, string $password): array|bool
    {
        $phoneNumber = htmlspecialchars($phoneNumber);

        if (!$phoneNumber) {
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE phone_number = :phone_number"
        );

        $stmt->bindParam(":phone_number", $phoneNumber);

        $stmt->execute();
        $matchedUsers = $stmt->fetchAll();

        foreach ($matchedUsers as $user) {
            $hash = $user["password"];
            $valid = password_verify($password, $hash);

            if ($valid) {
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT, ["cost" => 12]);
                    
                    $stmt = $this->db->prepare(
                        "UPDATE users SET password = :new_password 
                        WHERE phone_number = :phone_number AND password = :password"
                    );

                    $stmt->bindParam(":phone_number", $phoneNumber);
                    $stmt->bindParam(":password", $hash);
                    $stmt->bindParam(":new_password", $newHash);

                    $stmt->execute();
                }
                
                return $user;
            }
        }

        return [];
    }
}