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

    public int $backoff = 3;

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
        try {
            $loggerProcessor->handleWithoutTracing(
                function () use ($loggerHttpClient) {
                    $this->onHandle($loggerHttpClient);
                }
            );
        } catch (Throwable $exception) {
            if ($this->job->attempts() < $this->tries) {
                $this->job->release($this->backoff);
            } else {
                $this->job->delete();

                Log::channel(config('slogger.log_channel'))
                    ->error($exception->getMessage(), [
                        'code'  => $exception->getCode(),
                        'file'  => $exception->getFile(),
                        'line'  => $exception->getLine(),
                        'trace' => $exception->getTraceAsString(),
                    ]);
            }
        }
    }
}
