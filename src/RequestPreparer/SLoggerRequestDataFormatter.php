<?php

namespace SLoggerLaravel\RequestPreparer;

use Illuminate\Support\Str;
use SLoggerLaravel\Helpers\SLoggerMaskHelper;

class SLoggerRequestDataFormatter
{
    public function __construct(
        protected array $urlPatterns,
        protected bool $hideAllRequestParameters = false,
        protected array $requestHeaders = [],
        protected array $requestParameters = [],
        protected bool $hideAllResponseParameters = false,
        protected array $responseHeaders = [],
        protected array $responseFields = [],
    ) {
    }

    public function setHideAllRequestParameters(bool $hideAllRequestParameters): static
    {
        $this->hideAllRequestParameters = $hideAllRequestParameters;

        return $this;
    }

    public function addRequestHeaders(array $headers): static
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $headers);

        return $this;
    }

    public function addRequestParameters(array $parameters): static
    {
        $this->requestParameters = array_merge($this->requestParameters, $parameters);

        return $this;
    }

    public function setHideAllResponseParameters(bool $hideAllResponseParameters): static
    {
        $this->hideAllResponseParameters = $hideAllResponseParameters;

        return $this;
    }

    public function addResponseHeaders(array $headers): static
    {
        $this->responseHeaders = array_merge($this->responseHeaders, $headers);

        return $this;
    }

    public function addResponseFields(array $fields): static
    {
        $this->responseFields = array_merge($this->responseFields, $fields);

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

        if ($this->hideAllRequestParameters) {
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

        if ($this->hideAllResponseParameters) {
            return [
                '__cleaned' => null,
            ];
        }

        return SLoggerMaskHelper::maskArrayByPatterns(
            data: $data,
            patterns: $this->responseFields
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
