<?php

declare(strict_types=1);

namespace App\Vault;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\Helpers\Functions;

class Vault 
{
    protected bool $isUnsealed = false;

    public function __construct(array $unsealKeys) 
    {
        $this->unseal($unsealKeys);
    }

    public function unseal(array $unsealKeys): bool 
    {
        $results = [];
        $notEnoughKeys = false;

        foreach ($unsealKeys as $key => $index) {
            $ch = curl_init("http://127.0.0.1:8200/v1/sys/unseal");

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["key" => $key]));

            $output = curl_exec($ch);
            $output = (array) json_decode($output);

            if (
                $index + 1 === count($unsealKeys) && 
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

    public function get(string $secretName, string $rootToken): array
    {
        if (!$this->isUnsealed) {
            return [];
        }
        
        $ch = curl_init("http://127.0.0.1:8200/v1/secret/data/" . $secretName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Vault-Token: " . $rootToken]);

        $output = curl_exec($ch);
        $output = json_decode($output);

        return (array) $output;
    }
}