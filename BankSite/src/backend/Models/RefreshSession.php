<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Respect\Validation\ValidatorBuilder as v;

use App\DB;
use App\Model;

use App\Vault\Vault;
use Exception;

class RefreshSession extends Model
{
    private Vault $vault;

    public function __construct(DB $db, Vault $vault)
    {
        parent::__construct($db);
        $this->vault = $vault;
    }

    private function validate(int $userId, string $ipAddress, int $expiresAt): void
    {
        v::intType()->positive()->assert($userId);
        v::ip()->assert($ipAddress);

        v::intType()->positive()->assert($expiresAt);
    }

    public function create(
        int $userId,
        string $jti,
        string $userAgent,
        string $ipAddress,
        int $expiresAt
    ): bool {
        try {
            $this->validate($userId, $ipAddress, $expiresAt);

            $stmt = $this->db->prepare(
                "INSERT INTO refresh_sessions (user_id, jti, user_agent, ip_address, expires_at) VALUES (:ui, :ji, :ua, :ia, :ea)"
            );

            $refkey = $this->vault->getKV("refkey");
            $hashedJTI = hash_hmac("sha512", $jti, $refkey);
            $hashedUserAgent = hash_hmac("sha512", $userAgent, $refkey);
            $hashedIpAddress = hash_hmac("sha512", $ipAddress, $refkey);

            return $stmt->execute([
                ":ui" => $userId,
                ":ji" => $hashedJTI,
                ":ua" => $hashedUserAgent,
                ":ia" => $hashedIpAddress,
                ":ea" => $expiresAt,
            ]);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function deleteByJTIS(array $jtis): array
    {
        try {
            v::notBlank()->allStringType()->assert($jtis);

            $placeholders = implode(', ', array_fill(0, count($jtis), '?'));
            $stmt = $this->db->prepare("DELETE FROM refresh_sessions WHERE jti IN ($placeholders)");

            foreach ($jtis as $index => $jti) {
                $stmt->bindValue($index + 1, $jti);
            }

            $status = $stmt->execute();
            $numOfDeleted = $stmt->rowCount();

            return ["status" => $status ? "success" : "failure", "deleted" => $numOfDeleted];
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return ["status" => "success", "deleted" => 0];
        }
    }

    public function getAll(): array|bool
    {
        $stmt = $this->db->prepare("SELECT * FROM refresh_sessions");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function get(string $jti): array|bool
    {
        try {
            v::stringType()->notBlank()->assert($jti);

            $stmt = $this->db->prepare("SELECT * FROM refresh_sessions WHERE jti = :jti");

            $refkey = $this->vault->getKV("refkey");
            $hashedJTI = hash_hmac("sha512", $jti, $refkey);
            $stmt->bindParam(":jti", $hashedJTI);

            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function deleteByUserId(int $userId): bool
    {
        try {
            v::intType()->positive()->assert($userId);

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
        try {
            v::stringType()->notBlank()->assert($jti);

            $stmt = $this->db->prepare("UPDATE refresh_sessions SET is_revoked = 1 WHERE jti = :ji");

            $refkey = $this->vault->getKV("refkey");
            $hashedJTI = hash_hmac("sha512", $jti, $refkey);
            $stmt->bindParam(":ji", $hashedJTI);

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }
}