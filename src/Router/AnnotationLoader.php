<?php

declare(strict_types=1);

namespace App\Router;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Route;

class AnnotationLoader extends AnnotationClassLoader
{
    protected function configureRoute(Route $route, ReflectionClass $class, ReflectionMethod $method, object $annot): void
    {
        if ($method->getName() === '__invoke') {
            $route->setDefault('_controller', $class->getName());
        } else {
            $route->setDefault('_controller', $class->getName() . '::' . $method->getName());
        }
    }

    protected function getDefaultRouteName(ReflectionClass $class, ReflectionMethod $method): string
    {
        $name = $class->getShortName();

        if ($method->getName() !== '__invoke') {
            $name .= ucfirst($method->getName());
        }

        preg_match_all('#([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)#', $name, $matches);

        return strtolower(join('_', $matches[1]));
    }
}
