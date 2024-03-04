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
            'bindings'   => $this->maskValue($event->bindings),
            'sql'        => $event->sql,
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

    protected function maskValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return $value;
        }

        if (is_string($value)) {
            $length = Str::length($value);

            if ($length > 5) {
                return Str::mask($value, '*', ceil($length / 3));
            }

            return $value;
        }

        if (is_array($value)) {
            $arrayValue = [];

            foreach ($value as $valueKey => $valueValue) {
                $arrayValue[$valueKey] = $this->maskValue($valueValue);
            }

            return $arrayValue;
        }

        return $value;
    }
}
