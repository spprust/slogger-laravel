<?php

namespace SLoggerLaravel\Enums;

enum TraceStatusEnum: string
{
    case Started = 'started';
    case Failed = 'failed';
    case Success = 'success';
}
