<?php

namespace SLoggerLaravel\ApiClients;

use Exception;
use Grpc\ChannelCredentials;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
use SLoggerGrpc\Services\SLoggerTraceCollectorGrpcService;
use SLoggerLaravel\ApiClients\Grpc\SLoggerGrpcClient;
use SLoggerLaravel\ApiClients\Http\SLoggerHttpClient;

readonly class SLoggerApiClientFactory
{
    public function __construct(private Application $app)
    {
    }

    public function create(string $apiClientName): SLoggerApiClientInterface
    {
        return match ($apiClientName) {
            'http' => $this->createHttp(),
            'grpc' => $this->createGrpc(),
            default => throw new RuntimeException("Unknown api client [$apiClientName]"),
        };
    }

    private function createHttp(): SLoggerHttpClient
    {
        $config = $this->app['config']['slogger.api_clients.http'];

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
    }

    private function createGrpc(): SLoggerGrpcClient
    {
        if (!class_exists(SLoggerTraceCollectorGrpcService::class)) {
            throw new RuntimeException(
                'The package slogger/grpc is not installed'
            );
        }

        $config = $this->app['config']['slogger.api_clients.grpc'];

        $url   = $config['url'];
        $token = $config['token'];

        try {
            return new SLoggerGrpcClient(
                $token,
                new SLoggerTraceCollectorGrpcService(
                    hostname: $url,
                    opts: [
                        'credentials' => ChannelCredentials::createInsecure(),
                    ]
                )
            );
        } catch (Exception $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }
}
