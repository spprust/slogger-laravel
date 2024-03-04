<?php

namespace SLoggerLaravel\Watchers\Services;

use Closure;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use ReflectionFunction;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

/**
 * Not tested on custom events
 */
class SLoggerEventWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent('*', [$this, 'handleEvent']);
    }

    public function handleEvent(string $eventName, array $payload): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleEvent($eventName, $payload));
    }

    protected function onHandleEvent(string $eventName, array $payload): void
    {
        if ($this->shouldIgnore($eventName)) {
            return;
        }

        $data = [
            'name'      => $eventName,
            'listeners' => $this->formatListeners($eventName),
            'broadcast' => class_exists($eventName)
                && in_array(ShouldBroadcast::class, (array) class_implements($eventName)),
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Event->value,
            status: SLoggerTraceStatusEnum::Success->value,
            tags: [
                $eventName,
            ],
            data: $data,
        );
    }

    protected function formatListeners(string $eventName): array
    {
        return collect($this->app['events']->getListeners($eventName))
            ->map(function ($listener) {
                $listener = (new ReflectionFunction($listener))
                    ->getStaticVariables()['listener'];

                if (is_string($listener)) {
                    return Str::contains($listener, '@') ? $listener : $listener . '@handle';
                } elseif (is_array($listener) && is_string($listener[0])) {
                    return $listener[0] . '@' . $listener[1];
                } elseif (is_array($listener) && is_object($listener[0])) {
                    return get_class($listener[0]) . '@' . $listener[1];
                } elseif (is_object($listener) && is_callable($listener) && !$listener instanceof Closure) {
                    return get_class($listener) . '@__invoke';
                }

                return 'closure';
            })
            ->map(function ($listener) {
                if (Str::contains($listener, '@')) {
                    $queued = in_array(ShouldQueue::class, class_implements(Str::beforeLast($listener, '@')));
                }

                return [
                    'name'   => $listener,
                    'queued' => $queued ?? false,
                ];
            })
            ->values()
            ->toArray();
    }

    protected function shouldIgnore(string $eventName): bool
    {
        return $this->eventIsFiredByTheFramework($eventName);
    }

    protected function eventIsFiredByTheFramework($eventName): bool
    {
        return Str::is(
            [
                'Illuminate\*',
                'Laravel\*',
                'eloquent*',
                'bootstrapped*',
                'bootstrapping*',
                'creating*',
                'composing*',
                'SLoggerLaravel\*',
            ],
            $eventName
        );
    }
}
