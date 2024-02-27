<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;

class SLoggerTraceFileDispatcher implements SLoggerTraceDispatcherInterface
{
    /** @var SLoggerTraceObject[] */
    private array $traces = [];

    public function __construct(protected readonly Application $app)
    {
    }

    public function push(SLoggerTraceObject $parameters): void
    {
        $this->traces[] = $parameters;
    }

    public function stop(SLoggerTraceUpdateObject $parameters): void
    {
        if (!$this->traces) {
            return;
        }

        $filtered = array_filter(
            $this->traces,
            fn(SLoggerTraceObject $traceItem) => $traceItem->parentTraceId === $parameters->traceId
                || $traceItem->traceId === $parameters->traceId
        );

        $traces = Arr::sort(
            $filtered,
            fn(SLoggerTraceObject $traceItem) => $traceItem->loggedAt->getTimestampMs()
        );

        /** @var SLoggerTraceObject $parentTrace */
        $parentTrace = Arr::first(
            $traces,
            fn(SLoggerTraceObject $traceItem) => $traceItem->traceId === $parameters->traceId
        );

        if (!is_null($parameters->data)) {
            $parentTrace->data = $parameters->data;
        }


        if (!is_null($parameters->tags)) {
            $parentTrace->tags = $parameters->tags;
        }

        $this->sendTraces($parentTrace, $traces);

        $this->traces = array_filter(
            $this->traces,
            fn(SLoggerTraceObject $traceItem) => $traceItem->parentTraceId !== $parameters
        );
    }

    /**
     * @param SLoggerTraceObject[] $traces
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    protected function sendTraces(SLoggerTraceObject $parentTrace, array $traces): void
    {
        $storage = $this->app['filesystem']->build([
            'driver' => 'local',
            'root'   => storage_path('logs/slogger-traces'),
        ]);

        $traceObjects = new SLoggerTraceObjects();

        foreach ($traces as $trace) {
            $traceObjects->add($trace);
        }

        $storage->put(
            $parentTrace->loggedAt->toDateTimeString('microsecond') . '-' . $parentTrace->type . '.json',
            json_encode(array_values($traces), JSON_PRETTY_PRINT)
        );
    }
}
