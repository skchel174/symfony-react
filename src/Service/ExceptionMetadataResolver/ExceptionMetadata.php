<?php

declare(strict_types=1);

namespace App\Service\ExceptionMetadataResolver;

class ExceptionMetadata
{
    private int $statusCode;
    private bool $hidden;
    private bool $loggable;
    private string $logLevel;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function isLoggable(): bool
    {
        return $this->loggable;
    }

    public function setLoggable(bool $loggable): void
    {
        $this->loggable = $loggable;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function setLogLevel(string $logLevel): void
    {
        $this->logLevel = $logLevel;
    }
}
