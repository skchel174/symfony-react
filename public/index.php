<?php

declare(strict_types=1);

use App\ControllerResolver\ArgumentsResolver;
use App\ControllerResolver\ControllerResolver;
use App\Event\RequestEvent;
use App\Event\ResponseEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

const ENV = 'dev';
const DEBUG = true;
define('PROJECT_DIR', dirname(__DIR__));

require_once PROJECT_DIR . '/vendor/autoload.php';

// Initializing container

$containerCache = PROJECT_DIR . '/var/cache/' . ENV . '/container.php';

if (file_exists($containerCache)) {
    require_once $containerCache;
    $container = new ProjectServiceContainer();
} else {
    $container = new ContainerBuilder(new ParameterBag([
        'env' => ENV,
        'debug' => DEBUG,
        'project_dir' => PROJECT_DIR,
        'config_dir' => PROJECT_DIR . '/config',
        'cache_dir' => PROJECT_DIR . '/var/cache/' . ENV
    ]));

    $extensions = require_once PROJECT_DIR . '/config/extensions.php';

    foreach ($extensions as $extension) {
        $container->registerExtension(new $extension());
    }

    $fileLocator = new FileLocator(PROJECT_DIR . '/config');
    $loader = new PhpFileLoader($container, $fileLocator);
    $loader->load('services.php');

    foreach ($container->getExtensions() as $extension) {
        if (empty($container->getExtensionConfig($extension->getAlias()))) {
            // Add empty extension configuration array if configuration is missing
            $container->loadFromExtension($extension->getAlias(), []);
        }
    }

    $container->compile();

    $dumper = new PhpDumper($container);
    file_put_contents($containerCache, $dumper->dump());
}

// Handle request

$request = Request::createFromGlobals();

$eventDispatcher = $container->get(EventDispatcherInterface::class);

$requestEvent = new RequestEvent($request);
$eventDispatcher->dispatch($requestEvent);

if ($requestEvent->hasResponse()) {
    $response = $requestEvent->getResponse();
    $response->send();
    exit;
}

$controllerResolver = $container->get(ControllerResolver::class);
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
