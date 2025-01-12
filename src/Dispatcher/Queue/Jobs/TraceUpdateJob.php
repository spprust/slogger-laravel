<?php

namespace SLoggerLaravel\Dispatcher\Queue\Jobs;

use SLoggerLaravel\Dispatcher\Queue\ApiClients\ApiClientInterface;
use SLoggerLaravel\Objects\TraceUpdateObjects;

class TraceUpdateJob extends AbstractSLoggerTraceJob
{
    public function __construct(
        private readonly string $traceObjectsJson,
    ) {
        parent::__construct();
    }

    protected function onHandle(ApiClientInterface $apiClient): void
    {
        $apiClient->updateTraces(
            TraceUpdateObjects::fromJson($this->traceObjectsJson)
        );
    }
}
