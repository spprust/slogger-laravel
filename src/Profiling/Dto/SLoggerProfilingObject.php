<?php

namespace SLoggerLaravel\Profiling\Dto;

readonly class SLoggerProfilingObject
{
    public function __construct(
        public string $raw,
        public string $calling,
        public string $callable,
        public SLoggerProfilingDataObject $data
    ) {
    }
}
