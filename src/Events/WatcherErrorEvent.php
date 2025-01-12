<?php

namespace SLoggerLaravel\Events;

use Throwable;

class WatcherErrorEvent
{
    public function __construct(public Throwable $exception)
    {
    }
}
