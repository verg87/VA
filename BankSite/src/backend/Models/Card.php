<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\DB;
use App\Model;
use App\Vault\Vault;
use Exception;

class Card extends Model
{
    private Vault $vault;

    public function __construct(DB $db, Vault $vault)
    {
        parent::__construct($db);
        $this->useBank();
        $this->vault = $vault;
    }

    public function useBank(): void
    {
        $this->db->exec("USE bank");
    }

    public function create(int $userId, string $cardNumber, string $cardType, int $amount = 0, string $expiresAt, string $cvv): bool
    {
        $userId = htmlspecialchars($userId . "");
        $cardNumber = htmlspecialchars($cardNumber);
        $cardType = htmlspecialchars($cardType);
        $amount = htmlspecialchars($amount . "");
        $expiresAt = htmlspecialchars($expiresAt);
        $cvv = htmlspecialchars($cvv);

        if (!$userId || !$cardNumber || !$cardType || $amount === "" || !$expiresAt || !$cvv) {
            return false;
        }

        if (!is_numeric($userId) || !is_numeric(str_replace(" ", "", $cardNumber)) || !is_numeric($amount) || !is_numeric($cvv)) {
            return false;
        }

        try {
            list($month, $year) = explode("/", $expiresAt);

            if (
                !$month || !$year || 
                !is_numeric($month) || !is_numeric($year) || 
                strlen($month) !== 2 || strlen($year) !== 2
            ) {
                return false;
            }

            $fullYear = substr(date("Y"), 0, -2) . $year;
            $expiresAt = mktime(0, 0, 0, (int) $month, 1, (int) $fullYear);
        } catch (Exception $err) {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO cards (user_id, card_number, secret_key, card_type, amount, expires_at, cvv) VALUES (:ui, :cn, :sk, :ct, :am, :ea, :cv)"
        );

        $algo = $_ENV['ENVELOPE_ENCRYPTION_ALGO'];
        $taglen = (int) $_ENV["TAG_LENGTH"];

        $key = openssl_random_pseudo_bytes(openssl_cipher_key_length($algo));
      
        $encryptedCardNumber = $this->encrypt($cardNumber, $key, $algo, $taglen);
        $encryptedCVV = $this->encrypt($cvv, $key, $algo, $taglen);

        $masterKey = $this->vault->getKV("masterkey");

        if (!$masterKey || $masterKey === "") {
            return false;
        }

        $encodedKey = base64_encode($key);
        $decodedMasterKey = base64_decode($masterKey);

        $encryptedKey = $this->encrypt($encodedKey, $decodedMasterKey, $algo, $taglen);

        $stmt->bindParam(":ui", $userId);
        $stmt->bindParam(":cn", $encryptedCardNumber);
        $stmt->bindParam(":sk", $encryptedKey);
        $stmt->bindParam(":ct", $cardType);
        $stmt->bindParam(":am", $amount);
        $stmt->bindParam(":ea", $expiresAt);
        $stmt->bindParam(":cv", $encryptedCVV);

        return $stmt->execute();
    }

    private function encrypt(string $data, string $key, string $algo, int $taglen): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algo));
        $cipherText = openssl_encrypt($data, $algo, $key, OPENSSL_RAW_DATA, $iv, $tag, "", $taglen);
        return base64_encode($iv . $tag . $cipherText);
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

    public function getSecretKeys(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, secret_key FROM cards"
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function updateSecretKey(int $id, string $newSecretKey): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cards SET secret_key = :sk WHERE id = :id"
        );

        $stmt->bindParam(":sk", $newSecretKey);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    public function updateSecretKeys(array $secretKeys): bool
    {
        $this->db->beginTransaction();

        foreach ($secretKeys as $secret) {
            if (!$this->updateSecretKey($secret['id'], $secret['key'])) {
                $this->db->rollBack();
                return false;
            }
        }

        return true;
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollBack(): bool
    {
        return $this->db->rollBack();
    }
}