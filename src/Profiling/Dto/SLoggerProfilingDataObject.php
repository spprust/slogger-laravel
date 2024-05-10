<?php

namespace SLoggerLaravel\Profiling\Dto;

readonly class SLoggerProfilingDataObject
{
    public function __construct(
        public int $numberOfCalls,
        public float $waitTimeInUs,
        public float $cpuTime,
        public float $memoryUsageInBytes,
        public float $peakMemoryUsageInBytes
    ) {
    }
}
