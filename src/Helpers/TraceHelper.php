<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TraceHelper
{
    public static function makeTraceId(): string
    {
        return Str::slug(config('app.name')) . '-' . Str::uuid()->toString();
    }

    public static function calcDuration(Carbon $startedAt): float
    {
        return self::roundDuration(
            $startedAt->clone()->setTimezone('UTC')
                ->diffInMicroseconds(now()->setTimezone('UTC')) * 0.000001
        );
    }

    public static function roundDuration(float $duration): float
    {
        return round($duration, 6);
    }
}
