<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Throwable;

class SLoggerHttpClientWatcher extends AbstractSLoggerWatcher
{
    protected ?string $headerTraceIdKey;
    protected ?string $headerParentTraceIdKey;

    protected array $requests = [];

    protected function init(): void
    {
        $this->headerTraceIdKey       = Str::random(20);
        $this->headerParentTraceIdKey = $this->app['config']['slogger.requests.header_parent_trace_id_key'];
    }

    public function register(): void
    {
        /** @see SLoggerGuzzleHandlerFactory */
    }

    final public function handleRequest(RequestInterface $request): RequestInterface
    {
        $requestResult = $this->safeHandleWatching(
            function () use ($request) {
                return $this->onHandleRequest($request);
            }
        );

        return $requestResult ?: $request;
    }

    protected function onHandleRequest(RequestInterface $request): RequestInterface
    {
        if (!$this->isSubscribeRequest($request)) {
            return $request;
        }

        $traceId = $this->processor->startAndGetTraceId(
            type: 'http-client'
        );

        $this->requests[$traceId] = [
            'trace_id'   => $traceId,
            'started_at' => now(),
        ];

        $request = $request->withHeader($this->headerTraceIdKey, $traceId);

        if ($this->headerParentTraceIdKey) {
            $request = $request->withHeader(
                $this->headerParentTraceIdKey,
                $this->traceIdContainer->getParentTraceId()
            );
        }

        return $request;
    }

    final public function handleResponse(RequestInterface $request, array $options, ResponseInterface $response): void
    {
        $this->safeHandleWatching(
            function () use ($request, $options, $response) {
                $this->onHandleResponse($request, $options, $response);
            }
        );
    }

    protected function onHandleResponse(RequestInterface $request, array $options, ResponseInterface $response): void
    {
        if (!$this->isSubscribeRequest($request)) {
            return;
        }

        $traceId = $request->getHeader($this->headerTraceIdKey)[0];

        $requestData = $this->requests[$traceId] ?? null;

        if (!$requestData) {
            return;
        }

        /** @var Carbon $startedAt */
        $startedAt = $requestData['started_at'];

        $uri = (string) $request->getUri();

        $statusCode = $response->getStatusCode();

        $this->processor->stop(
            traceId: $traceId,
            status: ($statusCode >= 200 && $statusCode < 300)
                ? SLoggerTraceStatusEnum::Success->value
                : SLoggerTraceStatusEnum::Failed->value,
            tags: $uri ? [$uri] : [],
            data: [
                'method'   => $request->getMethod(),
                'uri'      => $uri,
                'request'  => [
                    'headers' => $this->getRequestHeaders($request),
                    'payload' => $this->getRequestPayload($request),
                ],
                'response' => [
                    'status_code' => $statusCode,
                    'headers'     => $this->getResponseHeaders($response),
                    'body'        => $this->getResponseBody($response),
                ],
            ],
            duration: SLoggerTraceHelper::calcDuration($startedAt)
        );
    }

    final public function handleInvalidResponse(RequestInterface $request, Throwable $exception): void
    {
        $this->safeHandleWatching(
            function () use ($request, $exception) {
                $this->onHandleInvalidResponse($request, $exception);
            }
        );
    }

    protected function onHandleInvalidResponse(RequestInterface $request, Throwable $exception): void
    {
        if (!$this->isSubscribeRequest($request)) {
            return;
        }

        $traceId = $request->getHeader($this->headerTraceIdKey)[0];

        $requestData = $this->requests[$traceId] ?? null;

        if (!$requestData) {
            return;
        }

        /** @var Carbon $startedAt */
        $startedAt = $requestData['started_at'];

        $uri = (string) $request->getUri();

        $this->processor->stop(
            traceId: $traceId,
            status: SLoggerTraceStatusEnum::Failed->value,
            tags: $uri ? [$uri] : [],
            data: [
                'method'    => $request->getMethod(),
                'uri'       => $uri,
                'request'   => [
                    'headers' => $this->getRequestHeaders($request),
                    'payload' => $this->getRequestPayload($request),
                ],
                'exception' => SLoggerDataFormatter::exception($exception),
            ],
            duration: SLoggerTraceHelper::calcDuration($startedAt)
        );
    }

    protected function isSubscribeRequest(RequestInterface $request): bool
    {
        return true;
    }

    protected function getRequestHeaders(RequestInterface $request): array
    {
        return $request->getHeaders();
    }

    protected function getRequestPayload(RequestInterface $request): array
    {
        $body = $request->getBody();

        $content = json_decode($body->getContents(), true) ?: [];

        $body->rewind();

        return $content;
    }

    protected function getResponseHeaders(ResponseInterface $response): array
    {
        return $response->getHeaders();
    }

    protected function getResponseBody(ResponseInterface $response): array
    {
        $body = $response->getBody();

        $content = json_decode($body->getContents(), true) ?: [];

        $body->rewind();

        return $content;
    }
}
