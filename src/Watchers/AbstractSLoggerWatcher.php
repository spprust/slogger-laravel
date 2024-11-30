<?php

namespace SLoggerLaravel\Watchers;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Events\SLoggerWatcherErrorEvent;
use SLoggerLaravel\SLoggerConfig;
use SLoggerLaravel\SLoggerProcessor;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use Throwable;

abstract class AbstractSLoggerWatcher
{
    private Dispatcher $events;

    abstract public function register(): void;

    public function __construct(
        protected readonly Application $app,
        protected readonly SLoggerTraceDispatcherInterface $traceDispatcher,
        protected readonly SLoggerProcessor $processor,
        protected readonly SLoggerTraceIdContainer $traceIdContainer,
        protected readonly SLoggerConfig $loggerConfig,
    ) {
        $this->events = $this->app['events'];

        $this->init();
    }

    protected function init(): void
    {
    }

    protected function listenEvent(string $eventClass, array $function): void
    {
        $this->events->listen($eventClass, $function);
    }

    protected function safeHandleWatching(Closure $callback): mixed
    {
        if ($this->processor->isPaused()) {
            return null;
        }

        try {
            return $callback();
        } catch (Throwable $exception) {
            $this->processor->handleWithoutTracing(function () use ($exception) {
                $this->app['events']->dispatch(new SLoggerWatcherErrorEvent($exception));
            });
        }

        return null;
    }
}
