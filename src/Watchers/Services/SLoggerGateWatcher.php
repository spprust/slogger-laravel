<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

/**
 * Not tested
 */
class SLoggerGateWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent(GateEvaluated::class, [$this, 'handleGateEvaluated']);
    }

    public function handleGateEvaluated(GateEvaluated $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleGateEvaluated($event));
    }

    protected function onHandleGateEvaluated(GateEvaluated $event): void
    {
        $caller = SLoggerTraceHelper::getCallerFromStackTrace([0, 1]);

        $data = [
            'ability'   => $event->ability,
            'result'    => $this->prepareResult($event->result),
            'arguments' => $this->prepareArguments($event->arguments),
            'file'      => $caller['file'] ?? null,
            'line'      => $caller['line'] ?? null,
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Gate->value,
            data: $data
        );
    }

    protected function prepareResult($result): string
    {
        if ($result instanceof Response) {
            return $result->allowed() ? 'allowed' : 'denied';
        }

        return $result ? 'allowed' : 'denied';
    }

    protected function prepareArguments($arguments): array
    {
        return collect($arguments)
            ->map(function ($argument) {
                return $argument instanceof Model
                    ? SLoggerDataFormatter::model($argument)
                    : $argument;
            })
            ->toArray();
    }
}
