<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Carbon;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\Console\Input\InputInterface;

class SLoggerCommandWatcher extends AbstractSLoggerWatcher
{
    protected array $commands = [];
    protected array $exceptedCommands = [];

    protected function init(): void
    {
        $this->exceptedCommands = $this->loggerConfig->commandsExcepted();
    }

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
        if (in_array($event->command, $this->exceptedCommands)) {
            return;
        }

        $data = [
            'command'   => $this->makeCommandView($event->command, $event->input),
            'arguments' => $event->input->getArguments(),
            'options'   => $event->input->getOptions(),
        ];

        $traceId = $this->processor->startAndGetTraceId(
            type: SLoggerTraceTypeEnum::Command->value,
            tags: [
                $this->makeCommandView($event->command, $event->input),
            ],
            data: $data
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
            'exit_code' => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options'   => $event->input->getOptions(),
        ];

        $this->processor->stop(
            traceId: $traceId,
            status: $event->exitCode
                ? SLoggerTraceStatusEnum::Failed->value
                : SLoggerTraceStatusEnum::Success->value,
            data: $data,
            duration: SLoggerTraceHelper::calcDuration($startedAt)
        );
    }

    protected function makeCommandView(?string $command, InputInterface $input): string
    {
        return $command ?? $input->getArguments()['command'] ?? 'default';
    }
}
