<?php

declare(strict_types=1);

namespace App\Helpers;

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

    static function array_first(array $array): mixed 
    {
        return $array ? $array[array_key_first($array)] : null;
    }
}