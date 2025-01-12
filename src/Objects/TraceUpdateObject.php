<?php

namespace SLoggerLaravel\Objects;

use SLoggerLaravel\Profiling\Dto\ProfilingObjects;

class TraceUpdateObject
{
    /**
     * @param string[]|null             $tags
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        public string $traceId,
        public string $status,
        public ?ProfilingObjects $profiling = null,
        public ?array $tags = null,
        public ?array $data = null,
        public ?float $duration = null,
        public ?float $memory = null,
        public ?float $cpu = null,
    ) {
    }

    public function toJson(): string
    {
        return json_encode([
            'traceId'   => $this->traceId,
            'status'    => $this->status,
            'profiling' => $this->profiling?->toJson(),
            'tags'      => $this->tags,
            'data'      => $this->data,
            'duration'  => $this->duration,
            'memory'    => $this->memory,
            'cpu'       => $this->cpu,
        ]);
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        return new static(
            traceId: $data['traceId'],
            status: $data['status'],
            profiling: ($data['profiling'] ?? null)
                ? ProfilingObjects::fromJson($data['profiling'])
                : null,
            tags: $data['tags'],
            data: $data['data'],
            duration: $data['duration'],
            memory: $data['memory'],
            cpu: $data['cpu'],
        );
    }
}
