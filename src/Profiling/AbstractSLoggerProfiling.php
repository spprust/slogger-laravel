<?php

namespace SLoggerLaravel\Profiling;

use Illuminate\Contracts\Foundation\Application;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;

abstract class AbstractSLoggerProfiling
{
    private bool $profilingEnabled;
    private bool $profilingStarted = false;

    abstract protected function onStart(): bool;

    abstract protected function onStop(): ?SLoggerProfilingObjects;

    public function __construct(private readonly Application $app)
    {
        $this->profilingEnabled = $this->app['config']['slogger.profiling.enabled'];
    }

    public function start(): void
    {
        if (!$this->profilingEnabled) {
            return;
        }

        $this->profilingStarted = $this->onStart();
    }

    public function stop(): ?SLoggerProfilingObjects
    {
        if (!$this->profilingStarted || !$this->profilingEnabled) {
            return null;
        }

        $profilingObjects = $this->onStop();

        $filteredProfilingObjects = new SLoggerProfilingObjects();

        foreach ($profilingObjects->getItems() as $profilingObject) {
            $filteredProfilingObjects->add($profilingObject);
        }

        $this->profilingStarted = false;

        return $filteredProfilingObjects;
    }
}
