<?php

namespace SLoggerLaravel\Dispatcher;

use Exception;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Jobs\SLoggerTraceCreateJob;
use SLoggerLaravel\Jobs\SLoggerTraceUpdateJob;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;

class SLoggerTraceQueueDispatcher implements SLoggerTraceDispatcherInterface
{
    /** @var SLoggerTraceObject[] */
    private array $traces = [];

    private int $maxBatchSize = 5;

    public function push(SLoggerTraceObject $parameters): void
    {
        $this->traces[] = $parameters;

        if (count($this->traces) < $this->maxBatchSize) {
            return;
        }

        $this->sendAndClearTraces();
    }

    public function stop(SLoggerTraceUpdateObject $parameters): void
    {
        if (count($this->traces)) {
            $this->sendAndClearTraces();
        }

        $parameters->data['stack_trace'] = SLoggerDataFormatter::stackTrace(
            (new Exception())->getTrace()
        );

        $traceObjects = (new SLoggerTraceUpdateObjects())
            ->add($parameters);

        dispatch(new SLoggerTraceUpdateJob($traceObjects));
    }

    protected function sendAndClearTraces(): void
    {
        $traceObjects = new SLoggerTraceObjects();

        foreach ($this->traces as $trace) {
            $traceObjects->add($trace);
        }

        dispatch(new SLoggerTraceCreateJob($traceObjects));

        $this->traces = [];
    }
}
