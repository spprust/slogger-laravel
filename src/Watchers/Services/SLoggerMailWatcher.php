<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Mail\Events\MessageSent;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\Mime\Address;

/**
 * Not tested
 */
class SLoggerMailWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent(MessageSent::class, [$this, 'handleMessageSent']);
    }

    public function handleMessageSent(MessageSent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleMessageSent($event));
    }

    protected function onHandleMessageSent(MessageSent $event): void
    {
        $data = [
            'mailable' => $this->getMailable($event),
            'queued'   => $this->getQueuedStatus($event),
            'from'     => $this->formatAddresses($event->message->getFrom()),
            'reply_to' => $this->formatAddresses($event->message->getReplyTo()),
            'to'       => $this->formatAddresses($event->message->getTo()),
            'cc'       => $this->formatAddresses($event->message->getCc()),
            'bcc'      => $this->formatAddresses($event->message->getBcc()),
            'subject'  => $event->message->getSubject(),
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Mail->value,
            data: $data
        );
    }

    protected function getMailable(MessageSent $event): string
    {
        return $event->data['__laravel_notification'] ?? '';
    }

    protected function getQueuedStatus(MessageSent $event): bool
    {
        return $event->data['__laravel_notification_queued'] ?? false;
    }

    protected function formatAddresses(?array $addresses): ?array
    {
        if (is_null($addresses)) {
            return null;
        }

        return collect($addresses)
            ->flatMap(function ($address, $key) {
                if ($address instanceof Address) {
                    return [$address->getAddress() => $address->getName()];
                }

                return [$key => $address];
            })
            ->all();
    }
}
