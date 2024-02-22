<?php

namespace SLoggerLaravel\Profiling\Dto;

class SLoggerProfilingObjects
{
    /** @var SLoggerProfilingObject[] */
    private array $items = [];

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
