<?php

declare(strict_types=1);

namespace App\Models;

use Respect\Validation\ValidatorBuilder as v;

use App\DB;
use App\Model;
use App\Vault\Vault;
use App\Helpers\Functions;
use App\Helpers\CardTypes;

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
    private function validate(int $userId, string $cardNumber, string $cardType, float $amount, string $expiresAt, string $cvv): void
    {
        v::intType()->positive()->assert($userId);
        
        if ($cardType === CardTypes::Credit->value) {
            v::creditCard()->assert($cardNumber);
        }

        if (!preg_match("/^\d{4} \d{4} \d{4} \d{4}$/", $cardNumber)) {
            throw new InvalidArgumentException("Invalid card number format");
        }

        v::alpha()->containsAny(["credit", "debit", "prepaid", "overdraft"])->assert($cardType);
        v::floatType()->between(0, 1000000)->assert($amount);
        v::stringType()->length(v::equals(3))->intVal()->assert($cvv);
        v::date("m/y")->assert($expiresAt);
    }

    private function prepareExpirationTime(string $expiresAt): int
    {
        $expiresAtDate = DateTimeImmutable::createFromFormat('m/y', $expiresAt);
        if ($expiresAtDate === false) {
            throw new InvalidArgumentException('Invalid expiration date format. Use "mm/yy"');
        }
        return $expiresAtDate->modify('last day of this month')->getTimestamp();
    }

    public function create(int $userId, string $cardNumber, string $cardType, float $amount = 0, string $expiresAt, string $cvv): bool
    {
        $fn = function() use($userId, $cardNumber, $cardType, $amount, $expiresAt, $cvv) {
            $this->validate($userId, $cardNumber, $cardType, $amount, $expiresAt, $cvv);
            $expiresAt = $this->prepareExpirationTime($expiresAt);

            $algo = $_ENV['ENVELOPE_ENCRYPTION_ALGO'];
            $taglen = (int) $_ENV["TAG_LENGTH"];

            $dataKey = openssl_random_pseudo_bytes(openssl_cipher_key_length($algo));

            $encryptedCardNumber = $this->encrypt($cardNumber, $dataKey, $algo, $taglen);
            $encryptedCVV = $this->encrypt($cvv, $dataKey, $algo, $taglen);

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
                ":ui" => $userId,
                ":cn" => $encryptedCardNumber,
                ":sk" => $encryptedDataKey,
                ":ct" => $cardType,
                ":am" => $amount,
                ":ea" => $expiresAt,
                ":cv" => $encryptedCVV
            ]);
        };

        return $this->tryAndLog($fn);
    }

    public function getLatestId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    private function validateTransfer(array $sender, array $receiver, float $amount): void 
    {
        $data = ["type" => $sender["card_type"], "toTransfer" => $amount, "left" => $sender["amount"] - $amount];

        $debitRule = v::allOf(
            v::key("type", v::equals("debit")),
            v::key("left", v::floatType()->greaterThanOrEqual(0)),
            v::key("toTransfer", v::floatType()->positive()->lessThanOrEqual(1000000))
        );

        $creditRule = v::allOf(
            v::key("type", v::equals("credit")),
            v::key("left", v::floatType()->greaterThanOrEqual(-10000)),
            v::key("toTransfer", v::floatType()->positive()->lessThanOrEqual(1000000))
        );

        $etcRule = v::allOf(
            v::key("type", v::stringType()->notEquals("prepaid")),
            v::key("left", v::floatType()->greaterThanOrEqual(0)),
            v::key("toTransfer", v::floatType()->positive()->lessThanOrEqual(1000000))
        );

        v::anyOf($etcRule, $debitRule, $creditRule)->assert($data);
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

    public function transfer(int $senderCardId, int $receiverCardId, float $amount): bool
    {
        $fn = function() use($senderCardId, $receiverCardId, $amount) {
            $senderCard = $this->getById($senderCardId);
            $receiverCard = $this->getById($receiverCardId);

            $this->validateTransfer($senderCard, $receiverCard, $amount);

            $senderNewAmount = $senderCard["amount"] - $amount;
            $receiverNewAmount = $receiverCard["amount"] + $amount;

            $senderAmountUpdate = $this->updateAmount($senderCard, $senderNewAmount);
            $receiverAmountUpdate = $this->updateAmount($receiverCard, $receiverNewAmount);

            if (!$senderAmountUpdate || !$receiverAmountUpdate) {
                return false;
            }

            return true;
        };
        
        return $this->tryAndLog($fn);
    }

    private function validateDeposit(array $receiver, float $amount): void
    {
        v::stringType()->notEquals("prepaid")->assert($receiver["card_type"]);
        v::floatType()->positive()->lessThanOrEqual(1000000)->assert($amount);
    }

    public function deposit(int $receiverCardId, float $amount): bool
    {
        $fn = function() use($receiverCardId, $amount) {
            $receiverCard = $this->getById($receiverCardId);

            $this->validateDeposit($receiverCard, $amount);

            return $this->updateAmount($receiverCard, $receiverCard["amount"] + $amount);
        };
        
        return $this->tryAndLog($fn);
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
        $fn = function() use($id) {
            v::intType()->positive()->assert($id);

            $stmt = $this->db->prepare(
                "SELECT * FROM cards WHERE id = :id"
            );

            $stmt->bindParam(":id", $id);
            $stmt->execute();

            return $stmt->fetch();
        };

        return $this->tryAndLog($fn);
    }

    public function getByIds(array $ids): array|bool
    {
        $fn = function() use($ids) {
            v::notBlank()->allIntType()->allPositive()->assert($ids);

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("SELECT id, user_id, card_type FROM cards WHERE id IN ($placeholders)");

            foreach ($ids as $index => $id) {
                $stmt->bindValue($index + 1, $id);
            }

            $stmt->execute();
            return $stmt->fetchAll();
        };

        return $this->tryAndLog($fn);
    }

    public function getByUserId(int $userId): array|bool
    {
        $fn = function() use($userId) {
            v::intType()->positive()->assert($userId);

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
        };

        return $this->tryAndLog($fn);
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
        $fn = function() use ($id, $newSecretKey) {
            v::intType()->positive()->assert($id);

            $stmt = $this->db->prepare(
                "UPDATE cards SET secret_key = :sk WHERE id = :id"
            );

            $stmt->bindParam(":sk", $newSecretKey);
            $stmt->bindParam(":id", $id);

            return $stmt->execute();
        };

        return $this->tryAndLog($fn);
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