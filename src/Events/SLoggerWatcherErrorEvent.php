<?php

namespace SLoggerLaravel\Events;

use Throwable;

class SLoggerWatcherErrorEvent
{
    public function __construct(public Throwable $exception)
    {
    }
}
