<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Respect\Validation\ValidatorBuilder as v;

use App\DB;
use App\Model;

use Exception;

class User extends Model
{
    public function __construct(DB $db)
    {
        parent::__construct($db);
    }

    private function validate(string $firstName, string $lastName, string $email, string $phoneNumber): void
    {
        v::alpha()->assert($firstName);
        v::alpha()->assert($lastName);

        v::email()->assert($email);
        v::phone()->assert("+" . $phoneNumber);
    }

    public function create(string $firstName, string $lastName, string $email, string $phoneNumber, string $password): bool
    {
        try {
            $this->validate($firstName, $lastName, $email, $phoneNumber);

            $pwdHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                "INSERT INTO users (first_name, last_name, email, phone_number, password) VALUES (:fn, :ln, :em, :pn, :pw)"
            );

            return $stmt->execute([
                ":fn" => $firstName,
                ":ln" => $lastName,
                ":em" => $email,
                ":pn" => $phoneNumber,
                ":pw" => $pwdHash,
            ]);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, phone_number FROM users");
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
            v::intType()->positive()->assert($userId);

            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE id = :ui"
            );

            $stmt->bindParam(":ui", $userId);
            $stmt->execute();

            $user = $stmt->fetch();
            unset($user["password"]);

            return $user;
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function getByIds(array $ids): array|bool
    {
        try {
            v::notBlank()->allIntType()->allPositive()->assert($ids);

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("SELECT id, first_name, last_name FROM users WHERE id IN ($placeholders)");

            foreach ($ids as $index => $id) {
                $stmt->bindValue($index + 1, $id);
            }

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function getByPhone(string $phoneNumber): array|bool
    {
        try {
            v::phone()->assert("+" . $phoneNumber);

            $stmt = $this->db->prepare(
                "SELECT id, first_name, last_name, phone_number FROM users WHERE phone_number = :pn"
            );

            $stmt->bindParam(":pn", $phoneNumber);

            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            // var_dump($e->getMessage());
            return false;
        }
    }

    public function getByPhoneAndPWD(string $phoneNumber, string $password): array|bool
    {
        try {
            v::phone()->assert("+" . $phoneNumber);

            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE phone_number = :pn"
            );

            $stmt->bindParam(":pn", $phoneNumber);

            $stmt->execute();
            $user = $stmt->fetch();

            $hash = $user["password"];
            $valid = password_verify($password, $hash);
            
            unset($user["password"]);

            return $valid ? $user : [];
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function rehashPwd(string $phoneNumber, string $password): bool
    {
        try {
            v::phone()->assert("+" . $phoneNumber);

            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE phone_number = :pn"
            );

            $stmt->bindParam(":pn", $phoneNumber);

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