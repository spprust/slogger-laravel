<?php

namespace SLoggerLaravel;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

readonly class Config
{
    private Repository $config;

    public function __construct(
        protected Application $app
    ) {
        $this->config = $this->app['config'];
    }

    public function profilingEnabled(): bool
    {
        return (bool) ($this->config['slogger.profiling.enabled'] ?? false);
    }

    public function requestsHeaderParentTraceIdKey(): ?string
    {
        return $this->config['slogger.watchers_customizing.requests.header_parent_trace_id_key'] ?? null;
    }

    /**
     * @return string[]
     */
    public function requestsExceptedPaths(): array
    {
        return $this->config['slogger.watchers_customizing.requests.excepted_paths'] ?? [];
    }

    /**
     * @return string[]
     */
    public function requestsInputFullHiding(): array
    {
        return $this->config['slogger.watchers_customizing.requests.input.full_hiding'] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function requestsInputMaskHeadersMasking(): array
    {
        return $this->config['slogger.watchers_customizing.requests.input.headers_masking'] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function requestsInputParametersMasking(): array
    {
        return $this->config['slogger.watchers_customizing.requests.input.parameters_masking'] ?? [];
    }

    /**
     * @return string[]
     */
    public function requestsOutputFullHiding(): array
    {
        return $this->config['slogger.watchers_customizing.requests.output.full_hiding'] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function requestsOutputHeadersMasking(): array
    {
        return $this->config['slogger.watchers_customizing.requests.output.headers_masking'] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function requestsOutputFieldsMasking(): array
    {
        return $this->config['slogger.watchers_customizing.requests.output.fields_masking'] ?? [];
    }

    /**
     * @return string[]
     */
    public function commandsExcepted(): array
    {
        return $this->config['slogger.watchers_customizing.commands.excepted'] ?? [];
    }

    /**
     * @return class-string[]
     */
    public function jobsExcepted(): array
    {
        return $this->config['slogger.watchers_customizing.jobs.excepted'] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function modelsMasks(): array
    {
        return $this->config['slogger.watchers_customizing.models.masks'] ?? [];
    }
}
