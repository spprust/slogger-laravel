<?php

namespace SLoggerLaravel\Objects;

class TraceObjects
{
    /** @var TraceObject[] */
    private array $traces = [];

    public function add(TraceObject $traceObject): static
    {
        $this->traces[] = $traceObject;

        return $this;
    }

    /**
     * @return TraceObject[]
     */
    public function get(): array
    {
        return $this->traces;
    }

    public function toJson(): string
    {
        return json_encode([
            'traces' => array_map(
                fn(TraceObject $trace) => $trace->toJson(),
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
                TraceObject::fromJson($traceJson)
            );
        }

        return $result;
    }
}
