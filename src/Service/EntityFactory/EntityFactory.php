<?php

declare(strict_types=1);

namespace App\Service\EntityFactory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Faker\Generator;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;

abstract class EntityFactory
{
    protected int $count = 1;
    protected array $state = [];
    protected bool $save = true;
    protected bool $savingDelay = false;
    protected array $beforeCreating = [];
    protected array $afterCreating = [];
    protected array $beforeSaving = [];
    protected array $afterSaving = [];

    public function __construct(
        protected readonly Generator $faker,
        protected readonly EntityManagerInterface $em
    ) {
    }

    public function state(array $state): static
    {
        $factory = clone $this;
        $factory->state = array_merge($this->state, $state);

        return $factory;
    }

    public function count(int $count): static
    {
        $factory = clone $this;
        $factory->count = $count;

        return $factory;
    }

    public function save(bool $value): static
    {
        $factory = clone $this;
        $factory->save = $value;

        return $factory;
    }

    public function savingDelay(bool $value): static
    {
        $factory = clone $this;
        $factory->savingDelay = $value;

        return $factory;
    }

    /**
     * @param callable $callback (array $state): array
     * @return $this
     */
    public function beforeCreating(callable $callback): static
    {
        $factory = clone $this;
        $factory->beforeCreating[] = $callback;

        return $factory;
    }

    /**
     * @param callable $callback (object $entity): void
     * @return static
     */
    public function afterCreating(callable $callback): static
    {
        $factory = clone $this;
        $factory->afterCreating[] = $callback;

        return $factory;
    }

    /**
     * @param callable $callback (object $entity): void
     * @return $this
     */
    public function beforeSaving(callable $callback): static
    {
        $factory = clone $this;
        $factory->beforeSaving[] = $callback;

        return $factory;
    }

    /**
     * @param callable $callback (object $entity): void
     * @return $this
     */
    public function afterSaving(callable $callback): static
    {
        $factory = clone $this;
        $factory->afterSaving[] = $callback;

        return $factory;
    }

    /**
     * @return object - entity or ArrayCollection with entities
     */
    public function create(): object
    {
        $entities = new ArrayCollection();

        $reflection = $this->getClassReflection();

        for ($i = 0; $i < $this->count; $i++) {
            $entity = $this->createEntity();

            $entities->add(new EntityProxy($entity, $reflection));

            if ($this->save && !$this->savingDelay) {
                $this->saveEntity($entity);
            }
        }

        if ($this->save && $this->savingDelay) {
            $this->em->flush();

            foreach ($entities as $entity) {
                foreach ($this->afterSaving as $callback) {
                    $callback($entity);
                }
            }
        }

        if ($entities->count() === 1) {
            return $entities->first();
        }

        return $entities;
    }

    protected function getClassMetadata(): ClassMetadata
    {
        return $this->em->getClassMetadata($this->getClass());
    }

    protected function getClassReflection(): ReflectionClass
    {
        return $this->getClassMetadata()->getReflectionClass();
    }

    /**
     * @return string - Entity classname
     */
    abstract protected function getClass(): string;

    /**
     * @return array - Entity properties
     */
    abstract protected function getDefinition(): array;

    private function createEntity(): object
    {
        $state = array_merge($this->getDefinition(), $this->state);

        foreach ($this->beforeCreating as $callback) {
            $state = $callback($state);

            if (!is_array($state)) {
                throw new LogicException(
                    sprintf('Before creation callback must return map of "%s" properties', $this->getClass())
                );
            }
        }

        $state = $this->prepareState($state);

        $reflection = $this->getClassReflection();

        $parameters = [];
        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (isset($state[$name])) {
                $parameters[$name] = $state[$name];
                unset($state[$name]);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $parameters[$name] = $parameter->getDefaultValue();
                continue;
            }

            throw new InvalidArgumentException(
                sprintf('Not defined argument "%s" of "%s" constructor', $name, $this->getClass())
            );
        }

        $entity = $reflection->newInstance(...$parameters);

        foreach ($state as $name => $value) {
            if (!$reflection->hasProperty($name)) {
                throw new InvalidArgumentException(
                    sprintf('Property "%s" not exist in entity "%s"', $name, $this->getClass())
                );
            }

            $reflection->getProperty($name)->setValue($entity, $value);
        }

        foreach ($this->afterCreating as $callback) {
            $callback($entity);
        }

        return $entity;
    }

    private function prepareState(array $state): array
    {
        $values = [];
        foreach ($state as $name => $value) {
            if (is_callable($value)) {
                $value = $value();
            }

            if ($value instanceof self) {
                $value = $value
                    ->savingDelay($this->savingDelay)
                    ->save($this->save)
                    ->create();
            }

            if ($this->getClassMetadata()->hasAssociation($name)) {
                $this->setInverseBindForAssociation($name, $value);
            }

            $values[$name] = $value;
        }

        return $values;
    }

    private function setInverseBindForAssociation(string $property, mixed $value): void
    {
        $assocClass = $this->getClassMetadata()->getAssociationTargetClass($property);

        $metadata = $this->em->getClassMetadata($assocClass);

        foreach ($metadata->getAssociationNames() as $association) {
            $assocTargetClass = $metadata->getAssociationTargetClass($association);
            if (!is_a($this->getClass(), $assocTargetClass, true)) {
                continue;
            }

            array_unshift($this->afterCreating, static function ($entity) use ($metadata, $association, $value) {
                $reflection = $metadata->getReflectionClass();

                if (!$value instanceof Collection && $metadata->isCollectionValuedAssociation($association)) {
                    /** @var Collection $entities */
                    $entities = $reflection->getProperty($association)->getValue($value);
                    $entities->add($entity);
                    $entity = $entities;
                }

                $reflection->getProperty($association)->setValue($value, $entity);
            });
        }
    }

    private function saveEntity(object $entity): void
    {
        foreach ($this->beforeSaving as $callback) {
            $callback($entity);
        }

        $this->persistEntityWithAssociations($entity);

        if (!$this->savingDelay) {
            $this->em->flush();

            foreach ($this->afterSaving as $callback) {
                $callback($entity);
            }
        }
    }

    private function persistEntityWithAssociations(object $entity): void
    {
        if ($this->em->contains($entity) || $this->em->getUnitOfWork()->isScheduledForInsert($entity)) {
            return;
        }

        $this->em->persist($entity);

        $metadata = $this->em->getClassMetadata($entity::class);
        $reflection = $metadata->getReflectionClass();

        foreach ($metadata->getAssociationNames() as $association) {
            if (!$assocValue = $reflection->getProperty($association)->getValue($entity)) {
                continue;
            }

            if ($metadata->isSingleValuedAssociation($association)) {
                $assocValue = [$assocValue];
            }

            foreach ($assocValue as $assocEntity) {
                $this->persistEntityWithAssociations($assocEntity);
            }
        }
    }
}
