<?php

namespace SLoggerLaravel\Events;

use Illuminate\Http\Request;

class RequestHandling
{
    public function __construct(public Request $request, public ?string $parentTraceId)
    {
    }
}
