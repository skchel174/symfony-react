<?php

declare(strict_types=1);

namespace App\Service\ExceptionMetadataResolver;

use Throwable;

class ExceptionMetadataResolver
{
    public function __construct(private readonly array $exceptionsMapping = [])
    {
    }

    public function resolve(Throwable $e): ExceptionMetadata
    {
        $options = $this->findOptionsByType($e);

        $metadata = new ExceptionMetadata();
        $metadata->setStatusCode($options['status_code'] ?? 500);
        $metadata->setHidden($options['hidden'] ?? true);
        $metadata->setLoggable($options['loggable'] ?? true);
        $metadata->setLogLevel($options['log_level'] ?? 'debug');

        return $metadata;
    }

    private function findOptionsByType(Throwable $e): array
    {
        foreach ($this->exceptionsMapping as $class => $options) {
            if ($e instanceof $class) {
                return $options;
            }
        }

        return [];
    }
}
