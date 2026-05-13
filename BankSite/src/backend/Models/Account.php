<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "/../../../vendor/autoload.php";

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
        try {
            $this->validate($userId, $cardId);

            $stmt = $this->db->prepare(
                "INSERT INTO accounts (user_id, card_id) VALUES (:ui, :ci)"
            );

            return $stmt->execute([
                ":ui" => $userId,
                ":ci" => $cardId
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
            v::intType()->positive()->assert($userId);

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