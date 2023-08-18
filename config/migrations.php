<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(DependencyFactory::class)
        ->factory([null, 'fromEntityManager'])
        ->args([
            service(ExistingConfiguration::class),
            service(ExistingEntityManager::class),
        ]);

    $services->set(ExistingConfiguration::class)
        ->args([service(Configuration::class)]);

    $services->set(Configuration::class)
        ->call('setCheckDatabasePlatform', [true])
        ->call('setTransactional', [true])
        ->call('setAllOrNothing', [true])
        ->call('setMigrationOrganization', ['none'])
        ->call('setMetadataStorageConfiguration', [service(TableMetadataStorageConfiguration::class)])
        ->call('addMigrationsDirectory', ['App\Database\Migration', '%app.project_dir%/src/Database/Migration']);

    $services->set(TableMetadataStorageConfiguration::class)
        ->call('setTableName', ['migrations']);

    $services->set(ExistingEntityManager::class)
        ->args([service(EntityManagerInterface::class)]);

    // CLI commands
    $services->set(DiffCommand::class)
        ->args([
            service(DependencyFactory::class),
            'migrations:diff',
        ])
        ->tag('console.command');

    $services->set(ExecuteCommand::class)
        ->args([
            service(DependencyFactory::class),
            'migrations:execute',
        ])
        ->tag('console.command');

    $services->set(MigrateCommand::class)
        ->args([
            service(DependencyFactory::class),
            'migrations:migrate',
        ])
        ->tag('console.command');
};
