<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

/**
 * Not tested
 */
class SLoggerScheduleWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent(ScheduledTaskSkipped::class, [$this, 'handleScheduledTaskSkipped']);
        $this->listenEvent(ScheduledTaskStarting::class, [$this, 'handleScheduledTaskStarting']);
        $this->listenEvent(ScheduledTaskFailed::class, [$this, 'handleScheduledTaskFailed']);
        $this->listenEvent(ScheduledTaskFinished::class, [$this, 'handleScheduledTaskFinished']);
    }

    public function handleScheduledTaskSkipped(ScheduledTaskSkipped $event): void
    {
        $this->pushTask($event->task, 'skipped');
    }

    public function handleScheduledTaskStarting(ScheduledTaskStarting $event): void
    {
        $this->pushTask($event->task, 'starting');
    }

    public function handleScheduledTaskFailed(ScheduledTaskFailed $event): void
    {
        $this->pushTask($event->task, 'failed');
    }

    public function handleScheduledTaskFinished(ScheduledTaskFinished $event): void
    {
        $this->pushTask($event->task, 'finished');
    }

    protected function pushTask(Event $task, string $tag): void
    {
        $this->safeHandleWatching(fn() => $this->onPushTask($task, $tag));
    }

    protected function onPushTask(Event $task, string $tag): void
    {
        $data = [
            'command'     => $task instanceof CallbackEvent ? 'Closure' : $task->command,
            'description' => $task->description,
            'expression'  => $task->expression,
            'timezone'    => $task->timezone,
            'user'        => $task->user,
            'output'      => $this->getTaskOutput($task),
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Schedule->value,
            tags: [
                $tag,
            ],
            data: $data
        );
    }

    protected function getTaskOutput(Event $event): string
    {
        if (!$event->output
            || $event->output === $event->getDefaultOutput()
            || $event->shouldAppendOutput
            || !file_exists($event->output)
        ) {
            return '';
        }

        return trim(file_get_contents($event->output));
    }
}
