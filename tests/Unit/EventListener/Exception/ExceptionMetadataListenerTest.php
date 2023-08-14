<?php

declare(strict_types=1);

namespace Test\Unit\EventListener\Exception;

use App\Event\ExceptionEvent;
use App\EventListener\Exception\ExceptionMetadataListener;
use App\Service\ExceptionMetadataResolver\ExceptionMetadata;
use App\Service\ExceptionMetadataResolver\ExceptionMetadataResolver;
use DomainException;
use PHPUnit\Framework\TestCase;

class ExceptionMetadataListenerTest extends TestCase
{
    public function testSetExceptionMetadataToEvent(): void
    {
        $e = new DomainException();

        $metadata = $this->createMock(ExceptionMetadata::class);

        $event = $this->createMock(ExceptionEvent::class);
        $event->expects($this->once())
            ->method('getThrowable')
            ->willReturn($e);
        $event->expects($this->once())
            ->method('setMetadata')
            ->with($metadata);

        $resolver = $this->createMock(ExceptionMetadataResolver::class);
        $resolver->expects($this->once())
            ->method('resolve')
            ->with($e)
            ->willReturn($metadata);

        $eventListener = new ExceptionMetadataListener($resolver);
        $eventListener($event);
    }
}