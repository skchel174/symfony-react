<?php

declare(strict_types=1);

namespace App\Console;

use DirectoryIterator;
use InvalidArgumentException;
use LogicException;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'clear:cache', description: 'Clear application cache')]
class ClearCacheCommand extends Command
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

        if (!$env = $input->getOption('env')) {
            $answer = $io->askQuestion(new Question(
                sprintf('Are you sure you want to clean the entire directory %s? (yes/no)', $cacheDir),
                'no'
            ));

            if (!str_starts_with($answer, 'y')) {
                return Command::SUCCESS;
            }

            foreach (new DirectoryIterator($cacheDir) as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if (!$fileInfo->isDot()) {
                    $this->filesystem->remove($fileInfo->getRealPath());
                }
            }

            $io->success('Cache was successfully cleared');

            return Command::SUCCESS;
        }

        $envCache = $cacheDir . '/' . $env;

        if (!$this->filesystem->exists($envCache)) {
            throw new InvalidArgumentException(sprintf('Cache for %s environment not exists', $env));
        }

        $this->filesystem->remove($envCache);

        $io->success(sprintf('Cache for %s environment was successfully cleared', $env));

        return Command::SUCCESS;
    }
}
