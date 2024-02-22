<?php

namespace SLoggerLaravel\Events;

use Illuminate\Http\Request;

class SLoggerRequestHandling
{
    public function __construct(public Request $request, ?string $parentTraceId)
    {
    }
}
