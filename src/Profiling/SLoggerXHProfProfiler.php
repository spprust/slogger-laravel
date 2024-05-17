<?php

namespace SLoggerLaravel\Profiling;

use SLoggerLaravel\Profiling\Dto\SLoggerProfilingDataObject;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObject;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;

class SLoggerXHProfProfiler extends AbstractSLoggerProfiling
{
    private ?bool $xhprofLoaded = null;

    protected function onStart(): bool
    {
        if (!$this->xhprofLoaded()) {
            return false;
        }

        xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_NO_BUILTINS);

        return true;
    }

    protected function onStop(): ?SLoggerProfilingObjects
    {
        if (!$this->xhprofLoaded()) {
            return null;
        }

        $profilingItems = new SLoggerProfilingObjects(
            mainCaller: 'main()'
        );

        foreach (xhprof_disable() as $method => $data) {
            $methodData = explode('==>', $method);

            $callable = $methodData[1] ?? null;

            if (is_null($callable)) {
                continue;
            }

            $profilingItems->add(
                new SLoggerProfilingObject(
                    raw: $method,
                    calling: $methodData[0],
                    callable: $callable,
                    data: new SLoggerProfilingDataObject(
                        numberOfCalls: $data['ct'],
                        waitTimeInUs: $data['wt'],
                        cpuTime: $data['cpu'],
                        memoryUsageInBytes: $data['mu'],
                        peakMemoryUsageInBytes: $data['pmu'],
                    )
                )
            );
        }

        return $profilingItems;
    }

    private function xhprofLoaded(): bool
    {
        if (is_null($this->xhprofLoaded)) {
            $this->xhprofLoaded = extension_loaded('xhprof');
        }

        return $this->xhprofLoaded;
    }
}
