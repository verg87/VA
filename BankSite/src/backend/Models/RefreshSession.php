<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\DB;
use App\Model;

class RefreshSession extends Model
{
    public function __construct(DB $db)
    {
        parent::__construct($db);
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

        $hashedJTI = hash("sha512", $jti);
        $hashedUserAgent = hash("sha512", $userAgent);
        $hashedIpAddress = hash("sha512", $ipAddress);

        $stmt->bindParam(":ui", $userId);
        $stmt->bindParam(":ji", $hashedJTI);
        $stmt->bindParam(":ua", $hashedUserAgent);
        $stmt->bindParam(":ia", $hashedIpAddress);
        $stmt->bindParam(":ea", $expiresAt);

        return $stmt->execute();
    }

    public function deleteByJTIS(array $jtis): array
    {
        if (empty($jtis)) {
            return ["status" => "success", "deleted" => 0];
        }
        
        $placeholders = implode(', ', array_fill(0, count($jtis), '?'));
        $stmt = $this->db->prepare("DELETE FROM refresh_sessions WHERE jti IN ($placeholders)");
       
        foreach ($jtis as $index => $jti) {
            $stmt->bindValue($index + 1, $jti);
        }

        $status = $stmt->execute(); 
        $numOfDeleted = $stmt->rowCount();

        return ["status" => $status ? "success" : "failure", "deleted" => $numOfDeleted];
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

        $hashedJTI = hash("sha512", $jti);
        $stmt->bindParam(":jti", $hashedJTI);

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

        $hashedJTI = hash("sha512", $jti);
        $stmt->bindParam(":ji", $hashedJTI);

        $stmt->execute();
        return $stmt->rowCount() > 0; 
    }
}