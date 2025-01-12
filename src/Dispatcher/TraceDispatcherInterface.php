<?php

namespace SLoggerLaravel\Dispatcher;

use SLoggerLaravel\Objects\TraceObject;
use SLoggerLaravel\Objects\TraceUpdateObject;

interface TraceDispatcherInterface
{
    public function push(TraceObject $parameters): void;

    public function stop(TraceUpdateObject $parameters): void;
}
