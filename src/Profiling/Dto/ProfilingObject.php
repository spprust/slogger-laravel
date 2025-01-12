<?php

namespace SLoggerLaravel\Profiling\Dto;

readonly class ProfilingObject
{
    public function __construct(
        public string $raw,
        public string $calling,
        public string $callable,
        public ProfilingDataObject $data
    ) {
    }
}
