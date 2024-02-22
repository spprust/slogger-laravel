<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerJobWatcher extends AbstractSLoggerWatcher
{
    protected array $jobs = [];

    public function register(): void
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return [
                'slogger_uuid'            => Str::uuid()->toString(),
                'slogger_parent_trace_id' => $this->traceIdContainer->getParentTraceId(),
            ];
        });

        $this->listenEvent(JobProcessing::class, [$this, 'handleJobProcessing']);
        $this->listenEvent(JobProcessed::class, [$this, 'handleJobProcessed']);
        $this->listenEvent(JobFailed::class, [$this, 'handleJobFailed']);
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleJobProcessing($event));
    }

    protected function onHandleJobProcessing(JobProcessing $event): void
    {
        $payload = $event->job->payload();

        if (in_array($payload['job'] ?? null, $this->getExceptedJobs())) {
            return;
        }

        $uuid = $payload['slogger_uuid'] ?? null;

        if (!$uuid) {
            return;
        }

        $parentTraceId = $payload['slogger_parent_trace_id'] ?? null;

        if (!$parentTraceId) {
            return;
        }

        if (!$this->processor->isActive()) {
            $this->traceIdContainer->setParentTraceId($parentTraceId);
        }

        $traceId = $this->processor->startAndGetTraceId(
            type: SLoggerTraceTypeEnum::Job->value,
            customParentTraceId: $parentTraceId
        );

        $this->jobs[$uuid] = [
            'trace_id'   => $traceId,
            'started_at' => now(),
        ];
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleJobProcessed($event));
    }

    protected function onHandleJobProcessed(JobProcessed $event): void
    {
        $payload = $event->job->payload();

        $uuid = $payload['slogger_uuid'] ?? null;

        if (!$uuid) {
            return;
        }

        $jobData = $this->jobs[$uuid] ?? null;

        if (!$jobData) {
            return;
        }

        $traceId = $jobData['trace_id'];

        /** @var Carbon $startedAt */
        $startedAt = $jobData['started_at'];

        $data = [
            'connectionName' => $event->connectionName,
            'payload'        => $event->job->payload(),
            'duration'       => SLoggerTraceHelper::calcDuration($startedAt),
            'status'         => 'processed',
        ];

        $this->processor->stop(
            new SLoggerTraceUpdateObject(
                traceId: $traceId,
                tags: [],
                data: $data,
            )
        );

        unset($this->jobs[$uuid]);
    }

    public function handleJobFailed(JobFailed $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleJobFailed($event));
    }

    protected function onHandleJobFailed(JobFailed $event): void
    {
        $payload = $event->job->payload();

        $uuid = $payload['slogger_uuid'] ?? null;

        if (!$uuid) {
            return;
        }

        $jobData = $this->jobs[$uuid] ?? null;

        if (!$jobData) {
            return;
        }

        $traceId = $jobData['trace_id'];

        /** @var Carbon $startedAt */
        $startedAt = $jobData['started_at'];

        $data = [
            'connectionName' => $event->connectionName,
            'payload'        => $event->job->payload(),
            'duration'       => SLoggerTraceHelper::calcDuration($startedAt),
            'status'         => 'failed',
            'exception'      => SLoggerDataFormatter::exception($event->exception),
        ];

        $this->processor->stop(
            new SLoggerTraceUpdateObject(
                traceId: $traceId,
                tags: [],
                data: $data,
            )
        );

        unset($this->jobs[$uuid]);
    }

    protected function getExceptedJobs(): array
    {
        return $this->app['config']['slogger.jobs.excepted'];
    }
}
