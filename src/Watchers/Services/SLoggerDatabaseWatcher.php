<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Database\Events\QueryExecuted;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Throwable;

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
        $caller = SLoggerTraceHelper::getCallerFromStackTrace();

        $data = [
            'connection' => $event->connectionName,
            'bindings'   => $event->bindings,
            'sql'        => $event->sql,
            'file'       => $caller['file'] ?? '?',
            'line'       => $caller['line'] ?? '?',
            'hash'       => md5($event->sql),
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Database->value,
            tags: [
                $event->connectionName,
            ],
            data: $data,
            duration: SLoggerTraceHelper::roundDuration($event->time / 1000)
        );
    }
}
