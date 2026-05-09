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
    private function validateTransactionCreationArgs(int $userId, int $receiverUserId, int $cardId, string $depositType, float $amount): array
    {
        $validatedUserId = $this->validateId($userId);
        $validatedReceiverUserId = $this->validateId($receiverUserId);
        $validatedCardId = $this->validateId($cardId);

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
            "receiverUserId" => $validatedReceiverUserId,
            "cardId" => $validatedCardId,
            "depositType" => $depositType,
            "amount" => $validatedAmount
        ];
    }

    public function create(int $userId, int $receiverUserId, int $cardId, string $depositType, float $amount): bool
    {
        try {
            $validatedData = $this->validateTransactionCreationArgs($userId, $receiverUserId, $cardId, $depositType, $amount);

            $stmt = $this->db->prepare(
                "INSERT INTO transactions (user_id, receiver_user_id, receiver_card_id, type, amount) VALUES (:ui, :rui, :rci, :ty, :am)"
            );

            return $stmt->execute([
                ":ui" => $validatedData["userId"],
                ":rui" => $validatedData["receiverUserId"],
                ":rci" => $validatedData["cardId"],
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