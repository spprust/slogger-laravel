<?php

namespace SLoggerLaravel\Watchers\Children;

use SLoggerLaravel\Enums\TraceStatusEnum;
use SLoggerLaravel\Enums\TraceTypeEnum;
use SLoggerLaravel\Watchers\AbstractWatcher;
use Symfony\Component\VarDumper\VarDumper;

class DumpWatcher extends AbstractWatcher
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
            'dump' => is_object($dump) ? (print_r($dump, true)) : $dump,
        ];

        $this->processor->push(
            type: TraceTypeEnum::Dump->value,
            status: TraceStatusEnum::Success->value,
            data: $data
        );
    }
}
