<?php

declare(strict_types=1);

namespace App\EventListener\Exception;

use App\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class ApiExceptionListener
{
    public function __construct(private bool $debug)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $preferredFormat = $event->getRequest()->getPreferredFormat();

        if ($preferredFormat !== 'json') {
            return;
        }

        $e = $event->getThrowable();
        $metadata = $event->getMetadata();

        $data = [
            'status' => $metadata->getStatusCode(),
            'detail' => $metadata->isHidden() ? Response::$statusTexts[$metadata->getStatusCode()] : $e->getMessage(),
        ];

        if ($this->debug) {
            if ($metadata->isHidden()) {
                $data['message'] = $e->getMessage();
            }

            $data['class'] = get_class($e);
            $data['code'] = $e->getCode();
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
            $data['trace'] = $e->getTrace();
        }

        $event->setResponse(new JsonResponse($data, $metadata->getStatusCode()));
    }
}