<?php

namespace SLoggerLaravel\Dispatcher;

use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;

interface SLoggerTraceDispatcherInterface
{
    public function push(SLoggerTraceObject $parameters): void;

    public function stop(SLoggerTraceUpdateObject $parameters): void;
}
