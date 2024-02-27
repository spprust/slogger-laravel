<?php

namespace SLoggerLaravel\Jobs;

use SLoggerLaravel\HttpClient\SLoggerHttpClient;
use SLoggerLaravel\Objects\SLoggerTraceObjects;

class SLoggerTraceCreateJob extends AbstractSLoggerTraceJob
{
    public function __construct(
        private readonly SLoggerTraceObjects $traceObjects,
    ) {
        parent::__construct();
    }

    protected function onHandle(SLoggerHttpClient $loggerHttpClient): void
    {
        $loggerHttpClient->sendTraces($this->traceObjects);
    }
}
