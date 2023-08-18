<?php

declare(strict_types=1);

namespace App\Console;

use App\Service\FixturesLoader\FixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'fixtures:load', description: 'Load fixtures')]
class FixturesLoadCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FixturesLoader $loader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'Name of fixture.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($name = $input->getArgument('name')) {
            $this->loader->loadByName($name);
        } else {
            $this->loader->load();
        }

        $executor = new ORMExecutor($this->em, new ORMPurger());
        $executor->setLogger(fn (string $message) => $io->text(sprintf('<info>%s</info>', $message)));
        $executor->execute($this->loader->getFixtures());

        return Command::SUCCESS;
    }
}
