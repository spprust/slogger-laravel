<?php

namespace SLoggerLaravel\Listeners;

use SLoggerLaravel\Events\SLoggerWatcherErrorEvent;
use SLoggerLaravel\SLoggerProcessor;
use Throwable;

readonly class SLoggerWatcherErrorListener
{
    public function __construct(private SLoggerProcessor $loggerProcessor)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(SLoggerWatcherErrorEvent $event): void
    {
        $this->loggerProcessor->handleWithoutTracing(
            fn() => report($event->exception)
        );
    }
}
