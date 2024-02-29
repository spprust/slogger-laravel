<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Str;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerDatabaseWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent(QueryExecuted::class, [$this, 'handleQueryExecuted']);
    }

    public function handleQueryExecuted(QueryExecuted $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleQueryExecuted($event));
    }

    protected function onHandleQueryExecuted(QueryExecuted $event): void
    {
        $data = [
            'connection' => $event->connectionName,
            'bindings'   => $event->bindings,
            'sql'        => $event->sql,
            'hash'       => md5($event->sql),
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Database->value,
            status: SLoggerTraceStatusEnum::Success->value,
            tags: [
                $event->connectionName,
                Str::substr($event->sql, 0, 40),
            ],
            data: $data,
            duration: SLoggerTraceHelper::roundDuration($event->time / 1000)
        );
    }
}
