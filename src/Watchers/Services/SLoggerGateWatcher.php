<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerGateWatcher extends AbstractSLoggerWatcher
{
    private const ALLOWED = 'allowed';
    private const DENIED  = 'denied';

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
        $result = $this->prepareResult($event->result);

        $data = [
            'ability'   => $event->ability,
            'result'    => $result,
            'arguments' => $this->prepareArguments($event->arguments),
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Gate->value,
            status: $result === self::ALLOWED
                ? SLoggerTraceStatusEnum::Success->value
                : SLoggerTraceStatusEnum::Failed->value,
            tags: [
                $result,
            ],
            data: $data
        );
    }

    protected function prepareResult($result): string
    {
        if ($result instanceof Response) {
            return $result->allowed() ? self::ALLOWED : self::DENIED;
        }

        return $result ? self::ALLOWED : self::DENIED;
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
