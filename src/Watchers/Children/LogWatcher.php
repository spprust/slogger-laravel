<?php

namespace SLoggerLaravel\Watchers\Children;

use Illuminate\Log\Events\MessageLogged;
use SLoggerLaravel\Enums\TraceStatusEnum;
use SLoggerLaravel\Enums\TraceTypeEnum;
use SLoggerLaravel\Helpers\DataFormatter;
use SLoggerLaravel\Watchers\AbstractWatcher;
use Throwable;

class LogWatcher extends AbstractWatcher
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
            $event->context['exception'] = DataFormatter::exception($exception);
        }

        $data = [
            'level'   => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];

        $this->processor->push(
            type: TraceTypeEnum::Log->value,
            status: TraceStatusEnum::Success->value,
            tags: [
                $event->level,
            ],
            data: $data
        );
    }
}
