<?php

declare(strict_types=1);

namespace App\Helpers;

enum CardTypes: string
{
    case Debit = "debit";
    case Credit = "credit";
    case Overdraft = "overdraft";
    case Prepaid = "prepaid";
}