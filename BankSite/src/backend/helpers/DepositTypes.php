<?php

declare(strict_types=1);

namespace App\Helpers;

enum DepositTypes: string
{
    case Transfer = "transfer";
    case Cash = "cash";
    case Check = "check";
}