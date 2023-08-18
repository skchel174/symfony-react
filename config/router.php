<?php

declare(strict_types=1);

use App\Console\DebugRouterCommand;
use App\Event\RequestEvent;
use App\EventListener\RoutingListener;
use App\Router\AnnotationLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(RouterInterface::class, Router::class)
        ->args([
            service('router.routes_loader'),
            '%app.project_dir%/src/Controller',
            [
                'cache_dir' => '%app.cache_dir%/router',
                'debug' => '%app.debug%',
            ],
        ]);

    $services->set('router.routes_loader', AnnotationDirectoryLoader::class)
        ->args([
            service('router.file_locator'),
            service('router.annotation_loader'),
        ]);

    $services->set('router.file_locator', FileLocator::class);

    $services->set('router.annotation_loader', AnnotationLoader::class);

    $services->set(RoutingListener::class)
        ->args([service(RouterInterface::class)])
        ->tag('event_listener', ['event' => RequestEvent::class]);

    $services->set(DebugRouterCommand::class)
        ->args([service(RouterInterface::class)])
        ->tag('console.command');
};
