<?php

namespace SLoggerLaravel\RequestPreparer;

use Illuminate\Support\Str;
use SLoggerLaravel\DataResolver;
use SLoggerLaravel\Helpers\SLoggerMaskHelper;

class SLoggerRequestDataFormatter
{
    protected array $urlPatterns;

    /**
     * @param string[] $urlPatterns
     */
    public function __construct(
        array $urlPatterns,
        protected bool $hideAllRequestParameters = false,
        protected array $requestHeaders = [],
        protected array $requestParameters = [],
        protected bool $hideAllResponseData = false,
        protected array $responseHeaders = [],
        protected array $responseFields = [],
    ) {
        $this->urlPatterns = array_map(
            fn(string $urlPattern) => mb_trim($urlPattern, '/'),
            $urlPatterns
        );
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

    public function setHideAllResponseData(bool $hideAllResponseData): static
    {
        $this->hideAllResponseData = $hideAllResponseData;

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

    public function prepareResponseData(string $url, DataResolver $dataResolver): bool
    {
        if (!$this->is($url)) {
            return true;
        }

        if ($this->hideAllResponseData) {
            $dataResolver->setData([
                '__cleaned' => null,
            ]);

            return false;
        }

        $dataResolver->setData(
            SLoggerMaskHelper::maskArrayByPatterns(
                data: $dataResolver->getData(),
                patterns: $this->responseFields
            )
        );

        return true;
    }

    public function isHideAllResponseData(): bool
    {
        return $this->hideAllResponseData;
    }

    protected function is(string $url): bool
    {
        return Str::is($this->urlPatterns, mb_trim($url, '/'));
    }

    protected function prepareHeaders(array $headers): array
    {
        return collect($headers)
            ->map(fn($header) => implode(', ', (array) $header))
            ->all();
    }

    public function isHideAllRequestParameters(): bool
    {
        return $this->hideAllRequestParameters;
    }
}
