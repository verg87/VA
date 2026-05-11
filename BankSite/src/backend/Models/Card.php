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
use PDOException;

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
    private function validateCardCreationArgs(int $userId, string $cardNumber, string $cardType, float $amount, string $expiresAt, string $cvv): array
    {
        $validatedUserId = $this->validateId($userId);

        if (!preg_match("/^\d{4} \d{4} \d{4} \d{4}$/", $cardNumber)) {
            throw new InvalidArgumentException("Invalid card number format");
        }

        if (!in_array($cardType, ["debit", "credit", "overdraft", "prepaid"])) {
            throw new InvalidArgumentException("Invalid card type");
        }

        $validatedAmount = filter_var($amount, FILTER_VALIDATE_FLOAT, [
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
            "userId" => $validatedUserId,
            "cardNumber" => $cardNumber,
            "cardType" => $cardType,
            "amount" => $validatedAmount,
            "expiresAt" => $expiresAtTimestamp,
            "cvv" => $validatedCvv,
        ];
    }

    public function create(int $userId, string $cardNumber, string $cardType, float $amount = 0, string $expiresAt, string $cvv): bool
    {
        try {
            $validatedData = $this->validateCardCreationArgs($userId, $cardNumber, $cardType, $amount, $expiresAt, $cvv);

            $algo = $_ENV['ENVELOPE_ENCRYPTION_ALGO'];
            $taglen = (int) $_ENV["TAG_LENGTH"];

            $dataKey = openssl_random_pseudo_bytes(openssl_cipher_key_length($algo));

            $encryptedCardNumber = $this->encrypt($validatedData['cardNumber'], $dataKey, $algo, $taglen);
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
                ":ui" => $validatedData["userId"],
                ":cn" => $encryptedCardNumber,
                ":sk" => $encryptedDataKey,
                ":ct" => $validatedData["cardType"],
                ":am" => $validatedData["amount"],
                ":ea" => $validatedData["expiresAt"],
                ":cv" => $encryptedCVV
            ]);
        } catch (Exception $e) {
            // maybe log it
            var_dump($e->getMessage());
            return false;
        }
    }

    public function getLatestId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    private function validateTransfer(array $sender, array $receiver, float $amount): bool 
    {
        if ($sender["card_type"] === "debit" && $sender["amount"] - $amount >= 0) {
            return true;
        } 

        // need to write about credit card balance limit
        if ($sender["card_type"] === "credit" && $sender["amount"] - $amount >= -10000) {
            return true;
        }

        return false;
    }

    private function updateAmount(array $card, float $amount): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cards SET amount = :am WHERE id = :id"
        );

        $status = $stmt->execute([
            ":am" => $amount,
            ":id" => $card["id"]
        ]);

        $numOfRowUpdated = $stmt->rowCount();

        return $status && $numOfRowUpdated === 1;
    }

    public function transfer(int $senderCardId, int $receiverCardId, int $amount): bool
    {
        try {
            $senderCard = $this->getById($senderCardId);
            $receiverCard = $this->getById($receiverCardId);

            if (!$this->validateTransfer($senderCard, $receiverCard, $amount)) {
                return false;
            }

            $senderNewAmount = $senderCard["amount"] - $amount;
            $receiverNewAmount = $receiverCard["amount"] + $amount;

            $senderAmountUpdate = $this->updateAmount($senderCard, $senderNewAmount);
            $receiverAmountUpdate = $this->updateAmount($receiverCard, $receiverNewAmount);

            if (!$senderAmountUpdate || !$receiverAmountUpdate) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    private function validateDeposit(array $receiver, float $amount): bool
    {
        if ($receiver["card_type"] === "prepaid") {
            return false;
        }

        $validatedAmount = filter_var($amount, FILTER_VALIDATE_FLOAT, [
            "options" => ["min_range" => 0, "max_range" => 1000000]
        ]);

        return true && $validatedAmount !== false;
    }

    public function deposit(int $receiverCardId, float $amount): bool
    {
        try {
            $receiverCard = $this->getById($receiverCardId);

            if (!$this->validateDeposit($receiverCard, $amount)) {
                return false;
            }

            return $this->updateAmount($receiverCard, $receiverCard["amount"] + $amount);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
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
            unset($cardInfo["secret_key"]);

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

    public function getByIds(array $ids): array|bool
    {
        try {
            if (empty($ids)) {
                return false;
            }

            foreach ($ids as $id) {
                $this->validateId($id);
            }

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("SELECT id, user_id, card_type FROM cards WHERE id IN ($placeholders)");

            foreach ($ids as $index => $id) {
                $stmt->bindValue($index + 1, $id);
            }

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
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

    public function getLatestCardId(): int
    {
        return (int) $this->db->lastInsertId();
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

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function safeTransaction(): bool
    {
        try {
            return $this->db->beginTransaction();
        } catch (PDOException $e) {
            $this->db->rollBack();
        }

        return $this->db->beginTransaction();
    }
}