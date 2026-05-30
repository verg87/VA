<?php

declare(strict_types=1);

namespace App;

use Exception;
use Throwable;

use App\Traits\LoggerTrait;

abstract class Model
{
    use LoggerTrait;

    protected DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
        $this->db->exec("USE " . $this->db->name);
    }

    protected function encrypt(string $data, string $key, string $algo, int $taglen): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algo));
        $cipherText = openssl_encrypt($data, $algo, $key, OPENSSL_RAW_DATA, $iv, $tag, "", $taglen);
        return base64_encode($iv . $tag . $cipherText);
    }

    /**
     * @throws Exception When method failes to decrypt data
     */
    protected function decrypt(string $data, string $key, string $algo, int $taglen): string
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

    protected function tryAndLog(callable $fn): array|bool
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            $this->log($e, "MODEL", "**Model error**", "**Model error**");
            return false;
        }
    }
}