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
    private static function getJWT(int $userId, int $liveTime): array
    {
        $time = time();
        $jti = Uuid::uuid4()->toString();

        return [
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://localhost:5173",
            "sub" => $userId,
            "jti" => $jti,
            "iat" => $time,
            "nbf" => $time,
            "exp" => $time + $liveTime,
        ];
    }

    static function getAccessJWT(int $userId): string
    {
        $payload = static::getJWT($userId, JWTLiveTime::AccessToken->value);

        return JWT::encode($payload, $_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]);
    }

    static function getRefreshJWT(int $userId, string $userAgent, string $ipAddress): string
    {
        $payload = static::getJWT($userId, JWTLiveTime::RefreshToken->value);

        $refreshSession = new RefreshSession();
        $refreshSession->create($userId, $payload["jti"], $userAgent, $ipAddress, $payload["exp"]);

        return JWT::encode($payload, $_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]);
    }
}