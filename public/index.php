<?php

declare(strict_types=1);

use App\ControllerResolver\ArgumentsResolver;
use App\ControllerResolver\ControllerResolver;
use App\DependencyInjection\ContainerFactory;
use App\Event\RequestEvent;
use App\Event\ResponseEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

const ENV = 'dev';
const DEBUG = true;
define('PROJECT_DIR', dirname(__DIR__));

require_once PROJECT_DIR . '/vendor/autoload.php';

$containerFactory = new ContainerFactory(DEBUG, ENV, PROJECT_DIR);
$container = $containerFactory->createContainer();

$request = Request::createFromGlobals();

$eventDispatcher = $container->get(EventDispatcherInterface::class);

$requestEvent = new RequestEvent($request);
$eventDispatcher->dispatch($requestEvent);

if ($requestEvent->hasResponse()) {
    $response = $requestEvent->getResponse();
    $response->send();
    exit;
}

/** @var ControllerResolver $controllerResolver */
$controllerResolver = $container->get(ControllerResolver::class);

/** @var ArgumentsResolver $controllerResolver */
$argumentsResolver = $container->get(ArgumentsResolver::class);

if (!$controller = $controllerResolver->getController($request)) {
    throw new RuntimeException(sprintf('Not found controller for path "%s"', $request->getUri()));
}

$arguments = $argumentsResolver->getArguments($request, $controller);
$response = $controller(...$arguments);

$responseEvent = new ResponseEvent($request, $response);
$eventDispatcher->dispatch($responseEvent);
$response = $responseEvent->getResponse();

$response->send();
