<?php

namespace SLoggerLaravel\ApiClients\Grpc;

use Google\Protobuf\DoubleValue;
use Google\Protobuf\Int32Value;
use Google\Protobuf\StringValue;
use Google\Protobuf\Timestamp;
use SLoggerGrpc\Services\SLoggerGrpcResponseException;
use SLoggerGrpc\Services\SLoggerTraceCollectorGrpcService;
use SLoggerGrpcDto\TraceCollector\TagsObject;
use SLoggerGrpcDto\TraceCollector\TraceCreateObject;
use SLoggerGrpcDto\TraceCollector\TraceCreateRequest;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemDataItemObject;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemDataItemValueObject;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemObject;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemsObject;
use SLoggerGrpcDto\TraceCollector\TraceUpdateObject;
use SLoggerGrpcDto\TraceCollector\TraceUpdateRequest;
use SLoggerLaravel\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;
use Spiral\RoadRunner\GRPC\Context;

readonly class SLoggerGrpcClient implements SLoggerApiClientInterface
{
    public function __construct(
        private string $apiToken,
        private SLoggerTraceCollectorGrpcService $grpcService
    ) {
    }

    /**
     * @throws SLoggerGrpcResponseException
     */
    public function sendTraces(SLoggerTraceObjects $traceObjects): void
    {
        $objects = [];

        foreach ($traceObjects->get() as $item) {
            $loggedAt = new Timestamp();
            $loggedAt->fromDateTime(now('UTC'));

            $objects[] = (new TraceCreateObject())
                ->setTraceId($item->traceId)
                ->setParentTraceId(
                    is_null($item->parentTraceId)
                        ? null
                        : new StringValue(['value' => $item->parentTraceId])
                )
                ->setType($item->type)
                ->setStatus($item->status)
                ->setTags($item->tags)
                ->setData(json_encode($item->data))
                ->setDuration(
                    is_null($item->duration)
                        ? null
                        : new DoubleValue(['value' => $item->duration])
                )
                ->setMemory(
                    is_null($item->memory)
                        ? null
                        : new DoubleValue(['value' => $item->memory])
                )
                ->setCpu(
                    is_null($item->cpu)
                        ? null
                        : new DoubleValue(['value' => $item->cpu])
                )
                ->setLoggedAt($loggedAt);
        }

        $this->grpcService->Create(
            new Context([
                'metadata' => [
                    'authorization' => [
                        "Bearer $this->apiToken",
                    ],
                ],
            ]),
            new TraceCreateRequest([
                'traces' => $objects,
            ])
        );
    }

    /**
     * @throws SLoggerGrpcResponseException
     */
    public function updateTraces(SLoggerTraceUpdateObjects $traceObjects): void
    {
        $objects = [];

        foreach ($traceObjects->get() as $item) {
            $loggedAt = new Timestamp();
            $loggedAt->fromDateTime(now('UTC'));

            $objects[] = (new TraceUpdateObject())
                ->setTraceId($item->traceId)
                ->setStatus($item->status)
                ->setProfiling(
                    is_null($item->profiling)
                        ? null
                        : $this->makeProfiling($item->profiling)

                )
                ->setTags(
                    is_null($item->tags)
                        ? null
                        : new TagsObject(['items' => $item->tags])
                )
                ->setData(
                    is_null($item->data)
                        ? null
                        : new StringValue(['value' => json_encode($item->data)])
                )
                ->setDuration(
                    is_null($item->duration)
                        ? null
                        : new DoubleValue(['value' => $item->duration])
                )
                ->setMemory(
                    is_null($item->memory)
                        ? null
                        : new DoubleValue(['value' => $item->memory])
                )
                ->setCpu(
                    is_null($item->cpu)
                        ? null
                        : new DoubleValue(['value' => $item->cpu])
                );
        }

        $this->grpcService->Update(
            new Context([
                'metadata' => [
                    'authorization' => [
                        "Bearer $this->apiToken",
                    ],
                ],
            ]),
            new TraceUpdateRequest([
                'traces' => $objects,
            ])
        );
    }

    private function makeProfiling(SLoggerProfilingObjects $profiling): TraceProfilingItemsObject
    {
        /** @var TraceProfilingItemObject[] $items */
        $items = [];

        foreach ($profiling->getItems() as $item) {
            $items[] = (new TraceProfilingItemObject())
                ->setRaw($item->raw)
                ->setCalling($item->calling)
                ->setCallable($item->callable)
                ->setData([
                    (new TraceProfilingItemDataItemObject())
                        ->setName('wait (us)')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                            ->setInt(new Int32Value([
                                'value' => $item->data->waitTimeInUs
                            ]))
                        ),
                    (new TraceProfilingItemDataItemObject())
                        ->setName('calls')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                            ->setDouble(new DoubleValue([
                                'value' => $item->data->numberOfCalls
                            ]))
                        ),
                    (new TraceProfilingItemDataItemObject())
                        ->setName('cpu')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                            ->setDouble(new DoubleValue([
                                'value' => $item->data->cpuTime
                            ]))
                        ),
                    (new TraceProfilingItemDataItemObject())
                        ->setName('mem (b)')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                            ->setDouble(new DoubleValue([
                                'value' => $item->data->memoryUsageInBytes
                            ]))
                        ),
                    (new TraceProfilingItemDataItemObject())
                        ->setName('mem peak (b)')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                            ->setDouble(new DoubleValue([
                                'value' => $item->data->peakMemoryUsageInBytes
                            ]))
                        ),
                ]);
        }

        return (new TraceProfilingItemsObject())
            ->setMainCaller($profiling->getMainCaller())
            ->setItems($items);
    }
}
