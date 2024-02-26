<?php

namespace SLoggerLaravel;

class SLoggerState
{
    /** @var string[] */
    private array $enabledWatcherClasses = [];

    public function addEnabledWatcher(string $watcherClass): void
    {
        $this->enabledWatcherClasses[] = $watcherClass;
    }

    public function isWatcherEnabled(string $watcherClass): bool
    {
        return in_array($watcherClass, $this->enabledWatcherClasses);
    }
}
