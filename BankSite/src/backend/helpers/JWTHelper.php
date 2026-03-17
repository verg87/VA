<?php

declare(strict_types=1);

namespace App\Helpers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Firebase\JWT\JWT;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();

$dotenv->load(__DIR__ . "\\..\\..\\..\\.dev.env");

class JWTHelper 
{
    static function getJWT(): string
    {
        $time = time();

        $payload = [
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://localhost:5173",
            "iat" => $time,
            "nbf" => $time + (60 * 15)
        ];

        return JWT::encode($payload, $_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]);
    }

    static function isValidJWTPayload(array $payload): bool
    {
        $keysDoNotExist = 
            $payload["iss"] ?? false && $payload["aud"] ?? false &&
            $payload["iat"] ?? false && $payload["nbf"] ?? false;

        if (
            $keysDoNotExist ||
            $payload["iss"] !== "http://localhost:8000" || 
            $payload["aud"] !== "http://localhost:5173" ||
            !is_numeric($payload["iat"]) || !is_numeric($payload["nbf"])
        ) {
            return false;
        }

        if (time() >= $payload["nbf"]) {
            return false;
        }

        return true;
    }
}