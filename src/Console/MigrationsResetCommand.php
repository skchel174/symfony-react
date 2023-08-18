<?php

declare(strict_types=1);

namespace App\Console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'migrations:reset', description: 'Reset all migrations')]
class MigrationsResetCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->em->getConnection();
        $schema = $connection->createSchemaManager();
        $tables = $schema->listTableNames();

        foreach ($tables as $table) {
            $keys = $schema->listTableForeignKeys($table);
            foreach ($keys as $key) {
                $schema->dropForeignKey($key, $table);
            }
        }

        foreach ($tables as $table) {
            $schema->dropTable($table);
        }

        $io = new SymfonyStyle($input, $output);
        $io->success('Database was reset');

        return Command::SUCCESS;
    }
}
