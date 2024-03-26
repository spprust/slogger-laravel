<?php

namespace SLoggerLaravel\Jobs;

use GuzzleHttp\Exception\GuzzleException;
use SLoggerLaravel\HttpClient\SLoggerHttpClient;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;

class SLoggerTraceUpdateJob extends AbstractSLoggerTraceJob
{
    public function __construct(
        private readonly string $traceObjectsJson,
    ) {
        parent::__construct();
    }

    /**
     * @throws GuzzleException
     */
    protected function onHandle(SLoggerHttpClient $loggerHttpClient): void
    {
        $loggerHttpClient->updateTraces(
            SLoggerTraceUpdateObjects::fromJson($this->traceObjectsJson)
        );
    }
}
