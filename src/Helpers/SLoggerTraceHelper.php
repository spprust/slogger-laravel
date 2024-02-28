<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SLoggerTraceHelper
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

    public static function injectCallerToData(array &$data): void
    {
        $caller = self::getCallerFromStackTrace();

        if ($caller['file'] ?? null) {
            $basePathLen = strlen(base_path());

            $caller['file'] = Str::substr($caller['file'], $basePathLen);
        }

        $data['__caller'] = [
            'file' => $caller['file'] ?? '?',
            'line' => $caller['line'] ?? '?',
        ];
    }

    private static function getCallerFromStackTrace(): array
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))->forget([0]);

        $basePathVendor   = base_path('vendor' . DIRECTORY_SEPARATOR);
        $basePathPackages = base_path('packages' . DIRECTORY_SEPARATOR);

        return $trace->first(
            function ($frame) use ($basePathVendor, $basePathPackages) {
                if (!isset($frame['file']) || !isset($frame['line'])) {
                    return false;
                }

                return !Str::startsWith($frame['file'], $basePathVendor)
                    && !Str::startsWith($frame['file'], $basePathPackages);
            }
        ) ?? [];
    }
}
