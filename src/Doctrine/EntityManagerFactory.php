<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class EntityManagerFactory
{
    public function __construct(private readonly string $env)
    {
    }

    /**
     * @throws MissingMappingDriverImplementation|Exception
     */
    public function __invoke(array $params): EntityManagerInterface
    {
        $isDevMode = $this->env === 'dev';

        $cache = !$isDevMode
            ? new FilesystemAdapter(directory: $params['cache_dir'])
            : new ArrayAdapter();

        $config = ORMSetup::createAttributeMetadataConfiguration(
            $params['metadata'],
            $isDevMode,
            $params['proxy_dir'],
            $cache
        );

        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        $connection = DriverManager::getConnection($params['connection'], $config);

        return new EntityManager($connection, $config);
    }
}
