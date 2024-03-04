<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Psr\Http\Message\StreamInterface;
use Throwable;

class SLoggerDataFormatter
{
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

    public static function responseBody(StreamInterface $body): array
    {
        $size = $body->getSize();

        if ($size >= 1000000) { // 1mb
            return [
                'body' => "<cleaned:size-$size>",
            ];
        }

        $body->rewind();

        $result = json_decode($body->getContents(), true) ?: [];

        $body->rewind();

        return $result;
    }

    /**
     * @see Throwable::getTrace()
     */
    private static function stackTrace(array $stackTrace): array
    {
        return array_map(
            fn(array $item) => Arr::only($item, ['file', 'line']),
            $stackTrace
        );
    }
}
