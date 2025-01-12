<?php

namespace SLoggerLaravel;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Queue;
use SLoggerLaravel\Dispatcher\DispatcherFactory;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\ApiClientFactory;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\ApiClientInterface;
use SLoggerLaravel\Dispatcher\TraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\Transporter\Clients\TransporterClient;
use SLoggerLaravel\Dispatcher\Transporter\Clients\TransporterClientInterface;
use SLoggerLaravel\Dispatcher\Transporter\Commands\LoadTransporterCommand;
use SLoggerLaravel\Dispatcher\Transporter\Commands\StartTransporterCommand;
use SLoggerLaravel\Dispatcher\Transporter\Commands\StatTransporterCommand;
use SLoggerLaravel\Dispatcher\Transporter\Commands\StopTransporterCommand;
use SLoggerLaravel\Dispatcher\Transporter\TransporterLoader;
use SLoggerLaravel\Middleware\HttpMiddleware;
use SLoggerLaravel\Profiling\AbstractProfiling;
use SLoggerLaravel\Profiling\XHProfProfiler;
use SLoggerLaravel\Traces\TraceIdContainer;
use SLoggerLaravel\Watchers\AbstractWatcher;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->app->singleton(Config::class);
        $this->app->singleton(State::class);
        $this->app->singleton(Processor::class);
        $this->app->singleton(TraceIdContainer::class);
        $this->app->singleton(HttpMiddleware::class);
        $this->app->singleton(AbstractProfiling::class, XHProfProfiler::class);

        $this->app->singleton(ApiClientInterface::class, static function (Application $app) {
            return $app->make(ApiClientFactory::class)->create(
                config('slogger.dispatchers.queue.api_clients.default')
            );
        });

        $this->app->singleton(TransporterClientInterface::class, function () {
            return new TransporterClient(
                apiToken: config('slogger.token'),
                connectionResolver: static function (): \Illuminate\Contracts\Queue\Queue {
                    return Queue::connection(config('slogger.dispatchers.transporter.queue.connection'));
                },
                queueName: config('slogger.dispatchers.transporter.queue.name')
            );
        });

        $this->app->singleton(TraceDispatcherInterface::class, function (Application $app) {
            return $app->make(DispatcherFactory::class)->create(
                config('slogger.dispatchers.default')
            );
        });

        $this->registerListeners();

        $this->registerWatchers();

        $this->registerConsole();

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

    /**
     * @throws BindingResolutionException
     */
    private function registerWatchers(): void
    {
        if (!$this->app['config']['slogger.enabled']) {
            return;
        }

        $state = $this->app->make(State::class);

        /** @var array{enabled: bool, class: class-string<AbstractWatcher>}[] $watcherConfigs */
        $watcherConfigs = $this->app['config']['slogger.watchers'] ?? [];

        foreach ($watcherConfigs as $watcherConfig) {
            if (!$watcherConfig['enabled']) {
                continue;
            }

            $watcherClass = $watcherConfig['class'];

            $state->addEnabledWatcher($watcherClass);

            /** @var AbstractWatcher $watcher */
            $watcher = $this->app->make($watcherClass);

            $watcher->register();
        }
    }

    private function registerConsole(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            LoadTransporterCommand::class,
            StartTransporterCommand::class,
            StopTransporterCommand::class,
            StatTransporterCommand::class,
        ]);

        $this->app->singleton(TransporterLoader::class, static function () {
            return new TransporterLoader(
                path: base_path('strans')
            );
        });
    }
}
