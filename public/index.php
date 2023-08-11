<?php

declare(strict_types=1);

use App\Router\RouterFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

const ENV = 'dev';
const DEBUG = true;
define('PROJECT_DIR', dirname(__DIR__));
const CACHE_DIR = PROJECT_DIR . '/var/cache' . '/' . ENV;

require_once PROJECT_DIR . '/vendor/autoload.php';

try {
    $router = (new RouterFactory())(
        sprintf('%s/src/Controller/', PROJECT_DIR),
        CACHE_DIR,
        DEBUG
    );

    $request = Request::createFromGlobals();
    $context = new RequestContext();
    $context->fromRequest($request);
    $router->setContext($context);

    $pathInfo = $context->getPathInfo();
    $parameters = $router->match($pathInfo);
    $request->attributes->add($parameters);

    if (!$controller = $request->attributes->get('_controller')) {
        throw new RuntimeException(sprintf('Not found controller for path "%s"', $request->getUri()));
    }

    if (is_string($controller)) {
        $controller = explode('::', $controller);
    }

    if (is_array($controller)) {
        $method = $controller[1] ?? '__invoke';
        $controller = $controller[0];

        if (!class_exists($controller)) {
            throw new RuntimeException(sprintf('Controller "%s" not exists.', $controller));
        }

        if (!method_exists($controller, $method)) {
            throw new RuntimeException(sprintf('Controller "%s" does not have a method "%s"', $controller, $method));
        }

        $controller = [new $controller(), $method];
    }

    if (!is_callable($controller)) {
        throw new RuntimeException(sprintf('Failed to resolve controller for URI "%s"', $request->getPathInfo()));
    }

    $response = $controller($request);
} catch (ResourceNotFoundException $e) {
    $response = new Response('404 error', 404);
}

$response->send();
