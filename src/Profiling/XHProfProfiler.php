<?php

namespace SLoggerLaravel\Profiling;

use SLoggerLaravel\Profiling\Dto\ProfilingDataObject;
use SLoggerLaravel\Profiling\Dto\ProfilingObject;
use SLoggerLaravel\Profiling\Dto\ProfilingObjects;

class XHProfProfiler extends AbstractProfiling
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

    protected function onStop(): ?ProfilingObjects
    {
        if (!$this->xhprofLoaded()) {
            return null;
        }

        $profilingItems = new ProfilingObjects(
            mainCaller: 'main()'
        );

        foreach (xhprof_disable() as $method => $data) {
            $methodData = explode('==>', $method);

            $callable = $methodData[1] ?? null;

            if (is_null($callable)) {
                continue;
            }

            $profilingItems->add(
                new ProfilingObject(
                    raw: $method,
                    calling: $methodData[0],
                    callable: $callable,
                    data: new ProfilingDataObject(
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
