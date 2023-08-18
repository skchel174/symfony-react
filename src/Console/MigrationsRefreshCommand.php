<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrations:refresh', description: 'Refresh all migrations')]
class MigrationsRefreshCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = $this->getApplication();

        $app->setAutoExit(false);

        $app->run(new ArrayInput([
            'command' => 'migrations:reset',
        ]), $output);

        $app->run(new ArrayInput([
            'command' => 'migrations:migrate',
            'version' => 'latest',
            '--no-interaction' => true,
            '--query-time' => true,
        ]), $output);

        return Command::SUCCESS;
    }
}
