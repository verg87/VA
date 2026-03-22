<?php

declare(strict_types=1);

namespace App\Helpers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();

$dotenv->overload(__DIR__ . "\\..\\..\\..\\.dev.env");

class JWTHelper 
{
    static function getJWT(int $liveTime): string
    {
        $time = time();
        $jti = Uuid::uuid4()->toString();

        $payload = [
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://localhost:5173",
            "jti" => $jti,
            "iat" => $time,
            "nbf" => $time,
            "exp" => $time + $liveTime,
        ];

        return JWT::encode($payload, $_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]);
    }

    static function isValidJWTPayload(array $payload): bool
    {
        $keysDoExist = (bool)
            $payload["iss"] ?? false && $payload["aud"] ?? false &&
            $payload["iat"] ?? false && $payload["nbf"] ?? false && 
            $payload["exp"] ?? false;

        if (
            !$keysDoExist ||
            $payload["iss"] !== "http://127.0.0.1:8000" || 
            $payload["aud"] !== "http://localhost:5173" ||
            !is_numeric($payload["iat"]) || !is_numeric($payload["nbf"]) ||
            !is_numeric($payload["exp"])
        ) {
            return false;
        }

        if (time() >= $payload["exp"]) {
            return false;
        }

        return true;
    }
}