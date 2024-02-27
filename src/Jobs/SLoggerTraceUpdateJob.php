<?php

namespace SLoggerLaravel\Jobs;

use SLoggerLaravel\HttpClient\SLoggerHttpClient;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;

class SLoggerTraceUpdateJob extends AbstractSLoggerTraceJob
{
    public function __construct(
        private readonly SLoggerTraceUpdateObjects $traceObjects,
    ) {
        parent::__construct();
    }

    protected function onHandle(SLoggerHttpClient $loggerHttpClient): void
    {
        $loggerHttpClient->updateTraces($this->traceObjects);
    }
}
