<?php

declare(strict_types=1);

use App\Console\FixturesLoadCommand;
use App\Service\FixturesLoader\FixturesLoader;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $autowireServices = $container->services()
        ->defaults()
            ->autowire()
            ->public();

    $autowireServices
        ->load('App\\Database\\Fixture\\', '../src/Database/Fixture/')
        ->load('App\\Database\\Factory\\', '../src/Database/Factory/');

    $services = $container->services();

    $services->set(FixturesLoader::class)
        ->args([
            service(ContainerInterface::class),
            ['%app.project_dir%/src/Database/Fixture/']
        ]);

    $services->set(Faker\Generator::class)
        ->factory([Faker\Factory::class, 'create']);

    // Commands
    $services->set(FixturesLoadCommand::class)
        ->args([
            service(EntityManagerInterface::class),
            service(FixturesLoader::class),
        ])
        ->tag('console.command');
};
