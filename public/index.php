<?php

declare(strict_types=1);

use App\DependencyInjection\ContainerFactory;
use App\Kernel;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;

const ENV = 'dev';
const DEBUG = true;
define('PROJECT_DIR', dirname(__DIR__));

require_once PROJECT_DIR . '/vendor/autoload.php';

ErrorHandler::register(new ErrorHandler(new BufferingLogger(), DEBUG));

$containerFactory = new ContainerFactory(DEBUG, ENV, PROJECT_DIR);
$container = $containerFactory->createContainer();

/** @var Kernel $kernel */
$kernel = $container->get(Kernel::class);
$response = $kernel->handleRequest(Request::createFromGlobals());
$response->send();
