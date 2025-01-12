<?php

namespace SLoggerLaravel\Dispatcher\Queue;

use SLoggerLaravel\Dispatcher\Queue\Jobs\TraceCreateJob;
use SLoggerLaravel\Dispatcher\Queue\Jobs\TraceUpdateJob;
use SLoggerLaravel\Dispatcher\TraceDispatcherInterface;
use SLoggerLaravel\Objects\TraceObject;
use SLoggerLaravel\Objects\TraceObjects;
use SLoggerLaravel\Objects\TraceUpdateObject;
use SLoggerLaravel\Objects\TraceUpdateObjects;

class TraceQueueDispatcher implements TraceDispatcherInterface
{
    /** @var TraceObject[] */
    private array $traces = [];

    private int $maxBatchSize = 5;

    public function push(TraceObject $parameters): void
    {
        $this->traces[] = $parameters;

        if (count($this->traces) < $this->maxBatchSize) {
            return;
        }

        $this->sendAndClearTraces();
    }

    public function stop(TraceUpdateObject $parameters): void
    {
        if (count($this->traces)) {
            $this->sendAndClearTraces();
        }

        $traceObjects = (new TraceUpdateObjects())
            ->add($parameters);

        dispatch(new TraceUpdateJob($traceObjects->toJson()));
    }

    protected function sendAndClearTraces(): void
    {
        $traceObjects = new TraceObjects();

        foreach ($this->traces as $trace) {
            $traceObjects->add($trace);
        }

        dispatch(new TraceCreateJob($traceObjects->toJson()));

        $this->traces = [];
    }
}
