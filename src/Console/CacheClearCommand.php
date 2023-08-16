<?php

declare(strict_types=1);

namespace App\Console;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'cache:clear', description: 'Clear application cache')]
class CacheClearCommand extends Command
{
    public function __construct(private readonly string $cacheDir, private readonly Filesystem $filesystem)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption(
            'env',
            'e',
            InputOption::VALUE_OPTIONAL,
            'Environment name',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cacheDir = dirname($this->cacheDir);

        if (!$this->filesystem->exists($cacheDir)) {
            throw new LogicException('Application cache not exists');
        }

        $env = $input->getOption('env');

        $envCache = $cacheDir . '/' . $env;

        if (!$this->filesystem->exists($envCache)) {
            throw new InvalidArgumentException(sprintf('Cache for %s environment not exists', $env));
        }

        $this->filesystem->remove($envCache);

        $io->success('Cache was successfully cleared');

        return Command::SUCCESS;
    }
}
