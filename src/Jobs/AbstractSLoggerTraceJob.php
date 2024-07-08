<?php

namespace SLoggerLaravel\Jobs;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use SLoggerLaravel\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\SLoggerProcessor;
use Throwable;

abstract class AbstractSLoggerTraceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    abstract protected function onHandle(SLoggerApiClientInterface $loggerHttpClient): void;

    public int $tries = 60;

    public int $timeout = 5;

    public function __construct()
    {
        $this->onConnection(config('slogger.queue.connection'))
            ->onQueue(config('slogger.queue.name'));
    }

    /**
     * @throws GuzzleException
     * @throws Throwable
     */
    public function handle(SLoggerProcessor $loggerProcessor, SLoggerApiClientInterface $loggerHttpClient): void
    {
        $loggerProcessor->handleWithoutTracing(
            function () use ($loggerHttpClient) {
                try {
                    $this->onHandle($loggerHttpClient);
                } catch (Throwable $exception) {
                    Log::channel(config('slogger.log_channel'))
                        ->error($exception->getMessage(), [
                            'code'  => $exception->getCode(),
                            'file'  => $exception->getFile(),
                            'line'  => $exception->getLine(),
                            'trace' => $exception->getTraceAsString(),
                        ]);
                }
            }
        );
    }
}
