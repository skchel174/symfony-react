<?php

declare(strict_types=1);

use App\Console\ClearCacheCommand;
use App\Console\ClearLogCommand;
use App\Event\RequestEvent;
use App\EventListener\ProfilerSubscriber;
use App\EventListener\RoutingListener;
use App\Kernel;
use App\Router\RouterFactory;
use App\Service\ControllerResolver\ControllerResolver;
use App\Service\ControllerResolver\ControllerResolverInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
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

    // EventDispatcher
    $services->set(EventDispatcherInterface::class, EventDispatcher::class)
        ->alias(Psr\EventDispatcher\EventDispatcherInterface::class, EventDispatcherInterface::class)
        ->public();

    // Router
    $services->set(RouterInterface::class, Router::class)
        ->factory(service(RouterFactory::class))
        ->args([
            '%app.project_dir%/src/Controller',
            '%app.cache_dir%',
            '%app.debug%',
        ])
        ->public();

    $services->set(RouterFactory::class);

    $services->set(RoutingListener::class)
        ->tag('event_listener', ['event' => RequestEvent::class]);

    // Kernel
    $services->set(Kernel::class)
        ->public();

    $services->set(ControllerResolverInterface::class, ControllerResolver::class);

    $services->set(ProfilerSubscriber::class)
        ->args(['%app.debug%'])
        ->tag('event_subscriber');

    // Console
    $services->set(Application::class)
        ->public();

    $services->set(ClearCacheCommand::class)
        ->args(['%app.cache_dir%', service(Filesystem::class)])
        ->tag('console.command');

    $services->set(Filesystem::class);
};
