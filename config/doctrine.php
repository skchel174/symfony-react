<?php

declare(strict_types=1);

use App\Console\FixturesLoadCommand;
use App\Service\FixturesLoader\FixturesLoader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Configuration as MigrationsConfiguration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    // EntityManager
    $services->set(EntityManagerInterface::class, EntityManager::class)
        ->args([
            service(Connection::class),
            service(Configuration::class),
        ])
        ->public();

    // DBAL\Connection
    $services->set(Connection::class)
        ->factory([DriverManager::class, 'getConnection'])
        ->args([
            [
                'driver' => 'pdo_mysql',
                'host' => getenv('MYSQL_HOST'),
                'port' => getenv('MYSQL_PORT'),
                'dbname' => getenv('MYSQL_DATABASE'),
                'user' => getenv('MYSQL_USER'),
                'password' => getenv('MYSQL_PASSWORD'),
            ],
            service(Configuration::class),
        ])
        ->public();

    // Configuration
    $services->set(Configuration::class)
        ->call('setQueryCache', [service('doctrine.cache')])
        ->call('setResultCache', [service('doctrine.cache')])
        ->call('setMetadataCache', [service('doctrine.cache')])
        ->call('setProxyDir', ['%app.cache_dir%/doctrine/proxy'])
        ->call('setProxyNamespace', ['DoctrineProxies'])
        ->call('setAutoGenerateProxyClasses', [true])
        ->call('setNamingStrategy', [service(UnderscoreNamingStrategy::class)])
        ->call('setMetadataDriverImpl', [service(AttributeDriver::class)]);

    $services->set('doctrine.cache', FilesystemAdapter::class)
        ->arg('$directory', '%app.cache_dir%/doctrine/cache');

    $services->set(UnderscoreNamingStrategy::class);

    $services->set(AttributeDriver::class)
        ->args([['%app.project_dir%/src/Entity']]);

    // CLI commands dependencies
    $services->set(EntityManagerProvider::class, SingleManagerProvider::class)
        ->args([service(EntityManagerInterface::class)]);

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

    // Fixtures loader
    $services->set(FixturesLoader::class)
        ->args([
            service(ContainerInterface::class),
            ['%app.project_dir%/src/Fixture/']
        ]);

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

    // Fixtures command
    $services->set(FixturesLoadCommand::class)
        ->args([
            service(EntityManagerInterface::class),
            service(FixturesLoader::class),
        ])
        ->tag('console.command');
};
