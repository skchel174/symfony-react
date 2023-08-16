<?php

namespace App\DependencyInjection\Extension;

use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class ConsoleCommandsExtension extends Extension implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Application::class)) {
            return;
        }

        $commands = [];
        foreach ($container->findTaggedServiceIds('console.command') as $id => $tags) {
            $definition = $container->getDefinition($id);
            if (!is_subclass_of($definition->getClass(), Command::class)) {
                throw new InvalidArgumentException(
                    sprintf('Service "%s" with tag "console.command" must be a subclass of "%s"', $id, Command::class)
                );
            }
            $commands[] = new Reference($id);
        }

        $container
            ->getDefinition(Application::class)
            ->addMethodCall('addCommands', [$commands]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}
