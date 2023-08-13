<?php

declare(strict_types=1);

use App\ControllerResolver\ArgumentsResolver;
use App\ControllerResolver\ControllerResolver;
use App\Event\RequestEvent;
use App\EventListener\ProfilerSubscriber;
use App\EventListener\RoutingListener;
use App\Router\RouterFactory;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->defaults()
        ->autowire();

    $services->load('App\\Controller\\', '../src/Controller')
        ->public();

    // Container
    $services->alias(PsrContainerInterface::class, 'service_container')
        ->public();
    $services->alias(SymfonyContainerInterface::class, 'service_container')
        ->public();

    // Router
    $services->set(RouterInterface::class, Router::class)
        ->factory(service(RouterFactory::class))
        ->args([
            '%project_dir%/src/Controller',
            '%cache_dir%',
            '%debug%',
        ])
        ->public();

    $services->set(RouterFactory::class);

    $services->set(ControllerResolver::class)
        ->public();

    $services->set(ArgumentsResolver::class)
        ->public();

    // EventDispatcher
    $services->set(EventDispatcherInterface::class, EventDispatcher::class)
        ->alias(Psr\EventDispatcher\EventDispatcherInterface::class, EventDispatcherInterface::class)
        ->public();

    $services->set(RoutingListener::class)
        ->tag('event_listener', ['event' => RequestEvent::class]);

    $services->set(ProfilerSubscriber::class)
        ->args(['%debug%'])
        ->tag('event_subscriber');
};
