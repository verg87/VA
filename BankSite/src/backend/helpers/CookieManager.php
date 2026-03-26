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

    public function create(bool $reset = false): array
    {
        $accessToken = JWTHelper::getAccessJWT($this->userId);
        $refreshToken = JWTHelper::getRefreshJWT($this->userId, $this->userAgent, $this->ipAddress);

        $att = JWTLiveTime::AccessToken->value;
        $rtt = JWTLiveTime::RefreshToken->value;
        var_dump($reset ? $att - ($att * 2) : $att);
        var_dump($reset ? $rtt - ($rtt * 2) : $rtt);
    
        return [
            "Set-Cookie" => [
                "access-token=" . $accessToken . ";HttpOnly" . ";Max-Age=" . $reset ? $att - ($att * 2) : $att . ";SameSite=Strict",
                "refresh-token=" . $refreshToken . ";HttpOnly" . ";Max-Age=" . $reset ? $rtt - ($rtt * 2) : $rtt . ";SameSite=Strict",
            ]
        ];
    }
}