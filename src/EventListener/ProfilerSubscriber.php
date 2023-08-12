<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\RequestEvent;
use App\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class ProfilerSubscriber implements EventSubscriberInterface
{
    public function __construct(private bool $debug)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequest',
            ResponseEvent::class => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$this->debug) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('_time_used', microtime(true));
        $request->attributes->set('_memory_used', memory_get_usage());
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->debug) {
            return;
        }

        $request = $event->getRequest();
        $time = microtime(true) - $request->attributes->get('_time_used');
        $memory = memory_get_usage() - $request->attributes->get('_memory_used');

        $response = $event->getResponse();
        $response->headers->set('X-Time-Used', (string) $time);
        $response->headers->set('X-Memory-Used', (string) $memory);
    }
}