<?php

declare(strict_types=1);

namespace App\Vault;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\Helpers\Functions;

class Vault 
{
    private array $unsealKeys = [];
    private string $rootToken = "";
    private bool $isUnsealed = false;
    private string $path = "secret";

    public function __construct(array $config) 
    {
        $vaultConfig = $config["vault"];

        $this->unsealKeys = [
            $vaultConfig["unsealKey1"],
            $vaultConfig["unsealKey2"],
            $vaultConfig["unsealKey3"],
        ];

        $this->rootToken = $vaultConfig["rootToken"];

        $this->unseal();
        $this->enableKV();
        $this->initializeKeys();
    }

    private function initializeKeys(): void
    {
        $masterkey = $this->getKV("masterkey");
        $refkey = $this->getKV("refkey");
        $jwtkey = $this->getKV("jwtkey");

        if (!$masterkey) {
            $algo = $_ENV["ENVELOPE_ENCRYPTION_ALGO"];

            $newMasterKey = base64_encode(
                openssl_random_pseudo_bytes(openssl_cipher_key_length($algo))
            );

            $this->setKV("masterkey", $newMasterKey);
        }

        if (!$refkey) {
            $newRefKey = base64_encode(openssl_random_pseudo_bytes(1024));

            $this->setKV("refkey", $newRefKey);
        }

        if (!$jwtkey) {
            $newJwtKey = base64_encode(openssl_random_pseudo_bytes(1024));

            $this->setKV("jwtkey", $newJwtKey);
        }
    }

    public function enableKV(): array|null
    {
        $ch = curl_init("http://127.0.0.1:8200/v1/sys/mounts/" . $this->path);

        $newMount = json_encode(["type" => "kv", "options" => ["version" => "2"]]);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $newMount);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Vault-Token: " . $this->rootToken]);

        $output = curl_exec($ch);
        $output = gettype($output) === "boolean" ? "" : $output;

        return json_decode($output, true);
    }

    public function unseal(): bool|null
    {
        $results = [];
        $notEnoughKeys = false;

        foreach ($this->unsealKeys as $index => $key) {
            $ch = curl_init("http://127.0.0.1:8200/v1/sys/unseal");

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["key" => $key]));

            $output = curl_exec($ch);
            $output = gettype($output) === "boolean" ? "" : $output;
            $output = json_decode($output, true);

            if (
                $index + 1 === count($this->unsealKeys) && 
                isset($output["sealed"]) && $output["sealed"] === false && 
                isset($output["progress"]) && $output["progress"] === 0
            ) {
                $results[] = true;
            } else {
                $notEnoughKeys = true;
            }

            if (
                !isset($output["errors"]) && isset($output["sealed"]) && 
                isset($output["progress"]) && isset($output["type"]) && 
                isset($output["version"]) && !$notEnoughKeys
            ) {
                $results[] = true;
            }

            $results[] = false;
        }

        if (Functions::array_all($results, fn($succeeded) => $succeeded)) {
            $this->isUnsealed = true;
            return true;
        }

        $this->isUnsealed = false;
        return false;
    }

    public function getKV(string $secretName): string|null
    {
        $ch = curl_init("http://127.0.0.1:8200/v1/" . $this->path . "/data/" . $secretName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Vault-Token: " . $this->rootToken]);

        $output = curl_exec($ch);
        $output = gettype($output) === "boolean" ? "" : $output;

        return json_decode($output, true)["data"]["data"]["key"] ?? null;
    }
    
    public function setKV(string $secretName, string $value): bool
    {     
        $ch = curl_init("http://127.0.0.1:8200/v1/" . $this->path . "/data/" . $secretName);

        $newSecret = json_encode(["data" => ["key" => $value]]);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $newSecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Vault-Token: " . $this->rootToken]);

        $output = curl_exec($ch);
        $output = gettype($output) === "boolean" ? "" : $output;

        $response = json_decode($output, true);

        return $response && !isset($response["errors"]) && isset($response["data"]);
    }
}