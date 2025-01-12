<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

class DataFormatter
{
    /**
     * @param Throwable $exception
     *
     * @return array{
     *     message: string,
     *     exception: string,
     *     file: string,
     *     line: int,
     *     trace: array<array{file: string, line: int}>
     * }
     */
    public static function exception(Throwable $exception): array
    {
        return [
            'message'   => $exception->getMessage(),
            'exception' => get_class($exception),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => self::stackTrace($exception->getTrace()),
        ];
    }

    public static function model(Model $model): string
    {
        return $model::class . ':' . $model->getKey();
    }

    /**
     * @see Throwable::getTrace()
     *
     * @param array<array{file: string, line: int}> $stackTrace
     *
     * @return array<array{file: string, line: int}>
     */
    private static function stackTrace(array $stackTrace): array
    {
        return array_map(
            fn(array $item) => Arr::only($item, ['file', 'line']),
            $stackTrace
        );
    }
}
