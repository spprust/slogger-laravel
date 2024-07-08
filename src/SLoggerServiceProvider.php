<?php

namespace SLoggerLaravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use SLoggerLaravel\ApiClients\SLoggerApiClientFactory;
use SLoggerLaravel\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Middleware\SLoggerHttpMiddleware;
use SLoggerLaravel\Profiling\AbstractSLoggerProfiling;
use SLoggerLaravel\Profiling\SLoggerXHProfProfiler;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(SLoggerConfig::class);
        $this->app->singleton(SLoggerState::class);
        $this->app->singleton(SLoggerProcessor::class);
        $this->app->singleton(SLoggerTraceIdContainer::class);
        $this->app->singleton(SLoggerHttpMiddleware::class);
        $this->app->singleton(
            SLoggerTraceDispatcherInterface::class,
            $this->app['config']['slogger.dispatcher']
        );
        $this->app->singleton(AbstractSLoggerProfiling::class, SLoggerXHProfProfiler::class);

        $this->app->singleton(SLoggerApiClientInterface::class, function (Application $app) {
            return $app->make(SLoggerApiClientFactory::class)->create(
                $app['config']['slogger.api_clients.default']
            );
        });

        $this->registerListeners();

        $this->registerWatchers();

        $this->publishes(
            paths: [
                __DIR__ . '/../config/slogger.php' => config_path('slogger.php'),
            ],
            groups: [
                'slogger-laravel',
            ]
        );
    }

    private function registerListeners(): void
    {
        $events = $this->app['events'];

        foreach ($this->app['config']['slogger.listeners'] ?? [] as $eventClass => $listenerClasses) {
            foreach ($listenerClasses as $listenerClass) {
                $events->listen($eventClass, $listenerClass);
            }
        }
    }

    private function registerWatchers(): void
    {
        if (!$this->app['config']['slogger.enabled']) {
            return;
        }

        $state = $this->app->make(SLoggerState::class);

        /** @var array[] $watcherConfigs */
        $watcherConfigs = $this->app['config']['slogger.watchers'] ?? [];

        foreach ($watcherConfigs as $watcherConfig) {
            if (!$watcherConfig['enabled']) {
                continue;
            }

            $watcherClass = $watcherConfig['class'];

            $state->addEnabledWatcher($watcherClass);

            /** @var AbstractSLoggerWatcher $watcher */
            $watcher = $this->app->make($watcherClass);

            $watcher->register();
        }
    }
}
