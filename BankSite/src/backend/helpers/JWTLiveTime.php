<?php

declare(strict_types=1);

namespace App\Helpers;

enum JWTLiveTime: int
{
    case AccessToken = 60 * 15;
    case RefreshToken = 60 * 60 * 24 * 3;
}