<?php

declare(strict_types=1);

namespace App\EventListener\Exception;

use App\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionListener
{
    public function __construct(private readonly bool $debug)
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
        $statusCode = $metadata->getStatusCode();
        $isHidden = $metadata->isHidden();

        $data = [
            'status' => $statusCode,
            'detail' => $isHidden ? Response::$statusTexts[$statusCode] : $e->getMessage(),
        ];

        if ($this->debug) {
            if ($isHidden) {
                $data['message'] = $e->getMessage();
            }
            $data['class'] = get_class($e);
            $data['code'] = $e->getCode();
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
            $data['trace'] = $e->getTrace();
        }

        $event->setResponse(new JsonResponse($data, $statusCode));
    }
}