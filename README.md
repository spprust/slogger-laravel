# SLogger for laravel

## Installation

### App

system:
```bash
php artisan vendor:publish --tag=slogger-laravel
```

.env:
```dotenv
# slogger
SLOGGER_ENABLED=true

# slogger.api-clients
# http, grpc (required slogger/grpc)
SLOGGER_API_CLIENT=http
SLOGGER_HTTP_CLIENT_URL=
SLOGGER_HTTP_CLIENT_TOKEN=
SLOGGER_GRPC_CLIENT_URL=
SLOGGER_GRPC_CLIENT_TOKEN=
SLOGGER_REQUESTS_HEADER_PARENT_TRACE_ID_KEY=x-parent-trace-id

## slogger.queues
SLOGGER_QUEUE_TRACES_PUSHING_CONNECTION=${QUEUE_CONNECTION}
SLOGGER_QUEUE_TRACES_PUSHING_NAME=slogger-pushing

## slogger.logging
SLOGGER_LOG_CHANNEL=daily

## slogger.profiling
SLOGGER_PROFILING_ENABLED=true

## slogger.watchers
SLOGGER_LOG_REQUESTS_ENABLED=true
SLOGGER_LOG_COMMANDS_ENABLED=true
SLOGGER_LOG_DATABASE_ENABLED=true
SLOGGER_LOG_LOG_ENABLED=true
SLOGGER_LOG_SCHEDULE_ENABLED=true
SLOGGER_LOG_JOBS_ENABLED=true
SLOGGER_LOG_MODEL_ENABLED=true
SLOGGER_LOG_GATE_ENABLED=true
SLOGGER_LOG_EVENT_ENABLED=true
SLOGGER_LOG_MAIL_ENABLED=true
SLOGGER_LOG_NOTIFICATION_ENABLED=true
SLOGGER_LOG_CACHE_ENABLED=true
SLOGGER_LOG_DUMP_ENABLED=true
SLOGGER_LOG_HTTP_ENABLED=true
```

For requests watcher you can use the middleware
```php
\SLoggerLaravel\Middleware\SLoggerHttpMiddleware::class
```

For guzzle requests you can use the factory
```php
new \GuzzleHttp\Client([
    'base_uri' => 'https://url.com',
    'handler'  => app(\SLoggerLaravel\Guzzle\SLoggerGuzzleHandlerFactory::class)->prepareHandler(
        (new SLoggerRequestDataFormatters())
            ->add(
                new SLoggerRequestDataFormatter(
                    urlPatterns: ['*'],
                    requestHeaders: [
                        'authorization',
                    ]
                )
            )
            ->add(
                new SLoggerRequestDataFormatter(
                    urlPatterns: [
                        '/api/auth/*',
                        '*sensitive/some/*',
                    ],
                    hideAllResponseData: true
                )
            )
    ),
])
```

### Profiling

bash
```bash
pecl install xhprof
```

php.ini
```ini
[xhprof]
extension=xhprof.so
```
