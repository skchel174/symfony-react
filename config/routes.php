<?php

use App\Controller\HomeController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('home', '/')
        ->controller([HomeController::class, 'index']);

    $routes->add('greeting', '/greeting/{name?}')
        ->controller([HomeController::class, 'greeting']);
};
