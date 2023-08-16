<?php

declare(strict_types=1);

namespace App\Console;

use DirectoryIterator;
use InvalidArgumentException;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'clear:log', description: 'Clear application log')]
class ClearLogCommand extends Command
{
    public function __construct(private readonly string $logDir, private readonly Filesystem $filesystem)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument(
            'file',
            InputArgument::IS_ARRAY,
            'Names of log files'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $files = $input->getArgument('file');

        if (empty($files)) {
            $question = new Question('Are you sure you want to clear all contents of the log directory [y/n]?');

            if ($io->askQuestion($question) !== 'y') {
                return Command::FAILURE;
            }

            foreach (new DirectoryIterator($this->logFile) as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if (!$fileInfo->isDot()) {
                    $this->filesystem->remove($fileInfo->getRealPath());
                }
            }

            $io->success('Log directory was successfully cleared');

            return Command::SUCCESS;
        }

        foreach ($files as $file) {
            $logFile = $this->logDir . '/' . $file;

            if (!$this->filesystem->exists($logFile)) {
                throw new InvalidArgumentException(sprintf('File %s not exists', $logFile));
            }

            file_put_contents($logFile, '');

            $io->success(sprintf('Log file %s was successfully cleared', $file));
        }

        return Command::SUCCESS;
    }
}
