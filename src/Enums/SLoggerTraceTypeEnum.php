<?php

namespace SLoggerLaravel\Enums;

enum SLoggerTraceTypeEnum: string
{
    case Cache = 'cache';
    case Command = 'command';
    case Database = 'database';
    case Dump = 'dump';
    case Event = 'event';
    case Exception = 'exception';
    case Gate = 'gate';
    case Job = 'job';
    case Log = 'log';
    case Mail = 'mail';
    case Model = 'model';
    case Notification = 'notification';
    case Redis = 'redis';
    case Request = 'request';
    case Schedule = 'schedule';
}
