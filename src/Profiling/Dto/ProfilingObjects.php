<?php

namespace SLoggerLaravel\Profiling\Dto;

class ProfilingObjects
{
    /**
     * @var ProfilingObject[]
     */
    private array $items = [];

    public function __construct(private readonly string $mainCaller)
    {
    }

    public function getMainCaller(): string
    {
        return $this->mainCaller;
    }

    public function add(ProfilingObject $object): static
    {
        $this->items[] = $object;

        return $this;
    }

    /**
     * @return ProfilingObject[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function toJson(): string
    {
        return json_encode([
            'mainCaller' => $this->mainCaller,
            'items'      => array_map(
                fn(ProfilingObject $item) => [
                    'raw'      => $item->raw,
                    'calling'  => $item->calling,
                    'callable' => $item->callable,
                    'data'     => [
                        [
                            'name'  => 'numberOfCalls',
                            'value' => $item->data->numberOfCalls,
                        ],
                        [
                            'name'  => 'waitTimeInUs',
                            'value' => $item->data->waitTimeInUs,
                        ],
                        [
                            'name'  => 'cpuTime',
                            'value' => $item->data->cpuTime,
                        ],
                        [
                            'name'  => 'memoryUsageInBytes',
                            'value' => $item->data->memoryUsageInBytes,
                        ],
                        [
                            'name'  => 'peakMemoryUsageInBytes',
                            'value' => $item->data->peakMemoryUsageInBytes,
                        ],
                    ],
                ],
                $this->items
            ),
        ]);
    }

    public static function fromJson(string $json): ProfilingObjects
    {
        $jsonData = json_decode($json, true);

        $result = new ProfilingObjects($jsonData['mainCaller']);

        foreach ($jsonData['items'] as $item) {
            $data = $item['data'];

            $result->add(
                new ProfilingObject(
                    raw: $item['raw'],
                    calling: $item['calling'],
                    callable: $item['callable'],
                    data: new ProfilingDataObject(
                        numberOfCalls: $data['numberOfCalls']['value'],
                        waitTimeInUs: $data['waitTimeInUs']['value'],
                        cpuTime: $data['cpuTime']['value'],
                        memoryUsageInBytes: $data['memoryUsageInBytes']['value'],
                        peakMemoryUsageInBytes: $data['peakMemoryUsageInBytes']['value']
                    )
                )
            );
        }

        return $result;
    }
}
