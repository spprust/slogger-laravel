<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Log\Events\MessageLogged;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Throwable;

class SLoggerLogWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent(MessageLogged::class, [$this, 'handleMessageLogged']);
    }

    public function handleMessageLogged(MessageLogged $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleMessageLogged($event));
    }

    protected function onHandleMessageLogged(MessageLogged $event): void
    {
        $exception = $event->context['exception'] ?? null;

        if ($exception instanceof Throwable) {
            $event->context['exception'] = SLoggerDataFormatter::exception($exception);
        }

        $data = [
            'level'   => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Log->value,
            status: SLoggerTraceStatusEnum::Success->value,
            tags: [
                $event->level,
            ],
            data: $data
        );
    }
}
