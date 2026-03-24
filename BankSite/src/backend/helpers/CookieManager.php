<?php

declare(strict_types=1);

namespace App\Helpers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\Helpers\JWTHelper;
use App\Helpers\JWTLiveTime;

class CookieManager
{
    public function __construct(
        private int $userId, private string $userAgent, private string $ipAddress
    )
    {
    }

    static function withInfo(int $userId, string $userAgent, string $ipAddress): static
    {
        return new static($userId, $userAgent, $ipAddress);
    }

    public function create(): array
    {
        $accessToken = JWTHelper::getAccessJWT($this->userId);
        $refreshToken = JWTHelper::getRefreshJWT($this->userId, $this->userAgent, $this->ipAddress);
    
        return [
            "Set-Cookie" => [
                "access-token=" . $accessToken . ";HttpOnly" . ";Max-Age=" . JWTLiveTime::AccessToken->value,
                "refresh-token=" . $refreshToken . ";HttpOnly" . ";Max-Age=" . JWTLiveTime::RefreshToken->value,
            ]
        ];
    }
}