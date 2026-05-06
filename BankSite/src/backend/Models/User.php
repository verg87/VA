<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\DB;
use App\Model;

use InvalidArgumentException;
use Exception;

class User extends Model
{
    public function __construct(DB $db)
    {
        parent::__construct($db);
    }

    private function validateUserCreationArgs(string $firstName, string $lastName, string $email, string $phoneNumber, string $password): array
    {
        if (!preg_match("/^[a-zA-Z]{2,}$/", $firstName)) {
            throw new InvalidArgumentException("Invalid name");
        }

        if (!preg_match("/^[a-zA-Z]{2,}$/", $lastName)) {
            throw new InvalidArgumentException("Invalid lastname");
        }

        $validatedEmail = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($validatedEmail === false) {
            throw new InvalidArgumentException("Invalid email");
        }

        $validatedPhoneNumber = $this->validatePhone($phoneNumber);

        return [
            "firstName" => $firstName,
            "lastName" => $lastName,
            "email" => $validatedEmail,
            "phoneNumber" => $validatedPhoneNumber,
            "password" => $password
        ];
    }

    public function create(string $firstName, string $lastName, string $email, string $phoneNumber, string $password): bool
    {
        try {
            $validatedData = $this->validateUserCreationArgs($firstName, $lastName, $email, $phoneNumber, $password);

            $pwdHash = password_hash($validatedData["password"], PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                "INSERT INTO users (first_name, last_name, email, phone_number, password) VALUES (:fn, :ln, :em, :pn, :pw)"
            );

            return $stmt->execute([
                ":fn" => $validatedData["firstName"],
                ":ln" => $validatedData["lastName"],
                ":em" => $validatedData["email"],
                ":pn" => $validatedData["phoneNumber"],
                ":pw" => $pwdHash,
            ]);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getLatestUserId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    public function getById(int $userId): array|bool
    {
        try {
            $userId = $this->validateId($userId);

            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE id = :ui"
            );

            $stmt->bindParam(":ui", $userId);
            $stmt->execute();

            return $stmt->fetch();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    private function validatePhone(string $phoneNumber): int
    {
        $validatedPhoneNumber = filter_var($phoneNumber, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 2900000, "max_range" => 999999999999999]
        ]);

        if ($validatedPhoneNumber === false) {
            throw new InvalidArgumentException("Invalid phone number");
        }

        return $validatedPhoneNumber;
    }

    public function getByPhoneAndPWD(string $phoneNumber, string $password): array|bool
    {
        try {
            $phoneNumber = $this->validatePhone($phoneNumber);

            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE phone_number = :phone_number"
            );

            $stmt->bindParam(":phone_number", $phoneNumber);

            $stmt->execute();
            $user = $stmt->fetch();

            $hash = $user["password"];
            $valid = password_verify($password, $hash);

            return $valid ? $user : [];
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function rehashPwd(string $phoneNumber, string $password): bool
    {
        try {
            $phoneNumber = $this->validatePhone($phoneNumber);

            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE phone_number = :phone_number"
            );

            $stmt->bindParam(":phone_number", $phoneNumber);

            $stmt->execute();
            $user = $stmt->fetch();

            $hash = $user["password"];
            $valid = password_verify($password, $hash);

            if ($valid && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                        
                $stmt = $this->db->prepare(
                    "UPDATE users SET password = :new_password 
                    WHERE phone_number = :phone_number AND password = :password"
                );

                $stmt->bindParam(":phone_number", $phoneNumber);
                $stmt->bindParam(":password", $hash);
                $stmt->bindParam(":new_password", $newHash);

                return $stmt->execute();
            }

            return false;
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }
}