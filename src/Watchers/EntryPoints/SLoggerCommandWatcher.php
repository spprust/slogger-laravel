<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Carbon;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\Console\Input\InputInterface;

class SLoggerCommandWatcher extends AbstractSLoggerWatcher
{
    protected array $commands = [];

    public function register(): void
    {
        $this->listenEvent(CommandStarting::class, [$this, 'handleCommandStarting']);
        $this->listenEvent(CommandFinished::class, [$this, 'handleCommandFinished']);
    }

    public function handleCommandStarting(CommandStarting $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleCommandStarting($event));
    }

    protected function onHandleCommandStarting(CommandStarting $event): void
    {
        if (in_array($event->command, $this->getExceptedCommands())) {
            return;
        }

        $traceId = $this->processor->startAndGetTraceId(
            type: SLoggerTraceTypeEnum::Command->value,
            tags: [
                $this->makeCommandView($event->command, $event->input),
            ]
        );

        $this->commands[] = [
            'trace_id'   => $traceId,
            'started_at' => now(),
        ];
    }

    public function handleCommandFinished(CommandFinished $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleCommandFinished($event));
    }

    protected function onHandleCommandFinished(CommandFinished $event): void
    {
        $commandData = array_pop($this->commands);

        if (!$commandData) {
            return;
        }

        $traceId = $commandData['trace_id'];

        /** @var Carbon $startedAt */
        $startedAt = $commandData['started_at'];

        $data = [
            'command'   => $this->makeCommandView($event->command, $event->input),
            'exitCode'  => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options'   => $event->input->getOptions(),
            'duration'  => SLoggerTraceHelper::calcDuration($startedAt),
        ];

        $this->processor->stop(
            new SLoggerTraceUpdateObject(
                traceId: $traceId,
                data: $data,
            )
        );
    }

    protected function makeCommandView(?string $command, InputInterface $input): string
    {
        return $command ?? $input->getArguments()['command'] ?? 'default';
    }

    protected function getExceptedCommands(): array
    {
        return $this->app['config']['slogger.commands.excepted'];
    }
}
