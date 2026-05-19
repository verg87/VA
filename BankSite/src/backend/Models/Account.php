<?php

declare(strict_types=1);

namespace App\Models;

use Respect\Validation\ValidatorBuilder as v;

use App\DB;
use App\Model;

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
    private function validate(int $userId, int $cardId): void
    {
        v::intType()->positive()->assert($userId);
        v::intType()->positive()->assert($cardId);
    }

    public function create(int $userId, int $cardId): bool
    {
        $fn = function() use ($userId, $cardId) {
            $this->validate($userId, $cardId);

            $stmt = $this->db->prepare(
                "INSERT INTO accounts (user_id, card_id) VALUES (:ui, :ci)"
            );

            return $stmt->execute([
                ":ui" => $userId,
                ":ci" => $cardId
            ]);
        };

        return $this->tryAndLog($fn);
    }

    public function getByUserId(int $userId): array|bool
    {
        $fn = function() use ($userId) {
            v::intType()->positive()->assert($userId);

            $stmt = $this->db->prepare(
                "SELECT * FROM accounts WHERE user_id = :ui"
            );

            $stmt->execute([":ui" => $userId]);
            return $stmt->fetch();
        };

        return $this->tryAndLog($fn);
    }

    public function update(int $userId, int $newCardId): bool
    {
        $fn = function() use ($userId, $newCardId) {
            v::intType()->positive()->assert($userId);
            v::intType()->positive()->assert($newCardId);

            $stmt = $this->db->prepare(
                "UPDATE accounts SET card_id = :ci WHERE user_id = :ui"
            );

            return $stmt->execute([":ci" => $newCardId, ":ui" => $userId]);
        };

        return $this->tryAndLog($fn);
    }
}