<?php

namespace SLoggerLaravel\Listeners;

use SLoggerLaravel\Events\SLoggerWatcherErrorEvent;

class SLoggerWatcherErrorListener
{
    public function handle(SLoggerWatcherErrorEvent $event): void
    {
        report($event->exception);
    }
}
