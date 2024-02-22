<?php

namespace SLoggerLaravel\Profiling\Dto;

readonly class SLoggerProfilingDataObject
{
    public function __construct(
        public int $numberOfCalls,
        public float $waitTimeInMs,
        public float $cpuTime,
        public float $memoryUsageInBytes,
        public float $peakMemoryUsageInMb
    ) {
    }
}
