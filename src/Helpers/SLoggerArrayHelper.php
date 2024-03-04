<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Str;

class SLoggerArrayHelper
{
    public static function findKeyInsensitive(array $array, string $key): ?string
    {
        foreach (array_keys($array) as $aKey) {
            if (Str::lower($aKey) === Str::lower($key)) {
                return $aKey;
            }
        }

        return null;
    }
}
