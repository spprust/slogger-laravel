<?php

namespace SLoggerLaravel\Listeners;

use SLoggerLaravel\Events\WatcherErrorEvent;
use SLoggerLaravel\Processor;
use Throwable;

readonly class WatcherErrorListener
{
    public function __construct(private Processor $processor)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(WatcherErrorEvent $event): void
    {
        $this->processor->handleWithoutTracing(
            fn() => report($event->exception)
        );
    }
}
