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

    // watchers_customizing.requests
    public function requestsExceptedPaths(): array
    {
        return $this->config['slogger.watchers_customizing.requests.excepted_paths'] ?? [];
    }

    // watchers_customizing.requests.input
    public function requestsInputFullHiding(): array
    {
        return $this->config['slogger.watchers_customizing.requests.input.full_hiding'] ?? [];
    }

    public function requestsInputMaskHeadersMasking(): array
    {
        return $this->config['slogger.watchers_customizing.requests.input.headers_masking'] ?? [];
    }

    public function requestsInputParametersMasking(): array
    {
        return $this->config['slogger.watchers_customizing.requests.input.parameters_masking'] ?? [];
    }

    // watchers_customizing.requests.output
    public function requestsOutputFullHiding(): array
    {
        return $this->config['slogger.watchers_customizing.requests.output.full_hiding'] ?? [];
    }

    public function requestsOutputHeadersMasking(): array
    {
        return $this->config['slogger.watchers_customizing.requests.output.headers_masking'] ?? [];
    }

    public function requestsOutputFieldsMasking(): array
    {
        return $this->config['slogger.watchers_customizing.requests.output.fields_masking'] ?? [];
    }

    // watchers_customizing.commands
    public function commandsExcepted(): array
    {
        return $this->config['slogger.watchers_customizing.commands.excepted'] ?? [];
    }

    // watchers_customizing.jobs
    public function jobsExcepted(): array
    {
        return $this->config['slogger.watchers_customizing.jobs.excepted'] ?? [];
    }

    // watchers_customizing.models
    public function modelsMasks(): array
    {
        return $this->config['slogger.watchers_customizing.models.masks'] ?? [];
    }
}
