<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Str;

class ArrayHelper
{
    /**
     * @param array<string, mixed> $array
     */
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
