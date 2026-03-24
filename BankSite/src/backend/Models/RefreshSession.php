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
        $userId = htmlspecialchars($userId . "");
        $jti = htmlspecialchars($jti);
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

    public function get(string $jti): array
    {
        $jti = htmlspecialchars($jti);

        if (!$jti) {
            return [];
        }

        $stmt = $this->db->prepare("SELECT * FROM refresh_sessions WHERE jti = :jti");

        $stmt->bindParam(":jti", $jti);

        $stmt->execute();
        return $stmt->fetch();
    }
}