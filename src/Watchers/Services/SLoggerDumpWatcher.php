<?php

namespace SLoggerLaravel\Watchers\Services;

use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\VarDumper\VarDumper;

class SLoggerDumpWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        VarDumper::setHandler(function (mixed $dump) {
            $this->handleDump($dump);
        });
    }

    public function handleDump(mixed $dump): void
    {
        VarDumper::setHandler(null);

        VarDumper::dump($dump);

        $this->register();

        if ($this->processor->isPaused()) {
            return;
        }

        $this->safeHandleWatching(fn() => $this->onHandleDump($dump));
    }

    protected function onHandleDump(mixed $dump): void
    {
        $data = [
            'dump' => $dump,
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Dump->value,
            data: $data
        );
    }
}
