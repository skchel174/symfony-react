<?php

declare(strict_types=1);

namespace App\Service\FixturesLoader;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class FixturesLoader extends Loader
{
    public function __construct(private readonly ContainerInterface $container, private readonly array $paths)
    {
    }

    public function load(): void
    {
        foreach ($this->paths as $path) {
            $this->loadFromDirectory($path);
        }
    }

    public function loadByName(string $name): void
    {
        $fixture = $this->createFixture($name);
        $this->addFixture($fixture);
    }

    protected function createFixture($class): FixtureInterface
    {
        if (!$this->container->has($class)) {
            throw new InvalidArgumentException(sprintf('Fixture "%s" not exists', $class));
        }

        $fixture = $this->container->get($class);

        if (!$fixture instanceof FixtureInterface) {
            throw new InvalidArgumentException(
                sprintf('Fixture "%s" not implement interface "%s"', $fixture::class, FixtureInterface::class)
            );
        }

        return $fixture;
    }
}
