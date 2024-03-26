<?php

namespace SLoggerLaravel\Objects;

class SLoggerTraceUpdateObjects
{
    /** @var SLoggerTraceUpdateObject[] */
    private array $traces = [];

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

    public function toJson(): string
    {
        return json_encode([
            'traces' => array_map(
                fn(SLoggerTraceUpdateObject $trace) => $trace->toJson(),
                $this->traces
            ),
        ]);
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        $result = new static();

        foreach ($data['traces'] as $traceJson) {
            $result->add(
                SLoggerTraceUpdateObject::fromJson($traceJson)
            );
        }

        return $result;
    }
}
