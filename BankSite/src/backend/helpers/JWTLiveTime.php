<?php

declare(strict_types=1);

namespace App\Helpers;

enum JWTLiveTime: int
{
    case AccessToken = 60 * 5;
    case RefreshToken = 60 * 60 * 24;
}