<?php

declare(strict_types=1);

namespace App\EventListener\Exception;

use App\Event\ExceptionEvent;
use Psr\Log\LoggerInterface;

readonly class ExceptionLogListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $metadata = $event->getMetadata();

        if (!$metadata->isLoggable()) {
            return;
        }

        $e = $event->getThrowable();

        $message = sprintf(
            '%s: "%s" at %s line %s',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        );

        $this->logger->log($metadata->getLogLevel(), $message, ['trace' => $e->getTraceAsString()]);
    }
}
