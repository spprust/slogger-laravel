<?php

namespace SLoggerLaravel\Middleware;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SLoggerLaravel\SLoggerState;
use SLoggerLaravel\Watchers\Services\SLoggerHttpClientWatcher;

readonly class SLoggerGuzzleHandlerFactory
{
    public function __construct(
        private SLoggerState $loggerState,
        private SLoggerHttpClientWatcher $httpClientWatcher
    ) {
    }

    public function makeHandler(?HandlerStack $handlerStack = null): HandlerStack
    {
        $handlersStack = $handlerStack ?: HandlerStack::create();

        if ($this->loggerState->isWatcherEnabled(SLoggerHttpClientWatcher::class)) {
            $handlersStack->push($this->request());
            $handlersStack->push($this->response());
        }

        return $handlersStack;
    }

    private function request(): callable
    {
        return Middleware::mapRequest(function (RequestInterface $request) {
            return $this->httpClientWatcher->handleRequest($request);
        });
    }

    private function response(): callable
    {
        return Middleware::mapResponse(function (ResponseInterface $response) {
            $this->httpClientWatcher->handleResponse($response);

            return $response;
        });
    }
}
