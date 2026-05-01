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
    }

    public function create(
        int $userId,
        string $jti,
        string $userAgent,
        string $ipAddress,
        int $expiresAt
    ): bool {
        if (empty($userId) || empty($jti) || empty($userAgent) || empty($ipAddress) || empty($expiresAt)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO refresh_sessions (user_id, jti, user_agent, ip_address, expires_at) VALUES (:ui, :ji, :ua, :ia, :ea)"
        );

        $secretKey = $_ENV['APP_KEY'];
        $hashedJTI = hash_hmac("sha512", $jti, $secretKey);
        $hashedUserAgent = hash_hmac("sha512", $userAgent, $secretKey);
        $hashedIpAddress = hash_hmac("sha512", $ipAddress, $secretKey);

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
        if (empty($jti)) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT * FROM refresh_sessions WHERE jti = :jti");

        $secretKey = $_ENV['APP_KEY'];
        $hashedJTI = hash_hmac("sha512", $jti, $secretKey);
        $stmt->bindParam(":jti", $hashedJTI);

        $stmt->execute();
        return $stmt->fetch();
    }

    public function deleteByUserId(int $userId): bool
    {
        if (empty($userId)) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM refresh_sessions WHERE user_id = :ui");
        $stmt->bindParam(":ui", $userId);

        return $stmt->execute();
    }

    public function update(string $jti): bool
    {
        if (empty($jti)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE refresh_sessions SET is_revoked = 1 WHERE jti = :ji");

        $secretKey = $_ENV['APP_KEY'];
        $hashedJTI = hash_hmac("sha512", $jti, $secretKey);
        $stmt->bindParam(":ji", $hashedJTI);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}