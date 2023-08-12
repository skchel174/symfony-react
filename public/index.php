<?php

declare(strict_types=1);

use App\ControllerResolver\ArgumentsResolver;
use App\ControllerResolver\ControllerResolver;
use App\Event\RequestEvent;
use App\Event\ResponseEvent;
use App\EventListener\RoutingListener;
use App\Router\RouterFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

const ENV = 'dev';
const DEBUG = true;
define('PROJECT_DIR', dirname(__DIR__));
const CACHE_DIR = PROJECT_DIR . '/var/cache' . '/' . ENV;

require_once PROJECT_DIR . '/vendor/autoload.php';

$router = (new RouterFactory())(
    sprintf('%s/src/Controller/', PROJECT_DIR),
    CACHE_DIR,
    DEBUG
);

$eventDispatcher = new EventDispatcher();
$eventDispatcher->addListener(RequestEvent::class, new RoutingListener($router));

$request = Request::createFromGlobals();

$requestEvent = new RequestEvent($request);
$eventDispatcher->dispatch($requestEvent);

if ($requestEvent->hasResponse()) {
    $response = $requestEvent->getResponse();
    $response->send();
    exit;
}

$controllerResolver = new ControllerResolver();
$argumentsResolver = new ArgumentsResolver();

if (!$controller = $controllerResolver->getController($request)) {
    throw new RuntimeException(sprintf('Not found controller for path "%s"', $request->getUri()));
}

$arguments = $argumentsResolver->getArguments($request, $controller);
$response = $controller(...$arguments);

$responseEvent = new ResponseEvent($request, $response);
$eventDispatcher->dispatch($responseEvent);
$response = $responseEvent->getResponse();

$response->send();
