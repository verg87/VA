<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "/../../../vendor/autoload.php";

use App\DB;
use App\Model;
use App\Vault\Vault;
use App\Helpers\Functions;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

class Account extends Model
{
    public function __construct(DB $db)
    {
        parent::__construct($db);
    }

    /**
     * @throws InvalidArgumentException|Exception
     */
    private function validateAccountCreationArgs(int $userId, int $cardId): array
    {
        return [
            "userId" => $this->validateId($userId),
            "cardId" => $this->validateId($cardId),
        ];
    }

    public function create(int $userId, int $cardId): bool
    {
        try {
            $validatedData = $this->validateAccountCreationArgs($userId, $cardId);

            $stmt = $this->db->prepare(
                "INSERT INTO accounts (user_id, card_id) VALUES (:ui, :ci)"
            );

            return $stmt->execute([
                ":ui" => $validatedData["userId"],
                ":ci" => $validatedData["cardId"]
            ]);
        } catch (Exception $e) {
            // maybe log it
            var_dump($e->getMessage());
            return false;
        }
    }

    public function getByUserId(int $userId): array|bool
    {
        try {
            $userId = $this->validateId($userId);

            $stmt = $this->db->prepare(
                "SELECT * FROM accounts WHERE user_id = :ui"
            );

            $stmt->execute([":ui" => $userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }
}