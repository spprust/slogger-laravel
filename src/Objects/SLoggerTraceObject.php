<?php

namespace SLoggerLaravel\Objects;

use Illuminate\Support\Carbon;

class SLoggerTraceObject
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public array $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt
    ) {
    }

    public function toJson(): string
    {
        return json_encode([
            'traceId'       => $this->traceId,
            'parentTraceId' => $this->parentTraceId,
            'type'          => $this->type,
            'status'        => $this->status,
            'tags'          => $this->tags,
            'data'          => $this->data,
            'duration'      => $this->duration,
            'memory'        => $this->memory,
            'cpu'           => $this->cpu,
            'loggedAt'      => $this->loggedAt->clone()
                ->setTimezone('UTC')
                ->toDateTimeString('microsecond'),
        ]);
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        return new static(
            traceId: $data['traceId'],
            parentTraceId: $data['parentTraceId'],
            type: $data['type'],
            status: $data['status'],
            tags: $data['tags'],
            data: $data['data'],
            duration: $data['duration'],
            memory: $data['memory'],
            cpu: $data['cpu'],
            loggedAt: (new Carbon($data['loggedAt']))->setTimezone('UTC'),
        );
    }
}
