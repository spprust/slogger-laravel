<?php

namespace SLoggerLaravel\RequestPreparer;

class SLoggerRequestDataFormatters
{
    /** @var SLoggerRequestDataFormatter[] */
    protected array $items = [];

    public function add(SLoggerRequestDataFormatter $formatter): static
    {
        $this->items[] = $formatter;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
