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

class Transaction extends Model
{
    public function __construct(DB $db)
    {
        parent::__construct($db);
    }

    /**
     * @throws InvalidArgumentException|Exception
     */
    private function validateTransactionCreationArgs(int $userId, int $receiverUserId, int $receiverCardId, string $depositType, float $amount, int|null $cardId): array
    {
        $validatedUserId = $this->validateId($userId);
        $validatedReceiverUserId = $this->validateId($receiverUserId);
        $validatedCardId = $this->validateId($receiverCardId);
        $validatedCardId = null;

        if (gettype($cardId) === "integer") {
            $validatedCardId = $this->validateId($cardId);
        } else if (gettype($cardId) !== "NULL") {
            throw new InvalidArgumentException("Card ID must be either an integer or null");
        }

        if (!in_array($depositType, ["transfer", "check", "cash"])) {
            throw new InvalidArgumentException("Invalid deposit type");
        }

        $validatedAmount = filter_var($amount, FILTER_VALIDATE_FLOAT, [
            "options" => ["min_range" => 0, "max_range" => 100000]
        ]);
        if ($validatedAmount === false) {
            throw new InvalidArgumentException("Deposit limit");
        }

        return [
            "userId" => $validatedUserId,
            "cardId" => $validatedCardId,
            "receiverUserId" => $validatedReceiverUserId,
            "receiverCardId" => $validatedCardId,
            "depositType" => $depositType,
            "amount" => $validatedAmount
        ];
    }

    public function create(int $userId, int $receiverUserId, int $receiverCardId, string $depositType, float $amount, int|null $cardId = null): bool
    {
        try {
            $validatedData = $this->validateTransactionCreationArgs($userId, $receiverUserId, $receiverCardId, $depositType, $amount, $cardId);

            $stmt = $this->db->prepare(
                "INSERT INTO transactions (user_id, card_id, receiver_user_id, receiver_card_id, type, amount) VALUES (:ui, :ci, :rui, :rci, :ty, :am)"
            );

            return $stmt->execute([
                ":ui" => $validatedData["userId"],
                ":ci" => $validatedData["cardId"],
                ":rui" => $validatedData["receiverUserId"],
                ":rci" => $validatedData["receiverCardId"],
                ":ty" => $validatedData["depositType"],
                ":am" => $validatedData["amount"],
            ]);
        } catch (Exception $e) {
            // maybe log it
            var_dump($e->getMessage());
            return false;
        }
    }

    public function getAllByUserId(int $userId): array|bool
    {
        try {
            $userId = $this->validateId($userId);

            $stmt = $this->db->prepare(
                "SELECT * FROM transactions WHERE user_id = :ui OR receiver_user_id = :rui"
            );

            $stmt->execute([":ui" => $userId, ":rui" => $userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }
}