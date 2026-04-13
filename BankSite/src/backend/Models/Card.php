<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\Model;

class Card extends Model
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

    public function create(int $userId, string $cardType, int $amount = 0): bool
    {
        $userId = htmlspecialchars($userId . "");
        $cardType = htmlspecialchars($cardType);
        $amount = htmlspecialchars($amount . "");

        if (!$userId || !$cardType || $amount === "") {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO cards (user_id, card_type, amount) VALUES (:ui, :ct, :am)"
        );

        $stmt->bindParam(":ui", $userId);
        $stmt->bindParam(":ct", $cardType);
        $stmt->bindParam(":am", $amount);

        return $stmt->execute();
    }

    public function getLatestId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    public function getById(int $id): array|bool
    {
        $id = htmlspecialchars($id . "");

        if (!$id || !is_numeric($id)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM cards WHERE id = :id"
        );

        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getByUserId(int $userId): array|bool
    {
        $userId = htmlspecialchars($userId . "");

        if (!$userId) {
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM cards WHERE user_id = :ui"
        );

        $stmt->bindParam(":ui", $userId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}