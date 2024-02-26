<?php

namespace SLoggerLaravel\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use SLoggerLaravel\Events\SLoggerRequestHandling;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;

class SLoggerHttpMiddleware implements TerminableInterface
{
    private ?string $traceId = null;
    private readonly ?string $headerParentTraceIdKey;

    public function __construct(
        private readonly Application $app,
        private readonly SLoggerTraceIdContainer $loggerTraceIdContainer
    ) {
        $this->headerParentTraceIdKey = $this->app['config']['slogger.requests.header_parent_trace_id_key'];
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
            new SLoggerRequestHandling($request, $parentTraceId)
        );

        $this->traceId = $this->loggerTraceIdContainer->getParentTraceId();

        return $next($request);
    }

    public function terminate(\Symfony\Component\HttpFoundation\Request $request, Response $response)
    {
        $response->headers->set($this->headerParentTraceIdKey, $this->traceId);
    }
}
