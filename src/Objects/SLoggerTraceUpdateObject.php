<?php

namespace SLoggerLaravel\Objects;

use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;

class SLoggerTraceUpdateObject
{
    public function __construct(
        public string $traceId,
        public string $status,
        public ?SLoggerProfilingObjects $profiling = null,
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
            'profiling' => serialize($this->profiling),
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
            profiling: unserialize($data['profiling']),
            tags: $data['tags'],
            data: $data['data'],
            duration: $data['duration'],
            memory: $data['memory'],
            cpu: $data['cpu'],
        );
    }
}
