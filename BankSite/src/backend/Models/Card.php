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

    public function create(int $userId, string $cardNumber, string $cardType, int $amount = 0, int $expiresAt): bool
    {
        $userId = htmlspecialchars($userId . "");
        $cardNumber = htmlspecialchars($cardNumber);
        $cardType = htmlspecialchars($cardType);
        $amount = htmlspecialchars($amount . "");
        $expiresAt = htmlspecialchars($expiresAt . "");

        if (!$userId || !$cardNumber || !$cardType || $amount === "" || !$expiresAt) {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO cards (user_id, card_number, card_type, amount, expires_at) VALUES (:ui, :cn, :ct, :am, :ea)"
        );

        $stmt->bindParam(":ui", $userId);
        $stmt->bindParam(":cn", $cardNumber);
        $stmt->bindParam(":ct", $cardType);
        $stmt->bindParam(":am", $amount);
        $stmt->bindParam(":ea", $expiresAt);

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