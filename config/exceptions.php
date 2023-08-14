<?php

declare(strict_types=1);

use App\Event\ExceptionEvent;
use App\EventListener\Exception\ApiExceptionListener;
use App\EventListener\Exception\ExceptionLogListener;
use App\EventListener\Exception\ExceptionMetadataListener;
use App\EventListener\Exception\HtmlExceptionListener;
use App\Service\ExceptionMetadataResolver\ExceptionMetadataResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('exceptions.mapping', []);

    $services = $container->services();

    $services->defaults()
        ->autowire();

    $services->set(ExceptionMetadataResolver::class)
        ->args(['%exceptions.mapping%']);

    $services->set(ExceptionMetadataListener::class)
        ->args([service(ExceptionMetadataResolver::class)])
        ->tag('event_listener', [
            'event' => ExceptionEvent::class,
            'priority' => 0,
        ]);

    $services->set(ExceptionLogListener::class)
        ->args([service(LoggerInterface::class)])
        ->tag('event_listener', [
            'event' => ExceptionEvent::class,
            'priority' => -50,
        ]);

    $services->set(ApiExceptionListener::class)
        ->args(['%debug%'])
        ->tag('event_listener', [
            'event' => ExceptionEvent::class,
            'priority' => -100,
        ]);

    $services->set(HtmlExceptionListener::class)
        ->args([service(HtmlErrorRenderer::class)])
        ->tag('event_listener', [
            'event' => ExceptionEvent::class,
            'priority' => -200,
        ]);

    $services->set(HtmlErrorRenderer::class)
        ->args(['%debug%']);
};
