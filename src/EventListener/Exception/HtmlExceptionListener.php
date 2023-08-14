<?php

declare(strict_types=1);

namespace App\EventListener\Exception;

use App\Event\ExceptionEvent;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpFoundation\Response;

readonly class HtmlExceptionListener
{
    public function __construct(private HtmlErrorRenderer $renderer)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $data = $this->renderer->render($event->getThrowable());
        $response = new Response($data->getAsString(), $data->getStatusCode(), $data->getHeaders());
        $event->setResponse($response);
    }
}
