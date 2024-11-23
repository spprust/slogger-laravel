<?php

namespace SLoggerLaravel;

use Closure;

class DataResolver
{
    private ?array $data = null;

    /**
     * @param Closure(): array $resolver
     */
    public function __construct(
        private readonly Closure $resolver
    ) {
    }

    public function getData(): array
    {
        return is_null($this->data)
            ? ($this->data = $this->resolve())
            : $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    private function resolve(): array
    {
        $resolver = $this->resolver;

        return $resolver();
    }
}
