<?php

namespace SLoggerLaravel\Objects;

class SLoggerTraceUpdateObjects
{
    /** @var SLoggerTraceUpdateObject[] */
    private array $traces = [];

    public function __construct()
    {
    }

    public function add(SLoggerTraceUpdateObject $traceObject): static
    {
        $this->traces[] = $traceObject;

        return $this;
    }

    /**
     * @return SLoggerTraceUpdateObject[]
     */
    public function get(): array
    {
        return $this->traces;
    }
}
