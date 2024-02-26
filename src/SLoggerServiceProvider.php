<?php

namespace SLoggerLaravel;

use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\HttpClient\SLoggerHttpClient;
use SLoggerLaravel\Middleware\SLoggerHttpMiddleware;
use SLoggerLaravel\Profiling\AbstractSLoggerProfiling;
use SLoggerLaravel\Profiling\SLoggerXHProfProfiler;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(SLoggerState::class);
        $this->app->singleton(SLoggerProcessor::class);
        $this->app->singleton(SLoggerTraceIdContainer::class);
        $this->app->singleton(SLoggerHttpMiddleware::class);
        $this->app->singleton(
            SLoggerTraceDispatcherInterface::class,
            $this->app['config']['slogger.dispatcher']
        );
        $this->app->singleton(AbstractSLoggerProfiling::class, SLoggerXHProfProfiler::class);

        $this->app->singleton(SLoggerHttpClient::class, function (Application $app) {
            $config = $app['config']['slogger.http_client'];

            $url   = $config['url'];
            $token = $config['token'];

            return new SLoggerHttpClient(
                new Client([
                    'headers'  => [
                        'Authorization'    => "Bearer $token",
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Content-Type'     => 'application/json',
                        'Accept'           => 'application/json',
                    ],
                    'base_uri' => $url,
                ])
            );
        });

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

    private function registerWatchers(): void
    {
        if (!$this->app['config']['slogger.enabled']) {
            return;
        }

        $state = $this->app->make(SLoggerState::class);

        /** @var array[] $watcherConfigs */
        $watcherConfigs = $this->app['config']['slogger.watchers'];

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
