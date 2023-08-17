<?php

declare(strict_types=1);

use App\Doctrine\EntityManagerFactory;
use Doctrine\Migrations\Configuration\Configuration as MigrationsConfiguration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $parameters = $container->parameters();

    $parameters->set('doctrine.default', [
        'cache_dir' => '%app.cache_dir%/doctrine/cache',
        'proxy_dir' => '%app.cache_dir%/doctrine/proxy',

        'metadata' => [
            '%app.project_dir%/src/Entity',
        ],

        'connection' => [
            'driver' => 'pdo_mysql',
            'host' => getenv('MYSQL_HOST'),
            'port' => getenv('MYSQL_PORT'),
            'dbname' => getenv('MYSQL_DATABASE'),
            'user' => getenv('MYSQL_USER'),
            'password' => getenv('MYSQL_PASSWORD'),
        ],
    ]);

    $services = $container->services();

    $services->set(EntityManagerInterface::class, EntityManager::class)
        ->factory(service(EntityManagerFactory::class))
        ->args(['%doctrine.default%'])
        ->public();

    $services->set(EntityManagerFactory::class)
        ->args(['%app.env%']);

    $services->set(EntityManagerProvider::class, SingleManagerProvider::class)
        ->args([service(EntityManagerInterface::class)]);

    // Migrations
    $services->set(DependencyFactory::class)
        ->factory([null, 'fromEntityManager'])
        ->args([
            service(ExistingConfiguration::class),
            service(ExistingEntityManager::class),
        ]);

    $services->set(ExistingConfiguration::class)
        ->args([service(MigrationsConfiguration::class)]);

    $services->set(MigrationsConfiguration::class)
        ->call('setCheckDatabasePlatform', [true])
        ->call('setTransactional', [true])
        ->call('setAllOrNothing', [true])
        ->call('setMigrationOrganization', ['none'])
        ->call('setMetadataStorageConfiguration', [service(TableMetadataStorageConfiguration::class)])
        ->call('addMigrationsDirectory', ['App\Migration', '%app.project_dir%/src/Migration']);

    $services->set(TableMetadataStorageConfiguration::class);

    $services->set(ExistingEntityManager::class)
        ->args([service(EntityManagerInterface::class)]);

    // ORM Commands
    $services->set(GenerateProxiesCommand::class)
        ->args([service(EntityManagerProvider::class)])
        ->tag('console.command');

    $services->set(ValidateSchemaCommand::class)
        ->args([service(EntityManagerProvider::class)])
        ->tag('console.command');

    $services->set(DropCommand::class)
        ->args([service(EntityManagerProvider::class)])
        ->tag('console.command');

    // Migrations commands
    $services->set(DiffCommand::class)
        ->args([service(DependencyFactory::class), 'migrations:diff'])
        ->tag('console.command');

    $services->set(ExecuteCommand::class)
        ->args([service(DependencyFactory::class), 'migrations:execute'])
        ->tag('console.command');

    $services->set(MigrateCommand::class)
        ->args([service(DependencyFactory::class), 'migrations:migrate'])
        ->tag('console.command');
};
