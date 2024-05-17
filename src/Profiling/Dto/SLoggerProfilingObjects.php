<?php

namespace SLoggerLaravel\Profiling\Dto;

class SLoggerProfilingObjects
{
    /** @var SLoggerProfilingObject[] */
    private array $items = [];

    public function __construct(private readonly string $mainCaller)
    {
    }

    public function getMainCaller(): string
    {
        return $this->mainCaller;
    }

    public function add(SLoggerProfilingObject $object): static
    {
        $this->items[] = $object;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
