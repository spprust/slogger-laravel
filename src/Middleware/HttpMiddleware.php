<?php

namespace SLoggerLaravel\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use SLoggerLaravel\Events\RequestHandling;
use SLoggerLaravel\Config;
use SLoggerLaravel\Traces\TraceIdContainer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;

class HttpMiddleware implements TerminableInterface
{
    private ?string $traceId = null;
    private readonly ?string $headerParentTraceIdKey;

    public function __construct(
        private readonly Application $app,
        private readonly TraceIdContainer $loggerTraceIdContainer,
        private readonly Config $loggerConfig
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
            new RequestHandling(
                request: $request,
                parentTraceId: is_array($parentTraceId)
                    ? ($parentTraceId[0] ?? null)
                    : (is_string($parentTraceId) ? $parentTraceId : null)
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
