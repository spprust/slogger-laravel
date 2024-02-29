<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationSent;
use SLoggerLaravel\Enums\SLoggerTraceStatusEnum;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

/**
 * Not tested
 */
class SLoggerNotificationWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent(NotificationSent::class, [$this, 'handleNotification']);
    }

    public function handleNotification(NotificationSent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleNotification($event));
    }

    protected function onHandleNotification(NotificationSent $event): void
    {
        $notification = get_class($event->notification);

        $data = [
            'notification' => $notification,
            'queued'       => in_array(ShouldQueue::class, class_implements($event->notification)),
            'notifiable'   => $this->formatNotifiable($event->notifiable),
            'channel'      => $event->channel,
            'response'     => $event->response,
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Notification->value,
            status: SLoggerTraceStatusEnum::Success->value,
            tags: [
                $notification,
            ],
            data: $data
        );
    }

    protected function formatNotifiable($notifiable): string
    {
        if ($notifiable instanceof Model) {
            return SLoggerDataFormatter::model($notifiable);
        } elseif ($notifiable instanceof AnonymousNotifiable) {
            $routes = array_map(
                fn($route) => is_array($route) ? implode(',', $route) : $route,
                $notifiable->routes
            );

            return 'Anonymous:' . implode(',', $routes);
        }

        return get_class($notifiable);
    }
}
