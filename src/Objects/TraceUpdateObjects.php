<?php

namespace SLoggerLaravel\Objects;

class TraceUpdateObjects
{
    /** @var TraceUpdateObject[] */
    private array $traces = [];

    public function add(TraceUpdateObject $traceObject): static
    {
        $this->traces[] = $traceObject;

        return $this;
    }

    /**
     * @return TraceUpdateObject[]
     */
    public function get(): array
    {
        return $this->traces;
    }

    public function toJson(): string
    {
        return json_encode([
            'traces' => array_map(
                fn(TraceUpdateObject $trace) => $trace->toJson(),
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
                TraceUpdateObject::fromJson($traceJson)
            );
        }

        return $result;
    }
}
