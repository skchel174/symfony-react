<?php

declare(strict_types=1);

namespace App\Service\ControllerResolver;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

class ControllerResolver implements ControllerResolverInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|ReflectionException
     */
    public function getController(Request $request): callable
    {
        if (!$controller = $request->attributes->get('_controller')) {
            throw new RuntimeException(sprintf('Not found controller for path "%s"', $request->getUri()));
        }

        if (is_string($controller)) {
            $controller = explode('::', $controller);
        }

        if (is_array($controller)) {
            $class = $controller[0];
            $method = $controller[1] ?? '__invoke';

            if (!$this->container->has($class)) {
                throw new RuntimeException(sprintf('Controller "%s" not exists', $class));
            }

            $controller = $this->container->get($class);

            if (!method_exists($controller, $method)) {
                throw new RuntimeException(sprintf('Controller "%s" does not have a method "%s"', $class, $method));
            }

            $controller = [$controller, $method];
        }

        if (!is_callable($controller)) {
            throw new RuntimeException(sprintf('Failed to resolve controller for URI "%s"', $request->getPathInfo()));
        }

        $arguments = $this->getArguments($request, $controller);

        return static function () use ($controller, $arguments) {
            return $controller(...$arguments);
        };
    }

    /**
     * @throws ReflectionException
     */
    public function getArguments(Request $request, callable $controller): array
    {
        $reflection = is_array($controller)
            ? new ReflectionMethod($controller[0], $controller[1])
            : new ReflectionFunction($controller);

        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if ($name === 'request') {
                $arguments[$name] = $request;
                continue;
            }

            if ($request->attributes->has($name)) {
                $arguments[$name] = $request->attributes->get($name);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[$name] = $parameter->getDefaultValue();
                continue;
            }

            throw new InvalidArgumentException(
                sprintf('Controller for URI "%s" requires a value for the argument "%s"', $request->getPathInfo(), $parameter->getName())
            );
        }

        return $arguments;
    }
}
