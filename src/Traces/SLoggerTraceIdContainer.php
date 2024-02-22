<?php

namespace SLoggerLaravel\Traces;

class SLoggerTraceIdContainer
{
    private ?string $parentTraceId = null;

    public function getParentTraceId(): ?string
    {
        return $this->parentTraceId;
    }

    public function setParentTraceId(?string $parentTraceId): static
    {
        $this->parentTraceId = $parentTraceId;

        return $this;
    }
}
