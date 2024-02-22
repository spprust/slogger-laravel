<?php

namespace SLoggerLaravel\Objects;

class SLoggerTraceObjects
{
    /** @var SLoggerTraceObject[] */
    private array $traces = [];

    public function __construct()
    {
    }

    public function add(SLoggerTraceObject $traceObject): static
    {
        $this->traces[] = $traceObject;

        return $this;
    }

    /**
     * @return SLoggerTraceObject[]
     */
    public function get(): array
    {
        return $this->traces;
    }
}
