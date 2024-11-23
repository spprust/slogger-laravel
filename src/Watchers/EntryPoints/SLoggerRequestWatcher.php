<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SLoggerLaravel\DataResolver;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Events\SLoggerRequestHandling;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Middleware\SLoggerHttpMiddleware;
use SLoggerLaravel\RequestPreparer\SLoggerRequestDataFormatter;
use SLoggerLaravel\RequestPreparer\SLoggerRequestDataFormatters;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see SLoggerHttpMiddleware - required for a tracing of requests
 */
class SLoggerRequestWatcher extends AbstractSLoggerWatcher
{
    protected array $requests = [];

    protected array $exceptedPaths = [];

    protected SLoggerRequestDataFormatters $formatters;

    protected function init(): void
    {
        $this->exceptedPaths = $this->loggerConfig->requestsExceptedPaths();

        $this->fillMaskers();
    }

    public function register(): void
    {
        $this->listenEvent(SLoggerRequestHandling::class, [$this, 'handleRequestHandling']);
        $this->listenEvent(RequestHandled::class, [$this, 'handleRequestHandled']);
    }

    public function handleRequestHandling(SLoggerRequestHandling $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleRequestHandling($event));
    }

    protected function onHandleRequestHandling(SLoggerRequestHandling $event): void
    {
        if ($this->isRequestByPatterns($event->request, $this->exceptedPaths)) {
            return;
        }

        $parentTraceId = $event->parentTraceId;

        $bootTime = defined('LARAVEL_START')
            ? SLoggerTraceHelper::roundDuration((microtime(true) - LARAVEL_START))
            : -1;

        $traceId = $this->processor->startAndGetTraceId(
            type: SLoggerTraceTypeEnum::Request->value,
            tags: $this->getPreTags($event->request),
            data: [
                ...$this->getCommonRequestData($event->request),
                'boot_time' => $bootTime,
                'request'   => [
                    'headers'    => $this->prepareRequestHeaders($event->request),
                    'parameters' => $this->prepareRequestParameters($event->request),
                ],
            ],
            customParentTraceId: $parentTraceId
        );

        $this->requests[] = [
            'trace_id'  => $traceId,
            'boot_time' => $bootTime,
        ];
    }

    public function handleRequestHandled(RequestHandled $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleRequestHandled($event));
    }

    protected function onHandleRequestHandled(RequestHandled $event): void
    {
        if ($this->isRequestByPatterns($event->request, $this->exceptedPaths)) {
            return;
        }

        $requestData = array_pop($this->requests);

        if (!$requestData) {
            return;
        }

        $traceId = $requestData['trace_id'];


        $request  = $event->request;
        $response = $event->response;

        $data = [
            ...$this->getCommonRequestData($request),
            'boot_time' => $requestData['boot_time'],
            'request'   => [
                'headers'    => $this->prepareRequestHeaders($request),
                'parameters' => $this->prepareRequestParameters($request),
            ],
            'response'  => [
                'status'  => $response->getStatusCode(),
                'headers' => $this->prepareResponseHeaders($request, $response),
                'data'    => $this->prepareResponseData($request, $response),
            ],
            ...$this->getAdditionalData(),
        ];

        if (defined('LARAVEL_START')) {
            $startedAt = new Carbon(LARAVEL_START);
        } else {
            $startedAt = $this->app[Kernel::class]->requestStartedAt();
        }

        $this->processor->stop(
            traceId: $traceId,
            status: $response->isSuccessful()
                ? SLoggerTraceStatusEnum::Success->value
                : SLoggerTraceStatusEnum::Failed->value,
            tags: $this->getPostTags($request, $response),
            data: $data,
            duration: SLoggerTraceHelper::calcDuration($startedAt)
        );
    }

    protected function getCommonRequestData(Request $request): array
    {
        $url = str_replace($request->root(), '', $request->fullUrl());

        return [
            'ip_address'  => $request->ip(),
            'uri'         => $this->prepareUrl($url),
            'method'      => $request->method(),
            'action'      => optional($request->route())->getActionName(),
            'middlewares' => array_values(optional($request->route())->gatherMiddleware() ?? []),
        ];
    }

    protected function getPreTags(Request $request): array
    {
        $url = str_replace($request->root(), '', $request->fullUrl());

        return [
            $this->prepareUrl($url),
        ];
    }

    protected function getPostTags(Request $request, Response $response): array
    {
        $route = $request->route();

        if (!$route) {
            return [];
        }

        return [
            $this->prepareUrl($route->uri()),
            ...array_values($route->originalParameters()),
        ];
    }

    protected function prepareUrl(string $url): string
    {
        return '/' . ltrim($url, '/');
    }

    protected function getAdditionalData(): array
    {
        return [];
    }

    protected function prepareRequestHeaders(Request $request): array
    {
        $uri = $this->getRequestPath($request);

        $headers = $request->headers->all();

        foreach ($this->formatters->getItems() as $formatter) {
            $headers = $formatter->prepareRequestHeaders($uri, $headers);
        }

        return $headers;
    }

    protected function prepareRequestParameters(Request $request): array
    {
        $uri = $this->getRequestPath($request);

        $parameters = $this->getRequestParameters($request);

        foreach ($this->formatters->getItems() as $formatter) {
            $parameters = $formatter->prepareRequestParameters($uri, $parameters);
        }

        return $parameters;
    }

    protected function prepareResponseHeaders(Request $request, Response $response): array
    {
        $uri = $this->getRequestPath($request);

        $headers = $response->headers->all();

        foreach ($this->formatters->getItems() as $formatter) {
            $headers = $formatter->prepareResponseHeaders($uri, $headers);
        }

        return $headers;
    }

    protected function prepareResponseData(Request $request, Response $response): array
    {
        if ($response instanceof RedirectResponse) {
            return [
                'redirect' => $response->getTargetUrl(),
            ];
        }

        if ($response instanceof IlluminateResponse && $response->getOriginalContent() instanceof View) {
            return [
                'view' => $response->getOriginalContent()->getPath(),
            ];
        }

        if ($request->acceptsJson()) {
            $url = $this->getRequestPath($request);

            $dataResolver = new DataResolver(
                fn() => json_decode($response->getContent(), true) ?: []
            );

            foreach ($this->formatters->getItems() as $formatter) {
                $continue = $formatter->prepareResponseData(
                    url: $url,
                    dataResolver: $dataResolver
                );

                if (!$continue) {
                    break;
                }
            }

            return $dataResolver->getData();
        }

        return [];
    }

    protected function isRequestByPatterns(Request $request, array $patterns): bool
    {
        $path = trim($request->getPathInfo(), '/');

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern, '/');

            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function getRequestParameters(Request $request): array
    {
        $files = $request->files->all();

        array_walk_recursive($files, function (&$file) {
            $file = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->isFile() ? ($file->getSize() / 1000) . 'KB' : '0',
            ];
        });

        return array_replace_recursive($request->input(), $files);
    }

    protected function fillMaskers(): void
    {
        /** @var array<string, SLoggerRequestDataFormatter> $formatterMap */
        $formatterMap = [];

        $inputFullHiding = $this->loggerConfig->requestsInputFullHiding();

        foreach ($inputFullHiding as $urlPattern) {
            $formatterMap[$urlPattern] ??= new SLoggerRequestDataFormatter([$urlPattern]);
            $formatterMap[$urlPattern]->setHideAllRequestParameters(true);
        }

        $inputMaskHeadersMasking = $this->loggerConfig->requestsInputMaskHeadersMasking();

        foreach ($inputMaskHeadersMasking as $urlPattern => $headers) {
            $formatterMap[$urlPattern] ??= new SLoggerRequestDataFormatter([$urlPattern]);
            $formatterMap[$urlPattern]->addRequestHeaders($headers);
        }

        $inputParametersMasking = $this->loggerConfig->requestsInputParametersMasking();

        foreach ($inputParametersMasking as $urlPattern => $parameters) {
            $formatterMap[$urlPattern] ??= new SLoggerRequestDataFormatter([$urlPattern]);
            $formatterMap[$urlPattern]->addRequestParameters($parameters);
        }

        $outputFullHiding = $this->loggerConfig->requestsOutputFullHiding();

        foreach ($outputFullHiding as $urlPattern) {
            $formatterMap[$urlPattern] ??= new SLoggerRequestDataFormatter([$urlPattern]);
            $formatterMap[$urlPattern]->setHideAllResponseData(true);
        }

        $outputHeadersMasking = $this->loggerConfig->requestsOutputHeadersMasking();

        foreach ($outputHeadersMasking as $urlPattern => $headers) {
            $formatterMap[$urlPattern] ??= new SLoggerRequestDataFormatter([$urlPattern]);
            $formatterMap[$urlPattern]->addResponseHeaders($headers);
        }

        $outputFieldsMasking = $this->loggerConfig->requestsOutputFieldsMasking();

        foreach ($outputFieldsMasking as $urlPattern => $fields) {
            $formatterMap[$urlPattern] ??= new SLoggerRequestDataFormatter([$urlPattern]);
            $formatterMap[$urlPattern]->addResponseFields($fields);
        }

        $this->formatters = new SLoggerRequestDataFormatters();

        foreach ($formatterMap as $formatter) {
            $this->formatters->add($formatter);
        }
    }

    protected function getRequestPath(Request $request): string
    {
        return $request->path();
    }
}
