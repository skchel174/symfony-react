#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\DependencyInjection\ContainerFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

$projectDir = dirname(__DIR__);

require_once $projectDir . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->usePutenv()->loadEnv($projectDir . '/.env');

$containerFactory = new ContainerFactory(
    (bool) getenv('APP_DEBUG'),
    getenv('APP_ENV'),
    $projectDir
);
$container = $containerFactory->createContainer();

/** @var Application $application */
$application = $container->get(Application::class);
$application->run();
