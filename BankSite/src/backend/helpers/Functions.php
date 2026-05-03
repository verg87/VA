<?php

declare(strict_types=1);

namespace App\Helpers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

class Functions
{
    static function array_all(array $array, callable $callable): bool
    {
        foreach ($array as $key => $value) {
            if (! $callable($value, $key))
                return false;
        }
        return true;
    }

    static function array_last(array $array): mixed
    {
        return $array ? $array[array_key_last($array)] : null;
    }
}