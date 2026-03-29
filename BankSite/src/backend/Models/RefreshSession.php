<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\Model;

class RefreshSession extends Model
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

    public function create(
        int $userId, string $jti, string $userAgent, string $ipAddress, int $expiresAt
    ): bool
    {
        $userAgent = htmlspecialchars($userAgent);
        $ipAddress = htmlspecialchars($ipAddress);
        $expiresAt = htmlspecialchars($expiresAt . "");

        if (!$userId || !$jti || !$userAgent || !$ipAddress || !$expiresAt) {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO refresh_sessions (user_id, jti, user_agent, ip_address, expires_at) VALUES (:ui, :ji, :ua, :ia, :ea)"
        );

        $stmt->bindParam(":ui", $userId);
        $stmt->bindParam(":ji", $jti);
        $stmt->bindParam(":ua", $userAgent);
        $stmt->bindParam(":ia", $ipAddress);
        $stmt->bindParam(":ea", $expiresAt);

        return $stmt->execute();
    }

    public function deleteByJTIS(array $jtis): bool
    {
        $isSuccessful = true;

        foreach ($jtis as $jti) {
            $stmt = $this->db->prepare("DELETE FROM refresh_sessions WHERE jti = :ji");
            $stmt->bindParam(":ji", $jti);

            $res = $stmt->execute();
            $isSuccessful = $isSuccessful ? $res : $isSuccessful;
        }

        return $isSuccessful;
    }

    public function getAll(): array|bool
    {
        $stmt = $this->db->prepare("SELECT * FROM refresh_sessions");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function get(string $jti): array|bool
    {
        $stmt = $this->db->prepare("SELECT * FROM refresh_sessions WHERE jti = :jti");

        $stmt->bindParam(":jti", $jti);

        $stmt->execute();
        return $stmt->fetch();
    }

    public function deleteByUserId(string $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM refresh_sessions WHERE user_id = :ui");
        $stmt->bindParam(":ui", $userId);

        return $stmt->execute();
    }

    public function update(string $jti): bool
    {
        $stmt = $this->db->prepare("UPDATE refresh_sessions SET is_revoked = 1 WHERE jti = :ji");
        $stmt->bindParam(":ji", $jti);

        $stmt->execute();
        return $stmt->rowCount() > 0; 
    }
}