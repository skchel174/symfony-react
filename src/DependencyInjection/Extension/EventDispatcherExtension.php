<?php

declare(strict_types=1);

namespace App\DependencyInjection\Extension;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcherExtension extends Extension implements CompilerPassInterface
{
    /**
     * Tags array for services tagged by 'event-listener':
     * - 'event' => event name
     * - 'event_dispatcher' => event dispatcher id
     * - 'method' => method of event listener
     * - 'priority' => integer value of priority
     *
     * Tags array for services tagged by 'event-subscriber':
     * - 'event_dispatcher' => event dispatcher id
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(EventDispatcherInterface::class)) {
            return;
        }

        $defaultDispatcher = $container->getDefinition(EventDispatcherInterface::class);

        foreach ($container->findTaggedServiceIds('event_listener') as $id => $tags) {
            foreach ($tags as $tag) {
                $dispatcher = isset($tag['dispatcher'])
                    ? $container->getDefinition($tag['dispatcher'])
                    : $defaultDispatcher;

                $dispatcher->addMethodCall('addListener', [
                    '$eventName' => $tag['event'],
                    '$listener' => [new Reference($id), $tag['method'] ?? '__invoke'],
                    '$priority' => $tag['priority'] ?? 0,
                ]);
            }
        }

        foreach ($container->findTaggedServiceIds('event_subscriber') as $id => $tags) {
            foreach ($tags as $tag) {
                $dispatcher = isset($tag['dispatcher'])
                    ? $container->getDefinition($tag['dispatcher'])
                    : $defaultDispatcher;

                $dispatcher->addMethodCall('addSubscriber', [
                    '$subscriber' => new Reference($id),
                ]);
            }
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}
