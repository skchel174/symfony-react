<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'debug:router', description: 'Display list of routes.')]
class DebugRouterCommand extends Command
{
    public function __construct(private readonly RouterInterface $router)
    {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = [];
        foreach ($this->router->getRouteCollection() as $name => $route) {
            $rows[] = [
                $name,
                join('|', $route->getMethods()) ?: 'ANY',
                join('|', $route->getSchemes()) ?: 'ANY',
                $route->getHost() ?: 'ANY',
                $route->getPath(),
            ];
        }

        $io->table(['Name', 'Method', 'Scheme', 'Host', 'Path'], $rows);

        return Command::SUCCESS;
    }
}
