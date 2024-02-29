<?php

namespace SLoggerLaravel\Objects;

use Illuminate\Support\Carbon;

class SLoggerTraceObject
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public array $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt
    ) {
    }
}
