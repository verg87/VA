<?php

declare(strict_types=1);

namespace App\Helpers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Dotenv\Dotenv;

use App\Models\RefreshSession;
use App\Helpers\JWTLiveTime;

$dotenv = new Dotenv();

$dotenv->overload(__DIR__ . "\\..\\..\\..\\.dev.env");

class JWTHelper 
{
    private static function getJWT(int $liveTime): array
    {
        $time = time();
        $jti = Uuid::uuid4()->toString();

        return [
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://localhost:5173",
            "jti" => $jti,
            "iat" => $time,
            "nbf" => $time,
            "exp" => $time + $liveTime,
        ];
    }

    static function getAccessJWT(): string
    {
        $payload = static::getJWT(JWTLiveTime::AccessToken->value);

        return JWT::encode($payload, $_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]);
    }

    static function getRefreshJWT(int $userId, string $userAgent, string $ipAddress): string
    {
        $payload = static::getJWT(JWTLiveTime::RefreshToken->value);

        $refreshSession = new RefreshSession();

        $refreshSession->create($userId, $payload["jti"], $userAgent, $ipAddress, $payload["exp"]);

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