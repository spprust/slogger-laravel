<?php

namespace SLoggerLaravel\Dispatcher\Queue\ApiClients;

use SLoggerLaravel\Objects\TraceObjects;
use SLoggerLaravel\Objects\TraceUpdateObjects;

interface ApiClientInterface
{
    public function sendTraces(TraceObjects $traceObjects): void;

    public function updateTraces(TraceUpdateObjects $traceObjects): void;
}
