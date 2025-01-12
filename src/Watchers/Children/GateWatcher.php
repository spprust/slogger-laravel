<?php

namespace SLoggerLaravel\Watchers\Children;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use SLoggerLaravel\Enums\TraceStatusEnum;
use SLoggerLaravel\Enums\TraceTypeEnum;
use SLoggerLaravel\Helpers\DataFormatter;
use SLoggerLaravel\Watchers\AbstractWatcher;

class GateWatcher extends AbstractWatcher
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
            'user_id'   => $event->user?->getAuthIdentifier(),
            'arguments' => $this->prepareArguments($event->arguments),
        ];

        $this->processor->push(
            type: TraceTypeEnum::Gate->value,
            status: $result === self::ALLOWED
                ? TraceStatusEnum::Success->value
                : TraceStatusEnum::Failed->value,
            tags: [
                $result,
            ],
            data: $data
        );
    }

    protected function prepareResult(mixed $result): string
    {
        if ($result instanceof Response) {
            return $result->allowed() ? self::ALLOWED : self::DENIED;
        }

        return $result ? self::ALLOWED : self::DENIED;
    }

    /**
     * @param array<string|int, mixed> $arguments
     *
     * @return array<string|int, mixed>
     */
    protected function prepareArguments(array $arguments): array
    {
        return Arr::map(
            $arguments,
            function (mixed $argument) {
                return $argument instanceof Model
                    ? DataFormatter::model($argument)
                    : $argument;
            }
        );
    }
}
