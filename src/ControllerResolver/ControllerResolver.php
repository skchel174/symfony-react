<?php

declare(strict_types=1);

namespace App\ControllerResolver;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

readonly class ControllerResolver
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function getController(Request $request): callable|false
    {
        if (!$controller = $request->attributes->get('_controller')) {
            return false;
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

        return $controller;
    }
}
