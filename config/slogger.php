<?php

use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\SLoggerTraceQueueDispatcher;
use SLoggerLaravel\Events\SLoggerWatcherErrorEvent;
use SLoggerLaravel\Jobs\SLoggerTraceCreateJob;
use SLoggerLaravel\Jobs\SLoggerTraceUpdateJob;
use SLoggerLaravel\Listeners\SLoggerWatcherErrorListener;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerCommandWatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerJobWatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerRequestWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerCacheWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerDatabaseWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerDumpWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerEventWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerGateWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerHttpClientWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerLogWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerMailWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerModelWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerNotificationWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerScheduleWatcher;

return [
    'enabled' => env('SLOGGER_ENABLED', false),

    'http_client' => [
        'url'   => env('SLOGGER_HTTP_CLIENT_URL'),
        'token' => env('SLOGGER_HTTP_CLIENT_TOKEN'),
    ],

    'profiling'  => [
        'enabled'    => env('SLOGGER_PROFILING_ENABLED', false),
        'namespaces' => [
            'App\*',
        ],
    ],

    /** @see SLoggerTraceDispatcherInterface */
    'dispatcher' => SLoggerTraceQueueDispatcher::class,

    'log_channel' => env('SLOGGER_LOG_CHANNEL', 'daily'),

    'queue' => [
        'connection' => env('SLOGGER_QUEUE_TRACES_PUSHING_CONNECTION', env('QUEUE_CONNECTION')),
        'name'       => env('SLOGGER_QUEUE_TRACES_PUSHING_NAME', 'slogger-pushing'),
    ],

    'listeners' => [
        SLoggerWatcherErrorEvent::class => [
            SLoggerWatcherErrorListener::class,
        ],
    ],

    'watchers_customizing' => [
        'requests' => [
            'header_parent_trace_id_key'      => env(
                'SLOGGER_REQUESTS_HEADER_PARENT_TRACE_ID_KEY',
                'x-parent-trace-id'
            ),

            /** url_patterns */
            'excepted_paths'                  => [
                //
            ],

            /** url_patterns */
            'paths_with_cleaning_of_request'  => [
                'admin-api/auth/login',
            ],

            /** url_pattern => keys */
            'mask_request_header_fields'      => [
                '*' => [
                    'authorization',
                    'cookie',
                    'x-xsrf-token',
                ],
            ],

            /** url_pattern => key_patterns */
            'mask_request_parameters'         => [
                '*' => [
                    '*token*',
                    '*password*',
                ],
            ],

            /** url_patterns */
            'paths_with_cleaning_of_response' => [
                'admin-api/auth/*',
            ],

            /** url_pattern => keys */
            'mask_response_header_fields'     => [
                '*' => [
                    'set-cookie',
                ],
            ],

            /** url_pattern => key_patterns */
            'mask_response_fields'            => [
                '*' => [
                    '*token*',
                    '*password*',
                ],
            ],
        ],

        'commands' => [
            'excepted' => [
                'queue:work',
                'queue:listen',
            ],
        ],

        'jobs' => [
            'excepted' => [
                SLoggerTraceCreateJob::class,
                SLoggerTraceUpdateJob::class,
            ],
        ],

        'models' => [
            /** model_class => field_patterns */
            'masks' => [
                '*' => [
                    '*token*',
                    '*password*',
                ],
            ],
        ],
    ],

    'watchers' => [
        [
            'class'   => SLoggerRequestWatcher::class,
            'enabled' => env('SLOGGER_LOG_REQUESTS_ENABLED', false),
        ],
        [
            'class'   => SLoggerCommandWatcher::class,
            'enabled' => env('SLOGGER_LOG_COMMANDS_ENABLED', false),
        ],
        [
            'class'   => SLoggerDatabaseWatcher::class,
            'enabled' => env('SLOGGER_LOG_DATABASE_ENABLED', false),
        ],
        [
            'class'   => SLoggerLogWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => SLoggerScheduleWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => SLoggerJobWatcher::class,
            'enabled' => env('SLOGGER_LOG_JOBS_ENABLED', false),
        ],
        [
            'class'   => SLoggerModelWatcher::class,
            'enabled' => env('SLOGGER_LOG_MODEL_ENABLED', false),
        ],
        [
            'class'   => SLoggerGateWatcher::class,
            'enabled' => env('SLOGGER_LOG_GATE_ENABLED', false),
        ],
        [
            'class'   => SLoggerEventWatcher::class,
            'enabled' => env('SLOGGER_LOG_EVENT_ENABLED', false),
        ],
        [
            'class'   => SLoggerMailWatcher::class,
            'enabled' => env('SLOGGER_LOG_MAIL_ENABLED', false),
        ],
        [
            'class'   => SLoggerNotificationWatcher::class,
            'enabled' => env('SLOGGER_LOG_NOTIFICATION_ENABLED', false),
        ],
        [
            'class'   => SLoggerCacheWatcher::class,
            'enabled' => env('SLOGGER_LOG_CACHE_ENABLED', false),
        ],
        [
            'class'   => SLoggerDumpWatcher::class,
            'enabled' => env('SLOGGER_LOG_DUMP_ENABLED', false),
        ],
        [
            'class'   => SLoggerHttpClientWatcher::class,
            'enabled' => env('SLOGGER_LOG_HTTP_ENABLED', false),
        ],
    ],
];
