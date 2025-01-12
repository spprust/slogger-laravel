<?php

namespace SLoggerLaravel\Dispatcher\Transporter;

use Exception;
use SLoggerLaravel\Dispatcher\TraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\Transporter\Clients\TransporterClientInterface;
use SLoggerLaravel\Objects\TraceObject;
use SLoggerLaravel\Objects\TraceUpdateObject;
use SLoggerLaravel\Profiling\Dto\ProfilingObjects;

class TraceTransporterDispatcher implements TraceDispatcherInterface
{
    /** @var TraceObject[] */
    private array $traces = [];

    private int $maxBatchSize = 5;

    /**
     * @throws Exception
     */
    public function __construct(
        private readonly TransporterClientInterface $client
    ) {
    }

    public function push(TraceObject $parameters): void
    {
        $this->traces[] = $parameters;

        if (count($this->traces) < $this->maxBatchSize) {
            return;
        }

        $actions = array_map(
            fn(TraceObject $trace) => $this->makeCreateData($trace),
            $this->traces
        );

        $this->traces = [];

        $this->client->dispatch($actions);
    }

    public function stop(TraceUpdateObject $parameters): void
    {
        $actions = [];

        if (count($this->traces)) {
            $actions = array_map(
                fn(TraceObject $trace) => $this->makeCreateData($trace),
                $this->traces
            );
        }

        $this->traces = [];

        $actions[] = $this->makeUpdateData($parameters);

        $this->client->dispatch($actions);
    }

    /**
     * @return array{tp: string, dt: string}
     */
    private function makeCreateData(TraceObject $trace): array
    {
        return $this->makeAction(
            type: 'cr',
            data: $this->traceCreateToJson($trace)
        );
    }

    /**
     * @return array{tp: string, dt: string}
     */
    private function makeUpdateData(TraceUpdateObject $trace): array
    {
        return $this->makeAction(
            type: 'upd',
            data: $this->traceUpdateToJson($trace)
        );
    }

    private function traceCreateToJson(TraceObject $trace): string
    {
        return json_encode([
            'tid' => $trace->traceId,
            'pid' => $trace->parentTraceId,
            'tp'  => $trace->type,
            'st'  => $trace->status,
            'tgs' => $trace->tags,
            'dt'  => json_encode($trace->data),
            'dur' => $trace->duration,
            'mem' => $trace->memory,
            'cpu' => $trace->cpu,
            'lat' => $trace->loggedAt->clone()
                ->setTimezone('UTC')
                ->toDateTimeString('microsecond'),
        ]);
    }

    private function traceUpdateToJson(TraceUpdateObject $trace): string
    {
        return json_encode([
            'tid' => $trace->traceId,
            'st'  => $trace->status,
            'pr'  => $trace->profiling
                ? $this->prepareProfiling($trace->profiling)
                : null,
            'tgs' => $trace->tags,
            'dt'  => is_null($trace->data) ? null : json_encode($trace->data),
            'dur' => $trace->duration,
            'mem' => $trace->memory,
            'cpu' => $trace->cpu,
        ]);
    }

    /**
     * @return array{tp: string, dt: string}
     */
    private function makeAction(string $type, string $data): array
    {
        return [
            'tp' => $type,
            'dt' => $data,
        ];
    }

    /**
     * @return array{
     *     mc: string,
     *     its: array{
     *      raw: string,
     *      c_ing: string,
     *      c_ble: string,
     *      dt: array{
     *          nm: string,
     *          val: int|float
     *      }[]
     *     }[]
     * }
     */
    private function prepareProfiling(ProfilingObjects $profiling): array
    {
        $result = [];

        foreach ($profiling->getItems() as $item) {
            $result[] = [
                'raw'   => $item->raw,
                'c_ing' => $item->calling,
                'c_ble' => $item->callable,
                'dt'    => [
                    $this->makeProfileDataItem('wait (us)', $item->data->waitTimeInUs),
                    $this->makeProfileDataItem('calls', $item->data->numberOfCalls),
                    $this->makeProfileDataItem('cpu', $item->data->cpuTime),
                    $this->makeProfileDataItem('mem (b)', $item->data->memoryUsageInBytes),
                    $this->makeProfileDataItem('mem peak (b)', $item->data->peakMemoryUsageInBytes),
                ],
            ];
        }

        return [
            'mc'  => $profiling->getMainCaller(),
            'its' => $result,
        ];
    }

    /**
     * @return array{nm: string, val: int|float}
     */
    private function makeProfileDataItem(string $name, int|float $value): array
    {
        return [
            'nm'  => $name,
            'val' => $value,
        ];
    }
}
