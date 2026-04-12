<?php

declare(strict_types=1);

namespace App\Helpers;

enum CardTypes
{
    case Debit;
    case Credit;
    case Overdraft;
    case Prepaid;
}