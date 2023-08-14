<?php

declare(strict_types=1);

namespace Test\Unit\Service\ExceptionMetadataResolver;

use App\Service\ExceptionMetadataResolver\ExceptionMetadata;
use App\Service\ExceptionMetadataResolver\ExceptionMetadataResolver;
use DomainException;
use PHPUnit\Framework\TestCase;

class ResolveTest extends TestCase
{
    public function testResolveExceptionMetadata(): void
    {
        $resolver = new ExceptionMetadataResolver($this->getExceptionsMapping());
        $metadata = $resolver->resolve(new DomainException());

        $this->assertInstanceOf(ExceptionMetadata::class, $metadata);
        $this->assertEquals(422, $metadata->getStatusCode());
        $this->assertFalse($metadata->isHidden());
        $this->assertFalse($metadata->isLoggable());
        $this->assertEquals('info', $metadata->getLogLevel());
    }

    public function testResolveMetadataInstantiatedException(): void
    {
        $exception = new class() extends DomainException {};

        $resolver = new ExceptionMetadataResolver($this->getExceptionsMapping());
        $metadata = $resolver->resolve($exception);

        $this->assertInstanceOf(ExceptionMetadata::class, $metadata);
        $this->assertEquals(422, $metadata->getStatusCode());
        $this->assertFalse($metadata->isHidden());
        $this->assertFalse($metadata->isLoggable());
        $this->assertEquals('info', $metadata->getLogLevel());
    }

    public function testResolveMetadataByUnregisteredException(): void
    {
        $resolver = new ExceptionMetadataResolver();
        $metadata = $resolver->resolve(new DomainException());

        $this->assertInstanceOf(ExceptionMetadata::class, $metadata);
        $this->assertEquals(500, $metadata->getStatusCode());
        $this->assertTrue($metadata->isHidden());
        $this->assertTrue($metadata->isLoggable());
        $this->assertEquals('debug', $metadata->getLogLevel());
    }

    private function getExceptionsMapping(): array
    {
        return [
            DomainException::class => [
                'status_code' => 422,
                'hidden' => false,
                'loggable' => false,
                'log_level' => 'info',
            ],
        ];
    }
}
