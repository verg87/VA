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
    private function validateCardCreationArgs(int $userId, string $cardNumber, string $cardType, int $amount, string $expiresAt, string $cvv): array
    {
        $validatedUserId = $this->validateId($userId);

        if (!preg_match("/^\d{4} \d{4} \d{4} \d{4}$/", $cardNumber)) {
            throw new InvalidArgumentException("Invalid card number format");
        }

        if (!in_array($cardType, ["debit", "credit", "overdraft", "prepaid"])) {
            throw new InvalidArgumentException("Invalid card type");
        }

        $validatedAmount = filter_var($amount, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 0, "max_range" => 1000000]
        ]);
        if ($validatedAmount === false) {
            throw new InvalidArgumentException("Invalid amount");
        }

        try {
            list($firstNumber, $secondNumber, $thirdNumber) = str_split($cvv);

            $validatedFirstNumber = filter_var($firstNumber, FILTER_VALIDATE_INT, [
                "options" => ["min_range" => 0, "max_range" => 9]
            ]);

            $validatedSecondNumber = filter_var($secondNumber, FILTER_VALIDATE_INT, [
                "options" => ["min_range" => 0, "max_range" => 9]
            ]);

            $validatedThirdNumber = filter_var($thirdNumber, FILTER_VALIDATE_INT, [
                "options" => ["min_range" => 0, "max_range" => 9]
            ]);

            if ($validatedFirstNumber === false || $validatedSecondNumber === false || $validatedThirdNumber === false) {
                throw new InvalidArgumentException("Invalid CVV");
            }

            $validatedCvv = $validatedFirstNumber . $validatedSecondNumber . $validatedThirdNumber;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Invalid CVV");
        }

        try {
            $expiresAtDate = DateTimeImmutable::createFromFormat('m/y', $expiresAt);
            if ($expiresAtDate === false) {
                throw new InvalidArgumentException('Invalid expiration date format. Use "mm/yy"');
            }
            $expiresAtTimestamp = $expiresAtDate->modify('last day of this month')->getTimestamp();
        } catch (Exception $e) {
            throw new InvalidArgumentException('Error processing expiration date: ' . $e->getMessage());
        }

        return [
            "user_id" => $validatedUserId,
            "card_number" => $cardNumber,
            "card_type" => $cardType,
            "amount" => $validatedAmount,
            "expires_at" => $expiresAtTimestamp,
            "cvv" => $validatedCvv,
        ];
    }

    public function create(int $userId, string $cardNumber, string $cardType, int $amount = 0, string $expiresAt, string $cvv): bool
    {
        try {
            $validatedData = $this->validateCardCreationArgs($userId, $cardNumber, $cardType, $amount, $expiresAt, $cvv);

            $algo = $_ENV['ENVELOPE_ENCRYPTION_ALGO'];
            $taglen = (int) $_ENV["TAG_LENGTH"];

            $dataKey = openssl_random_pseudo_bytes(openssl_cipher_key_length($algo));

            $encryptedCardNumber = $this->encrypt($validatedData['card_number'], $dataKey, $algo, $taglen);
            $encryptedCVV = $this->encrypt($validatedData['cvv'], $dataKey, $algo, $taglen);

            $masterKey = $this->vault->getKV("masterkey");
            if (!$masterKey) {
                throw new Exception("Could not retrieve master key from Vault.");
            }
            $decodedMasterKey = base64_decode($masterKey);

            $encryptedDataKey = $this->encrypt(base64_encode($dataKey), $decodedMasterKey, $algo, $taglen);

            $stmt = $this->db->prepare(
                "INSERT INTO cards (user_id, card_number, secret_key, card_type, amount, expires_at, cvv) VALUES (:ui, :cn, :sk, :ct, :am, :ea, :cv)"
            );

            return $stmt->execute([
                ":ui" => $validatedData["user_id"],
                ":cn" => $encryptedCardNumber,
                ":sk" => $encryptedDataKey,
                ":ct" => $validatedData["card_type"],
                ":am" => $validatedData["amount"],
                ":ea" => $validatedData["expires_at"],
                ":cv" => $encryptedCVV
            ]);
        } catch (Exception $e) {
            // maybe log it
            var_dump($e->getMessage());
            return false;
        }
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

    /**
     * @throws InvalidArgumentException
     */
    private function validateId(int $id): int
    {
        $validatedId = filter_var($id, FILTER_VALIDATE_INT);

        if ($validatedId === false) {
            throw new InvalidArgumentException("Passed ID is not an integer");
        }
        
        return $validatedId;
    }

    private function decrypt(string $data, string $key, string $algo, int $taglen): string
    {
        $ivlen = openssl_cipher_iv_length($algo);

        $data = base64_decode($data);

        $iv = substr($data, 0, $ivlen);
        $tag = substr($data, $ivlen, $taglen);
        $cipherKey = substr($data, $ivlen + $taglen);

        $res = openssl_decrypt($cipherKey, $algo, base64_decode($key), OPENSSL_RAW_DATA, $iv, $tag);

        if (gettype($res) === "boolean") {
            throw new Exception("Failed to decrypt data");
        }

        return $res;
    }

    private function formatCardData(array $cardInfo): array|bool
    {
        try {
            $algo = $_ENV["ENVELOPE_ENCRYPTION_ALGO"];
            $taglen = (int) $_ENV["TAG_LENGTH"];

            $masterKey = $this->vault->getKV("masterkey");

            $decryptedSecretKey = $this->decrypt($cardInfo["secret_key"], $masterKey, $algo, $taglen);
            $decryptedCardNumber = $this->decrypt($cardInfo["card_number"], $decryptedSecretKey, $algo, $taglen);

            $cardInfo["card_number"] = "**** **** **** " . Functions::array_last(explode(" ", $decryptedCardNumber));
            $cardInfo["expires_at"] = date("m\/y", $cardInfo["expires_at"]);
            unset($cardInfo["cvv"]);

            return $cardInfo;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getById(int $id): array|bool
    {
        try {
            $id = $this->validateId($id);

            $stmt = $this->db->prepare(
                "SELECT * FROM cards WHERE id = :id"
            );

            $stmt->bindParam(":id", $id);
            $stmt->execute();

            return $stmt->fetch();
        } catch (Exception $e) {
            // maybe log it
            var_dump($e->getMessage());
            return false;
        }
    }

    public function getByUserId(int $userId): array|bool
    {
        try {
            $userId = $this->validateId($userId);

            $stmt = $this->db->prepare(
                "SELECT * FROM cards WHERE user_id = :ui"
            );

            $stmt->bindParam(":ui", $userId);
            $stmt->execute();

            $cards = $stmt->fetchAll();
            $formattedCards = [];

            foreach ($cards as $card) {
                $formattedCards[] = $this->formatCardData($card);
            }

            return Functions::array_all($formattedCards, fn($card) => gettype($card) === "array") 
                ? $formattedCards 
                : false;
        } catch (\Throwable $e) {
            // maybe log it
            var_dump($e->getMessage());
            return false;
        }
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
        try {
            $id = $this->validateId($id);

            $stmt = $this->db->prepare(
                "UPDATE cards SET secret_key = :sk WHERE id = :id"
            );

            $stmt->bindParam(":sk", $newSecretKey);
            $stmt->bindParam(":id", $id);

            return $stmt->execute();
        } catch (Exception $e) {
            // maybe log it
            var_dump($e->getMessage());
            return false;
        }
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