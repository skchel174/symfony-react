<?php

declare(strict_types=1);

use App\ControllerResolver\ArgumentsResolver;
use App\ControllerResolver\ControllerResolver;
use App\Event\RequestEvent;
use App\Event\ResponseEvent;
use App\EventListener\ProfilerSubscriber;
use App\EventListener\RoutingListener;
use App\Router\RouterFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

const ENV = 'dev';
const DEBUG = true;
define('PROJECT_DIR', dirname(__DIR__));

require_once PROJECT_DIR . '/vendor/autoload.php';

$parameters = new ParameterBag([
    'env' => ENV,
    'debug' => DEBUG,
    'project_dir' => PROJECT_DIR,
    'config_dir' => PROJECT_DIR . '/config',
    'cache_dir' => PROJECT_DIR . '/var/cache/' . ENV
]);

$container = new ContainerBuilder($parameters);
$container->register(RouterFactory::class, RouterFactory::class);
$container->register(RouterInterface::class, Router::class)
    ->setFactory(new Reference(RouterFactory::class))
        ->setArguments([
            '%project_dir%/src/Controller',
            '%cache_dir%',
            '%debug%',
        ]);
$container->register(EventDispatcherInterface::class, EventDispatcher::class);
$container->register(RoutingListener::class, RoutingListener::class)
    ->setArguments([new Reference(RouterInterface::class)]);
$container->register(ProfilerSubscriber::class, ProfilerSubscriber::class)
    ->setArguments(['%debug%']);

$eventDispatcher = $container->get(EventDispatcherInterface::class);
$eventDispatcher->addListener(RequestEvent::class, $container->get(RoutingListener::class));
$eventDispatcher->addSubscriber($container->get(ProfilerSubscriber::class));

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
