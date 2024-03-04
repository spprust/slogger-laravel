<?php

namespace SLoggerLaravel\Profiling;

use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;
use SLoggerLaravel\SLoggerConfig;

abstract class AbstractSLoggerProfiling
{
    private bool $profilingEnabled;
    private bool $profilingStarted = false;

    abstract protected function onStart(): bool;

    abstract protected function onStop(): ?SLoggerProfilingObjects;

    public function __construct(
        private readonly SLoggerConfig $loggerConfig
    )
    {
        $this->profilingEnabled = $this->loggerConfig->profilingEnabled();
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
