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

$routes->add('home', new Route('/', ['_controller' => function (): Response {
    return new Response('Hello, World!');
}]));

$routes->add('greeting', new Route('/greeting/{name?}', ['_controller' => function (Request $request): Response {
    $name = $request->attributes->get('name') ?? 'Guest';
    $content = sprintf('Hello, %s!', $name);

    return new Response($content);
}]));

$request = Request::createFromGlobals();
$context = new RequestContext();
$context->fromRequest($request);

$urlMatcher = new UrlMatcher($routes, $context);

try {
    $pathInfo = $context->getPathInfo();
    $parameters = $urlMatcher->match($pathInfo);
    $request->attributes->add($parameters);
    $handler = $request->attributes->get('_controller');
    $response = $handler($request);
} catch (ResourceNotFoundException $e) {
    $response = new Response('404 error', 404);
}

$response->send();
