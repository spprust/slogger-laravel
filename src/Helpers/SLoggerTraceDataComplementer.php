<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Str;

class SLoggerTraceDataComplementer
{
    private string $basePathVendor;
    private string $basePathPackages;

    public function __construct()
    {
        $this->basePathVendor   = base_path('vendor' . DIRECTORY_SEPARATOR);
        $this->basePathPackages = base_path('packages' . DIRECTORY_SEPARATOR);
    }

    public function inject(array &$data): void
    {
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $trace = [];

        foreach ($backTrace as $frame) {
            if (!isset($frame['file']) || !isset($frame['line'])) {
                continue;
            }

            $class = $frame['class'] ?? null;

            if ($class && ($class === self::class || $class === static::class)) {
                continue;
            }

            if (Str::startsWith($frame['file'], $this->basePathVendor)
                || Str::startsWith($frame['file'], $this->basePathPackages)
            ) {
                continue;
            }

            $trace[] = [
                'file' => $frame['file'],
                'line' => $frame['line'],
            ];
        }

        $data['__trace'] = $trace;
    }
}
