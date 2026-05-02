<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\DB;
use App\Model;

use App\Vault\Vault;
use Exception;
use InvalidArgumentException;

class RefreshSession extends Model
{
    private Vault $vault;

    public function __construct(DB $db, Vault $vault)
    {
        parent::__construct($db);
        $this->vault = $vault;
    }

    private function validateRefSessionCreationArgs(int $userId, string $jti, string $userAgent, string $ipAddress, int $expiresAt): array
    {
        $validatedUserId = $this->validateId($userId);
        $validatedIpAddress = filter_var($ipAddress, FILTER_VALIDATE_IP);

        if ($validatedIpAddress === false) {
            throw new InvalidArgumentException("Invalid IP address");
        }

        $validatedExpiresAt = filter_var($expiresAt, FILTER_VALIDATE_INT);

        if ($validatedExpiresAt === false) {
            throw new InvalidArgumentException("Invalid expiration date");
        }

        return [
            "userId" => $validatedUserId,
            "jti" => $jti,
            "userAgent" => $userAgent,
            "ipAddress" => $validatedIpAddress,
            "expiresAt" => $validatedExpiresAt,
        ];
    }

    public function create(
        int $userId,
        string $jti,
        string $userAgent,
        string $ipAddress,
        int $expiresAt
    ): bool {
        try {
            $validatedData = $this->validateRefSessionCreationArgs($userId, $jti, $userAgent, $ipAddress, $expiresAt);

            $stmt = $this->db->prepare(
                "INSERT INTO refresh_sessions (user_id, jti, user_agent, ip_address, expires_at) VALUES (:ui, :ji, :ua, :ia, :ea)"
            );

            $refkey = $this->vault->getKV("refkey");
            $hashedJTI = hash_hmac("sha512", $validatedData["jti"], $refkey);
            $hashedUserAgent = hash_hmac("sha512", $validatedData["userAgent"], $refkey);
            $hashedIpAddress = hash_hmac("sha512", $validatedData["ipAddress"], $refkey);

            $stmt->bindParam(":ui", $validatedData["userId"]);
            $stmt->bindParam(":ji", $hashedJTI);
            $stmt->bindParam(":ua", $hashedUserAgent);
            $stmt->bindParam(":ia", $hashedIpAddress);
            $stmt->bindParam(":ea", $validatedData["expiresAt"]);

            return $stmt->execute();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
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

        $refkey = $this->vault->getKV("refkey");
        $hashedJTI = hash_hmac("sha512", $jti, $refkey);
        $stmt->bindParam(":jti", $hashedJTI);

        $stmt->execute();
        return $stmt->fetch();
    }

    private function validateId(int $id): int
    {
        $validatedId = filter_var($id, FILTER_VALIDATE_INT);

        if ($validatedId === false) {
            throw new InvalidArgumentException("Passed ID is not an integer");
        }
        
        return $validatedId;
    }

    public function deleteByUserId(int $userId): bool
    {
        try {
            $userId = $this->validateId($userId);

            $stmt = $this->db->prepare("DELETE FROM refresh_sessions WHERE user_id = :ui");
            $stmt->bindParam(":ui", $userId);

            return $stmt->execute();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function update(string $jti): bool
    {
        if (empty($jti)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE refresh_sessions SET is_revoked = 1 WHERE jti = :ji");

        $refkey = $this->vault->getKV("refkey");
        $hashedJTI = hash_hmac("sha512", $jti, $refkey);
        $stmt->bindParam(":ji", $hashedJTI);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}