<?php

declare(strict_types=1);

namespace App\Services\Workers;

use App\Models\Card;
use App\Services\Workers\Worker;
use App\Vault\Vault;

class MasterKeyWorker extends Worker
{
    private Vault $vault;
    private Card $card;

    public function __construct(Vault $vault, Card $card)
    {
        $this->vault = $vault;
        $this->card = $card;
        parent::__construct("master_key_rotator", 60 * 60 * 24 * 30 * 3, false, 1);
    }

    public function work(): bool
    {
        $this->log("Starting master key rotation");

        $oldMasterKey = $this->vault->getKV("masterkey");
        $newMasterKey = base64_encode(openssl_random_pseudo_bytes(32));

        $oldEncryptedSecretKeys = $this->card->getSecretKeys();
        $oldDecryptedSecretKeys = [];

        $newEncryptedSecretKeys = [];

        foreach ($oldEncryptedSecretKeys as $key) {
            $decoded = base64_decode($key["secret_key"]);

            $iv = substr($decoded, 0, 12);
            $tag = substr($decoded, 12, 16);
            $cipherKey = substr($decoded, 28);

            $decryptedSecretKey = openssl_decrypt($cipherKey, "aes-256-gcm", base64_decode($oldMasterKey), OPENSSL_RAW_DATA, $iv, $tag);
            $oldDecryptedSecretKeys[] = $decryptedSecretKey;
        }

        foreach ($oldDecryptedSecretKeys as $decryptedKey) {
            $iv = openssl_random_pseudo_bytes(12);
            $cipherKey = openssl_encrypt($decryptedKey, "aes-256-gcm", base64_decode($newMasterKey), OPENSSL_RAW_DATA, $iv, $tag);
            $encodedKey = base64_encode($iv . $tag . $cipherKey);

            $newEncryptedSecretKeys[] = $encodedKey;
        }

        if ($this->card->updateSecretKeys($newEncryptedSecretKeys)) {
            if ($this->vault->setKV("masterkey", "key", $newMasterKey) && $this->card->commit()) {
                $this->log("Master key rotated successfully");
                return true;
            }

            $this->card->rollBack();

            $this->log("Failed to update master key in vault");
            return false;
        }
            
        $this->log("Failed to update secret keys in database");
        return false;
    }
}
