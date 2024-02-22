<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerCacheWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent(CacheHit::class, [$this, 'handleCacheHit']);
        $this->listenEvent(CacheMissed::class, [$this, 'handleCacheMissed']);

        $this->listenEvent(KeyWritten::class, [$this, 'handleKeyWritten']);
        $this->listenEvent(KeyForgotten::class, [$this, 'handleKeyForgotten']);
    }

    public function handleCacheHit(CacheHit $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleCacheHit($event));
    }

    protected function onHandleCacheHit(CacheHit $event): void
    {
        if ($this->shouldIgnore($event->key)) {
            return;
        }

        $data = [
            'type'  => 'hit',
            'key'   => $event->key,
            'value' => $this->prepareValue($event->key, $event->value),
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Cache->value,
            data: $data
        );
    }

    public function handleCacheMissed(CacheMissed $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleCacheMissed($event));
    }

    protected function onHandleCacheMissed(CacheMissed $event): void
    {
        if ($this->shouldIgnore($event->key)) {
            return;
        }

        $data = [
            'type' => 'missed',
            'key'  => $event->key,
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Cache->value,
            data: $data
        );
    }

    public function handleKeyWritten(KeyWritten $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleKeyWritten($event));
    }

    protected function onHandleKeyWritten(KeyWritten $event): void
    {
        if ($this->shouldIgnore($event->key)) {
            return;
        }

        $data = [
            'type'       => 'set',
            'key'        => $event->key,
            'value'      => $this->prepareValue($event->key, $event->value),
            'expiration' => $event->seconds,
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Cache->value,
            data: $data
        );
    }

    public function handleKeyForgotten(KeyForgotten $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleKeyForgotten($event));
    }

    protected function onHandleKeyForgotten(KeyForgotten $event): void
    {
        if ($this->shouldIgnore($event->key)) {
            return;
        }

        $data = [
            'type' => 'forget',
            'key'  => $event->key,
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Cache->value,
            data: $data
        );
    }

    protected function prepareValue(string $key, mixed $value): mixed
    {
        if ($this->shouldHideValue($key)) {
            return '********';
        }

        if ($value instanceof Model) {
            return SLoggerDataFormatter::model($value);
        }

        if (is_object($value)) {
            return $value::class;
        }

        return $value;
    }

    protected function shouldHideValue(string $key): bool
    {
        return false;
    }

    protected function shouldIgnore(string $key): bool
    {
        return Str::is(
            [
                'illuminate:queue:restart',
                'framework/schedule*',
            ],
            $key
        );
    }
}
