<?php

namespace SLoggerLaravel\RequestPreparer;

use Illuminate\Support\Str;
use SLoggerLaravel\Helpers\SLoggerMaskHelper;

class SLoggerRequestDataFormatter
{
    public function __construct(
        protected array $urlPatterns,
        protected bool $clearRequestParameters = false,
        protected bool $clearResponseData = false,
        protected array $requestHeaders = [],
        protected array $requestParameters = [],
        protected array $responseHeaders = [],
        protected array $responseDataFields = [],
    ) {
    }

    public function setClearRequestParameters(bool $clearRequestParameters): static
    {
        $this->clearRequestParameters = $clearRequestParameters;

        return $this;
    }

    public function setClearResponseData(bool $clearResponseData): static
    {
        $this->clearResponseData = $clearResponseData;

        return $this;
    }

    public function addRequestHeaders(array $data): static
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $data);

        return $this;
    }

    public function addRequestParameters(array $data): static
    {
        $this->requestParameters = array_merge($this->requestParameters, $data);

        return $this;
    }

    public function addResponseHeaders(array $data): static
    {
        $this->responseHeaders = array_merge($this->responseHeaders, $data);

        return $this;
    }

    public function addResponseDataFields(array $data): static
    {
        $this->responseDataFields = array_merge($this->responseDataFields, $data);

        return $this;
    }

    public function prepareRequestHeaders(string $url, array $headers): array
    {
        if (!$this->is($url)) {
            return $headers;
        }

        return SLoggerMaskHelper::maskArrayByList(
            data: $this->prepareHeaders($headers),
            patterns: $this->requestHeaders
        );
    }

    public function prepareRequestParameters(string $url, array $parameters): array
    {
        if (!$this->is($url)) {
            return $parameters;
        }

        if ($this->clearRequestParameters) {
            return [
                '__cleaned' => null,
            ];
        }

        return SLoggerMaskHelper::maskArrayByPatterns(
            data: $parameters,
            patterns: $this->requestParameters
        );
    }

    public function prepareResponseHeaders(string $url, array $headers): array
    {
        if (!$this->is($url)) {
            return $headers;
        }

        return SLoggerMaskHelper::maskArrayByList(
            data: $this->prepareHeaders($headers),
            patterns: $this->responseHeaders
        );
    }

    public function prepareResponseData(string $url, array $data): array
    {
        if (!$this->is($url)) {
            return $data;
        }

        if ($this->clearResponseData) {
            return [
                '__cleaned' => null,
            ];
        }

        return SLoggerMaskHelper::maskArrayByPatterns(
            data: $data,
            patterns: $this->responseDataFields
        );
    }

    protected function is(string $url): bool
    {
        return Str::is($this->urlPatterns, $url);
    }

    protected function prepareHeaders(array $headers): array
    {
        return collect($headers)
            ->map(fn($header) => implode(', ', (array) $header))
            ->all();
    }
}
