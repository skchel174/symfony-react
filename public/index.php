<?php

declare(strict_types=1);

use App\DependencyInjection\ContainerFactory;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;

define('PROJECT_DIR', dirname(__DIR__));

require_once PROJECT_DIR . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->usePutenv()->loadEnv(PROJECT_DIR . '/.env');

$errorHandler = new ErrorHandler(new BufferingLogger(), (bool) getenv('APP_DEBUG'));
ErrorHandler::register($errorHandler);

$containerFactory = new ContainerFactory(
    (bool) getenv('APP_DEBUG'),
    getenv('APP_ENV'),
    PROJECT_DIR
);
$container = $containerFactory->createContainer();

/** @var Kernel $kernel */
$kernel = $container->get(Kernel::class);
$response = $kernel->handleRequest(Request::createFromGlobals());
$response->send();
