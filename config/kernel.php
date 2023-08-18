<?php

declare(strict_types=1);

use App\Console\ClearCacheCommand;
use App\Console\DebugEventsCommand;
use App\EventListener\ProfilerSubscriber;
use App\Kernel;
use App\Service\ControllerResolver\ControllerResolver;
use App\Service\ControllerResolver\ControllerResolverInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(Kernel::class)
        ->args([
            service(EventDispatcherInterface::class),
            service(ControllerResolverInterface::class),
        ])
        ->public();

    $services->set(ControllerResolverInterface::class, ControllerResolver::class)
        ->args([service(ContainerInterface::class)]);

    $services->set(EventDispatcherInterface::class, EventDispatcher::class)
        ->alias(Psr\EventDispatcher\EventDispatcherInterface::class, EventDispatcherInterface::class)
        ->public();

    // Console application
    $services->set(Application::class)
        ->public();

    // Events
    $services->set(ProfilerSubscriber::class)
        ->args(['%app.debug%'])
        ->tag('event_subscriber');

    // Commands
    $services->set(DebugEventsCommand::class)
        ->args([service(EventDispatcherInterface::class)])
        ->tag('console.command');

    $services->set(ClearCacheCommand::class)
        ->args([
            '%app.cache_dir%',
            service(Filesystem::class)
        ])
        ->tag('console.command');
};
