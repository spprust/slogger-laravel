<?php

namespace SLoggerLaravel\Jobs;

use SLoggerLaravel\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\Objects\SLoggerTraceObjects;

class SLoggerTraceCreateJob extends AbstractSLoggerTraceJob
{
    public function __construct(
        private readonly string $traceObjectsJson,
    ) {
        parent::__construct();
    }

    protected function onHandle(SLoggerApiClientInterface $loggerHttpClient): void
    {
        $loggerHttpClient->sendTraces(
            SLoggerTraceObjects::fromJson($this->traceObjectsJson)
        );
    }
}
