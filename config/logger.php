<?php

declare(strict_types=1);

use App\Console\ClearLogCommand;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('logger.dir', '%app.project_dir%/var/log');

    $services = $container->services();

    $services->set('logger.handler.default', StreamHandler::class)
        ->args(['%logger.dir%/%app.env%.log']);

    $services->set('logger.default', Logger::class)
        ->args([
            'default',
            [service('logger.handler.default')]
        ])
        ->alias(LoggerInterface::class, 'logger.default');

    $services->set(ClearLogCommand::class)
        ->args(['%logger.dir%', service(Filesystem::class)])
        ->tag('console.command');
};
