<?php

declare(strict_types=1);

namespace App\Router;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class RouterFactory
{
    public function __invoke(string $resource, string $cacheDir, bool $debug): RouterInterface
    {
        $fileLocator = new FileLocator();
        $annotationLoader = new AnnotationLoader();
        $loader = new AnnotationDirectoryLoader($fileLocator, $annotationLoader);

        $options = [
            'cache_dir' => $cacheDir . '/router',
            'debug' => $debug,
        ];

        return new Router($loader, $resource, $options);
    }
}
