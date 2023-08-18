<?php

declare(strict_types=1);

namespace App\Service\EntityFactory;

use ReflectionClass;

class EntityProxy
{
    public function __construct(private readonly object $entity, private readonly ReflectionClass $reflection)
    {
    }

    public function __get(string $name)
    {
        return $this->entity->$name;
    }

    public function __set(string $name, $value): void
    {
        $this->entity->$name = $value;
    }

    public function __isset(string $name): bool
    {
        return empty($this->entity->$name);
    }

    public function __call(string $name, array $arguments)
    {
        return $this->entity->$name(...$arguments);
    }

    public function forceSet(string $property, mixed $value): void
    {
        $this->reflection->getProperty($property)->setValue($this->entity, $value);
    }

    public function forceGet(string $property): mixed
    {
        return $this->reflection->getProperty($property)->getValue($this->entity);
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
