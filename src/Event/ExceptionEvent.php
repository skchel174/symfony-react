<?php

declare(strict_types=1);

namespace App\Event;

use App\Service\ExceptionMetadataResolver\ExceptionMetadata;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class ExceptionEvent extends RequestEvent
{
    private ExceptionMetadata $exceptionMetadata;

    public function __construct(private Throwable $throwable, Request $request)
    {
        parent::__construct($request);
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }

    public function setThrowable(Throwable $throwable): void
    {
        $this->throwable = $throwable;
    }

    public function getMetadata(): ExceptionMetadata
    {
        return $this->exceptionMetadata;
    }

    public function setMetadata(ExceptionMetadata $exceptionMetadata): void
    {
        $this->exceptionMetadata = $exceptionMetadata;
    }
}
