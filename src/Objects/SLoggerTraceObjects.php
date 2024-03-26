<?php

namespace SLoggerLaravel\Objects;

class SLoggerTraceObjects
{
    /** @var SLoggerTraceObject[] */
    private array $traces = [];

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

    public function toJson(): string
    {
        return json_encode([
            'traces' => array_map(
                fn(SLoggerTraceObject $trace) => $trace->toJson(),
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
                SLoggerTraceObject::fromJson($traceJson)
            );
        }

        return $result;
    }
}
