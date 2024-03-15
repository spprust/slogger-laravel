<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SLoggerMaskHelper
{
    public static function maskArrayByList(array $data, array $patterns): array
    {
        foreach ($patterns as $key) {
            $realKey = SLoggerArrayHelper::findKeyInsensitive($data, $key);

            if (!$realKey) {
                continue;
            }

            $data[$realKey] = self::maskValue($data[$realKey]);
        }

        return $data;
    }

    public static function maskArrayByPatterns(array $data, array $patterns): array
    {
        $result = [];

        $data = Arr::dot($data);

        foreach ($data as $key => $value) {
            if (Str::is($patterns, $key)) {
                $value = self::maskValue($value);
            }

            Arr::set($result, $key, $value);
        }

        return $result;
    }

    private static function maskValue(mixed $value): mixed
    {
        if (!$value) {
            return $value;
        }

        if (!is_string($value) && !is_numeric($value)) {
            $value = '********';
        } else {
            if (strlen($value) === 1) {
                $value = '*';
            } else {
                $batchLength = (int) ceil(Str::length($value) / 4);

                $value = Str::mask(
                    string: (string) $value,
                    character: '*',
                    index: $batchLength,
                    length: ($batchLength * 2) ?: 1
                );
            }
        }

        return $value;
    }
}
