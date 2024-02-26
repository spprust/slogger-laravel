<?php

namespace SLoggerLaravel;

use Closure;
use Illuminate\Support\Carbon;
use LogicException;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Helpers\SLoggerMetricsHelper;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;
use SLoggerLaravel\Profiling\AbstractSLoggerProfiling;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use Throwable;

class SLoggerProcessor
{
    private bool $started = false;

    private array $preParentIdsStack = [];

    private bool $paused = false;

    public function __construct(
        private readonly SLoggerTraceDispatcherInterface $traceDispatcher,
        private readonly SLoggerTraceIdContainer $traceIdContainer,
        private readonly AbstractSLoggerProfiling $profiling
    ) {
    }

    public function isActive(): bool
    {
        return $this->started;
    }

    public function isPaused(): bool
    {
        return $this->paused;
    }

    /**
     * @throws Throwable
     */
    public function handleWithoutTracing(Closure $callback): mixed
    {
        $this->paused = true;

        $exception = null;

        try {
            $result = $callback();
        } catch (Throwable $exception) {
            $result = null;
        }

        $this->paused = false;

        if ($exception) {
            throw $exception;
        }

        return $result;
    }

    /**
     * @throws Throwable
     */
    public function handleSeparateTracing(
        Closure $callback,
        string $type,
        array $tags = [],
        array $data = [],
        ?Carbon $loggedAt = null,
        ?string $customParentTraceId = null,
    ): mixed {
        $this->profiling->start();

        $traceId = $this->startAndGetTraceId(
            type: $type,
            tags: $tags,
            data: $data,
            loggedAt: $loggedAt,
            customParentTraceId: $customParentTraceId,
        );

        $startedAt = now();

        $exception = null;

        try {
            $result = $callback();
        } catch (Throwable $exception) {
            $result = null;

            $this->push(
                type: SLoggerTraceTypeEnum::Exception->value,
                data: [
                    'exception' => SLoggerDataFormatter::exception($exception),
                ]
            );
        }

        $this->stop(
            traceId: $traceId,
            data: $data,
            duration: SLoggerTraceHelper::calcDuration($startedAt)
        );

        if ($exception) {
            throw $exception;
        }

        return $result;
    }

    public function startAndGetTraceId(
        string $type,
        array $tags = [],
        array $data = [],
        ?Carbon $loggedAt = null,
        ?string $customParentTraceId = null
    ): string {
        $this->profiling->start();

        $traceId = SLoggerTraceHelper::makeTraceId();

        $parentTraceId = $this->traceIdContainer->getParentTraceId();

        SLoggerTraceHelper::injectCallerToData($data);

        $this->traceDispatcher->push(
            new SLoggerTraceObject(
                traceId: $traceId,
                parentTraceId: $customParentTraceId ?? $parentTraceId,
                type: $type,
                tags: $tags,
                data: $data,
                duration: null,
                memory: SLoggerMetricsHelper::getMemoryUsagePercent(),
                cpu: SLoggerMetricsHelper::getCpuAvgPercent(),
                loggedAt: ($loggedAt ?: now())->clone()->setTimezone('UTC')
            )
        );

        $this->preParentIdsStack[] = $parentTraceId;

        $this->traceIdContainer->setParentTraceId($traceId);

        $this->started = true;

        return $traceId;
    }

    public function push(
        string $type,
        array $tags = [],
        array $data = [],
        ?float $duration = null,
        ?Carbon $loggedAt = null
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $traceId = SLoggerTraceHelper::makeTraceId();

        $parentTraceId = $this->traceIdContainer->getParentTraceId();

        if (!$parentTraceId) {
            throw new LogicException("Parent trace id has not found for $type.");
        }

        SLoggerTraceHelper::injectCallerToData($data);

        $this->traceDispatcher->push(
            new SLoggerTraceObject(
                traceId: $traceId,
                parentTraceId: $parentTraceId,
                type: $type,
                tags: $tags,
                data: $data,
                duration: $duration,
                memory: SLoggerMetricsHelper::getMemoryUsagePercent(),
                cpu: SLoggerMetricsHelper::getCpuAvgPercent(),
                loggedAt: ($loggedAt ?: now())->clone()->setTimezone('UTC')
            )
        );
    }

    public function stop(
        string $traceId,
        ?array $tags = null,
        ?array $data = null,
        ?float $duration = null,
    ): void {
        if (!$this->isActive()) {
            throw new LogicException('Tracing process isn\'t active.');
        }

        $currentParentTraceId = $this->traceIdContainer->getParentTraceId();

        if ($traceId !== $currentParentTraceId) {
            throw new LogicException(
                "Current parent trace id [$currentParentTraceId] isn't same that stopping [$traceId]."
            );
        }

        $preParentTraceId = array_pop($this->preParentIdsStack);

        $this->traceIdContainer->setParentTraceId(
            $preParentTraceId
        );

        if (count($this->preParentIdsStack) == 0) {
            $this->started = false;

            $this->traceIdContainer->setParentTraceId(null);
        }

        $parameters = new SLoggerTraceUpdateObject(
            traceId: $traceId,
            profiling: $this->profiling->stop(),
            tags: $tags,
            data: $data,
            duration: $duration,
            memory: SLoggerMetricsHelper::getMemoryUsagePercent(),
            cpu: SLoggerMetricsHelper::getCpuAvgPercent()
        );

        $this->traceDispatcher->stop($parameters);
    }
}
