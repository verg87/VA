<?php

declare(strict_types=1);

namespace App\Helpers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\Helpers\JWTHelper;
use App\Helpers\JWTLiveTime;

class Headers 
{
    static function getCookies(): array
    {
        $accessTokenLiveTime = JWTLiveTime::AccessToken->value;
        $refreshTokenLiveTime = JWTLiveTime::RefreshToken->value;

        return [
            "Set-Cookie" => [
                "access-token=" . JWTHelper::getJWT($accessTokenLiveTime) . ";HttpOnly" . ";Max-Age=" . $accessTokenLiveTime,
                "refresh-token=" . JWTHelper::getJWT($refreshTokenLiveTime) . ";HttpOnly" . ";Max-Age=" . $refreshTokenLiveTime,
            ]
        ];
    }
}