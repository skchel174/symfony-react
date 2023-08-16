<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(name: 'debug:events', description: 'Display event listeners.')]
class DebugEventsCommand extends Command
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'event',
            InputArgument::OPTIONAL,
            'Event name',
            null
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $event = $input->getArgument('event');

        if (!$this->eventDispatcher->getListeners($event)) {
            throw new \InvalidArgumentException(sprintf('Listeners for event "%s" not found', $event));
        }

        foreach ($this->eventDispatcher->getListeners($event) as $event => $listeners) {
            $this->renderEventTable($event, $listeners, $io);
        }

        return Command::SUCCESS;
    }

    private function renderEventTable(string $event, array $listeners, SymfonyStyle $io)
    {
        $io->block($event, style: 'fg=yellow');

        $rows = [];
        foreach ($listeners as $num => $listener) {
            $order = sprintf('#%d', $num + 1);

            $listenerName = $listener[1] === '__invoke'
                ? $listener[0]::class
                : $listener[0]::class . '::' . $listener[1];

            $priority = $this->eventDispatcher->getListenerPriority($event, $listener);

            $rows[] = [$order, $listenerName . '()', $priority];
        }

        $io->table(['Order', 'Callable', 'Priority'], $rows);
    }
}
