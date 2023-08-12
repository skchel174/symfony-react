<?php

declare(strict_types=1);

use App\ControllerResolver\ArgumentsResolver;
use App\ControllerResolver\ControllerResolver;
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

    $controllerResolver = new ControllerResolver();
    $argumentsResolver = new ArgumentsResolver();

    $controller = $controllerResolver->getController($request);

    $arguments = $argumentsResolver->getArguments($request, $controller);
    $response = $controller(...$arguments);
} catch (ResourceNotFoundException $e) {
    $response = new Response('404 error', 404);
}

$response->send();
