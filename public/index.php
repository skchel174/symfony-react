<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

define('PROJECT_DIR', dirname(__DIR__));

require_once PROJECT_DIR . '/vendor/autoload.php';

$routes = new RouteCollection();

$routes->add('dummy', new Route('/', ['_controller' => function (): Response {
    return new Response('Hello, World!');
}]));

$request = Request::createFromGlobals();
$context = new RequestContext();
$context->fromRequest($request);

$urlMatcher = new UrlMatcher($routes, $context);

try {
    $parameters = $urlMatcher->match($context->getPathInfo());
    $handler = $parameters['_controller'];
    $response = $handler($request);
} catch (ResourceNotFoundException $e) {
    $response = new Response('404 error', 404);
}

$response->send();
