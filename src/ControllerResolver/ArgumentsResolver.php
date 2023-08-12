<?php

declare(strict_types=1);

namespace App\ControllerResolver;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

class ArgumentsResolver
{
    /**
     * @throws ReflectionException
     */
    public function getArguments(Request $request, callable $controller): array
    {
        $arguments = [];

        $attributes = array_merge($request->attributes->all(), ['request' => $request]);
        $parameters = $this->getControllerParameters($controller);

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (isset($attributes[$name])) {
                $arguments[$name] = $attributes[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[$name] = $parameter->getDefaultValue();
                continue;
            }

            throw new InvalidArgumentException(sprintf(
                'Controller for URI "%s" requires that you provide a value for the "%s" argument',
                $request->getPathInfo(),
                $parameter->getName(),
            ));
        }

        return $arguments;
    }

    /**
     * @throws ReflectionException
     */
    private function getControllerParameters(callable $controller): array
    {
        if (is_array($controller)) {
            $reflection = new ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && !$controller instanceof Closure) {
            $reflection = new ReflectionMethod($controller, '__invoke');
        } else {
            $reflection = new ReflectionFunction($controller);
        }

        return $reflection->getParameters();
    }
}
