<?php

namespace SLoggerLaravel;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

readonly class SLoggerConfig
{
    private Repository $config;

    public function __construct(
        protected Application $app
    ) {
        $this->config = $this->app['config'];
    }

    public function profilingEnabled(): bool
    {
        return (bool) ($this->config['profiling.enabled'] ?? false);
    }

    public function requestsHeaderParentTraceIdKey(): ?string
    {
        return $this->config['slogger.watchers_customizing.requests.header_parent_trace_id_key'] ?? null;
    }

    public function requestsExceptedPaths(): array
    {
        return $this->config['slogger.watchers_customizing.requests.excepted_paths'] ?? [];
    }

    public function requestsPathsWithCleaningOfResponse(): array
    {
        return $this->config['slogger.watchers_customizing.requests.paths_with_cleaning_of_response'] ?? [];
    }

    public function requestsMaskRequestHeaderFields(): array
    {
        return $this->config['slogger.watchers_customizing.requests.mask_request_header_fields'] ?? [];
    }

    public function requestsMaskResponseHeaderFields(): array
    {
        return $this->config['slogger.watchers_customizing.requests.mask_response_header_fields'] ?? [];
    }

    public function commandsExcepted(): array
    {
        return $this->config['slogger.watchers_customizing.commands.excepted'] ?? [];
    }

    public function jobsExcepted(): array
    {
        return $this->config['slogger.watchers_customizing.jobs.excepted'] ?? [];
    }
}
