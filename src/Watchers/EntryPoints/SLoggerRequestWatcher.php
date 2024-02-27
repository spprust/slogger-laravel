<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\View\View;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Events\SLoggerRequestHandling;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Middleware\SLoggerHttpMiddleware;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see SLoggerHttpMiddleware - required for a tracing of requests
 */
class SLoggerRequestWatcher extends AbstractSLoggerWatcher
{
    protected array $requests = [];

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
        $parentTraceId = $event->parentTraceId;

        $traceId = $this->processor->startAndGetTraceId(
            type: SLoggerTraceTypeEnum::Request->value,
            tags: [
                $this->getRequestView($event->request),
            ],
            customParentTraceId: $parentTraceId
        );

        $this->requests[] = [
            'trace_id' => $traceId,
        ];
    }

    public function handleRequestHandled(RequestHandled $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleRequestHandled($event));
    }

    protected function onHandleRequestHandled(RequestHandled $event): void
    {
        $requestData = array_pop($this->requests);

        if (!$requestData) {
            return;
        }

        $traceId = $requestData['trace_id'];

        $startedAt = $this->app[Kernel::class]->requestStartedAt();

        $request  = $event->request;
        $response = $event->response;

        $data = [
            'ip_address'      => $request->ip(),
            'uri'             => str_replace($request->root(), '', $request->fullUrl()) ?: '/',
            'method'          => $request->method(),
            'action'          => optional($request->route())->getActionName(),
            'middlewares'     => array_values(optional($request->route())->gatherMiddleware() ?? []),
            'headers'         => $this->prepareHeaders($request, $request->headers->all()),
            'payload'         => $this->preparePayload($request, $this->getInput($request)),
            'response_status' => $response->getStatusCode(),
            'response'        => $this->prepareResponse($request, $response),
            ...$this->getAdditionalData(),
        ];

        $this->processor->stop(
            traceId: $traceId,
            data: $data,
            duration: SLoggerTraceHelper::calcDuration($startedAt)
        );
    }

    protected function getRequestView(Request $request): string
    {
        return $request->getPathInfo();
    }

    protected function getAdditionalData(): array
    {
        return [];
    }

    protected function prepareHeaders(Request $request, array $headers): array
    {
        return collect($headers)
            ->map(fn($header) => implode(', ', $header))
            ->all();
    }

    protected function preparePayload(Request $request, array $payload): array
    {
        return $payload;
    }

    protected function getInput(Request $request): array
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

    protected function prepareResponse(Request $request, Response $response): array
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
            return json_decode($response->getContent(), true) ?: [];
        }

        return [];
    }
}
