<?php

declare(strict_types=1);

namespace Test\Unit\EventListener\Exception;

use App\Event\ExceptionEvent;
use App\EventListener\Exception\ExceptionLogListener;
use App\Service\ExceptionMetadataResolver\ExceptionMetadata;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ExceptionLogListenerTest extends TestCase
{
    public function testLogException(): void
    {
        $e = new RuntimeException('Test exception');

        $metadata = $this->createMock(ExceptionMetadata::class);
        $metadata->expects($this->once())
            ->method('isLoggable')
            ->willReturn(true);
        $metadata->expects($this->once())
            ->method('getLogLevel')
            ->willReturn($logLevel = 'debug');

        $event = $this->createMock(ExceptionEvent::class);
        $event->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $event->expects($this->once())
            ->method('getThrowable')
            ->willReturn($e);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(
                $logLevel,
                sprintf('%s: "%s" at %s line %s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()),
                ['trace' => $e->getTraceAsString()]
            );

        $eventListener = new ExceptionLogListener($logger);
        $eventListener($event);
    }

    public function testHandleNotLoggableException(): void
    {
        $metadata = $this->createMock(ExceptionMetadata::class);
        $metadata->expects($this->once())
            ->method('isLoggable')
            ->willReturn(false);
        $metadata->expects($this->never())
            ->method('getLogLevel');

        $event = $this->createMock(ExceptionEvent::class);
        $event->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $event->expects($this->never())
            ->method('getThrowable');

        $logger = $this->createMock(LoggerInterface::class);

        $eventListener = new ExceptionLogListener($logger);
        $eventListener($event);
    }
}
