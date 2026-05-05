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

class Card extends Model
{
    private Vault $vault;

    public function __construct(DB $db, Vault $vault)
    {
        parent::__construct($db);
        $this->vault = $vault;
    }

    /**
     * @throws InvalidArgumentException|Exception
     */
    private function validateTransactionCreationArgs(int $userId, string $cardNumber, string $depositType, int $amount): array
    {
        return [];
    }

    public function create(int $userId, string $cardNumber, string $depositType, int $amount): bool
    {
        try {
            $validatedData = $this->validateTransactionCreationArgs($userId, $cardNumber, $depositType, $amount);

            return false;
        } catch (Exception $e) {
            // maybe log it
            var_dump($e->getMessage());
            return false;
        }
    }
}