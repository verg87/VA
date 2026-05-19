<?php

declare(strict_types=1);

namespace App\Models;

use Respect\Validation\ValidatorBuilder as v;

use App\DB;
use App\Model;

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
    private function validate(int $userId, int $receiverUserId, int $receiverCardId, string $depositType, float $amount, int|null $cardId): void
    {
        v::intType()->positive()->assert($userId);
        v::intType()->positive()->assert($receiverUserId);
        v::intType()->positive()->assert($receiverCardId);

        v::alpha()->containsAny(["transfer", "check", "cash"])->assert($depositType);
        v::floatType()->between(0, 1000000)->assert($amount); // transaction limit

        v::nullOr(v::intType()->positive())->assert($cardId);
    }

    public function create(int $userId, int $receiverUserId, int $receiverCardId, string $depositType, float $amount, int|null $cardId = null): bool
    {
        $fn = function() use($userId, $receiverUserId, $receiverCardId, $depositType, $amount, $cardId) {
            $this->validate($userId, $receiverUserId, $receiverCardId, $depositType, $amount, $cardId);

            $stmt = $this->db->prepare(
                "INSERT INTO transactions (user_id, card_id, receiver_user_id, receiver_card_id, type, amount) VALUES (:ui, :ci, :rui, :rci, :ty, :am)"
            );

            return $stmt->execute([
                ":ui" => $userId,
                ":ci" => $cardId,
                ":rui" => $receiverUserId,
                ":rci" => $receiverCardId,
                ":ty" => $depositType,
                ":am" => $amount,
            ]);
        };

        return $this->tryAndLog($fn);
    }

    public function getAllByUserId(int $userId): array|bool
    {
        $fn = function() use($userId) {
            v::intType()->positive()->assert($userId);

            $stmt = $this->db->prepare(
                "SELECT * FROM transactions WHERE user_id = :ui OR receiver_user_id = :rui"
            );

            $stmt->execute([":ui" => $userId, ":rui" => $userId]);
            return $stmt->fetchAll();
        };

        return $this->tryAndLog($fn);
    }
}