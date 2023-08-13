<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

readonly class ContainerFactory
{
    public function __construct(protected bool $debug, protected string $env, protected string $projectDir)
    {
    }

    public function createContainer(): ContainerInterface
    {
        $containerFile = $this->getContainerCacheFile();

        $cache = new ConfigCache($containerFile, $this->debug);

        if (!$cache->isFresh()) {
            $this->compileContainer($cache);
        }

        require_once $containerFile;

        $containerClass = $this->getContainerClass();

        return new $containerClass();
    }

    protected function getConfigDir(): string
    {
        return $this->projectDir . '/config';
    }

    protected function getCacheDir(): string
    {
        return $this->projectDir . '/var/cache';
    }

    protected function getExtensionsFile(): string
    {
        return $this->getConfigDir() . '/extensions.php';
    }

    protected function getContainerClass(): string
    {
        return ucfirst($this->env) . ($this->debug ? 'Debug' : '') . 'Container';
    }

    protected function getContainerCacheFile(): string
    {
        return $this->getCacheDir() . '/container/' . $this->getContainerClass() . '.php';
    }

    protected function getProjectParameters(): array
    {
        return [
            'env' => $this->env,
            'debug' => $this->debug,
            'project_dir' => $this->projectDir,
            'config_dir' => $this->getConfigDir(),
            'cache_dir' => $this->getCacheDir(),
        ];
    }

    protected function registerConfiguration(ContainerBuilder $container): void
    {
        $fileLocator = new FileLocator($this->getConfigDir());
        $loader = new PhpFileLoader($container, $fileLocator, $this->env);

        $loader->import($this->getConfigDir() . '/*.php');

        if (is_dir($this->getConfigDir() . '/' . $this->env)) {
            $loader->import($this->getConfigDir() . '/' . $this->env . '/*.php');
        }
    }

    private function getContainerExtensions(): array
    {
        $extensions = [];
        if (file_exists($this->getExtensionsFile())) {
            $extensions = array_merge($extensions, require $this->getExtensionsFile());
        }

        return $extensions;
    }

    private function compileContainer(ConfigCache $cache): void
    {
        $parameters = new ParameterBag($this->getProjectParameters());

        $container = new ContainerBuilder($parameters);

        foreach ($this->getContainerExtensions() as $extension) {
            $container->registerExtension(new $extension());
        }

        $this->registerConfiguration($container);

        foreach ($container->getExtensions() as $extension) {
            if (empty($container->getExtensionConfig($extension->getAlias()))) {
                $container->loadFromExtension($extension->getAlias(), []);
            }
        }

        $container->compile();

        $dumper = new PhpDumper($container);
        $dump = $dumper->dump([
            'debug' => $this->debug,
            'class' => $this->getContainerClass(),
        ]);

        $cache->write($dump, $container->getResources());
    }
}
