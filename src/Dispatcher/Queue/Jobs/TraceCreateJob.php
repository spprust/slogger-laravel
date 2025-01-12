<?php

namespace SLoggerLaravel\Dispatcher\Queue\Jobs;

use SLoggerLaravel\Dispatcher\Queue\ApiClients\ApiClientInterface;
use SLoggerLaravel\Objects\TraceObjects;

class TraceCreateJob extends AbstractSLoggerTraceJob
{
    public function __construct(
        private readonly string $traceObjectsJson,
    ) {
        parent::__construct();
    }

    protected function onHandle(ApiClientInterface $apiClient): void
    {
        $apiClient->sendTraces(
            TraceObjects::fromJson($this->traceObjectsJson)
        );
    }
}
