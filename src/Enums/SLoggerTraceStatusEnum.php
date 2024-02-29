<?php

namespace SLoggerLaravel\Enums;

enum SLoggerTraceStatusEnum: string
{
    case Started = 'started';
    case Failed = 'failed';
    case Success = 'success';
}
