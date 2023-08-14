<?php

declare(strict_types=1);

namespace App\EventListener\Exception;

use App\Event\ExceptionEvent;
use App\Service\ExceptionMetadataResolver\ExceptionMetadataResolver;

readonly class ExceptionMetadataListener
{
    public function __construct(private ExceptionMetadataResolver $metadataResolver)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $metadata = $this->metadataResolver->resolve($event->getThrowable());

        $event->setMetadata($metadata);
    }
}
