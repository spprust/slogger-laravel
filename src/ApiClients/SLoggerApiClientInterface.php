<?php

namespace SLoggerLaravel\ApiClients;

use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;

interface SLoggerApiClientInterface
{
    public function sendTraces(SLoggerTraceObjects $traceObjects): void;

    public function updateTraces(SLoggerTraceUpdateObjects $traceObjects): void;
}
