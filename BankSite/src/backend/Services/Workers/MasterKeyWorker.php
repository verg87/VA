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

        $algo = $_ENV["ENVELOPE_ENCRYPTION_ALGO"];
        $taglen = (int) $_ENV["TAG_LENGTH"];
        
        $ivlen = openssl_cipher_iv_length($algo);

        $oldMasterKey = $this->vault->getKV("masterkey");
        $newMasterKey = base64_encode(openssl_random_pseudo_bytes(openssl_cipher_key_length($algo)));

        $oldEncryptedSecretKeys = $this->card->getSecretKeys();
        
        $newEncryptedSecretKeys = [];
        
        foreach ($oldEncryptedSecretKeys as $key) {
            $decoded = base64_decode($key["secret_key"]);

            $iv = substr($decoded, 0, $ivlen);
            $tag = substr($decoded, $ivlen, $taglen);
            $cipherKey = substr($decoded, $ivlen + $taglen);

            $decryptedSecretKey = openssl_decrypt($cipherKey, $algo, base64_decode($oldMasterKey), OPENSSL_RAW_DATA, $iv, $tag);
            
            $iv = openssl_random_pseudo_bytes($ivlen);
            $cipherKey = openssl_encrypt($decryptedSecretKey, $algo, base64_decode($newMasterKey), OPENSSL_RAW_DATA, $iv, $tag, "", $taglen);
            $encodedKey = base64_encode($iv . $tag . $cipherKey);

            $newEncryptedSecretKeys[] = ["id" => $key["id"], "key" => $encodedKey];
        }

        if ($this->card->updateSecretKeys($newEncryptedSecretKeys)) {
            if ($this->card->commit() && $this->vault->setKV("masterkey", $newMasterKey)) {
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
