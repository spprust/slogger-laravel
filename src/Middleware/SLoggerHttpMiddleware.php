<?php

namespace SLoggerLaravel\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use SLoggerLaravel\Events\SLoggerRequestHandling;
use SLoggerLaravel\SLoggerConfig;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;

class SLoggerHttpMiddleware implements TerminableInterface
{
    private ?string $traceId = null;
    private readonly ?string $headerParentTraceIdKey;

    public function __construct(
        private readonly Application $app,
        private readonly SLoggerTraceIdContainer $loggerTraceIdContainer,
        private readonly SLoggerConfig $loggerConfig
    ) {
        $this->headerParentTraceIdKey = $this->loggerConfig->requestsHeaderParentTraceIdKey();
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $parentTraceId = $request->header($this->headerParentTraceIdKey);

        $this->app['events']->dispatch(
            new SLoggerRequestHandling(
                request: $request,
                parentTraceId: is_array($parentTraceId) ? ($parentTraceId[0] ?? null) : null
            )
        );

        $this->traceId = $this->loggerTraceIdContainer->getParentTraceId();

        return $next($request);
    }

    public function terminate(\Symfony\Component\HttpFoundation\Request $request, Response $response)
    {
        if ($this->headerParentTraceIdKey) {
            $response->headers->set($this->headerParentTraceIdKey, $this->traceId);
        }
    }
}
