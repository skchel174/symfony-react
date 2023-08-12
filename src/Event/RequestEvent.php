<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class RequestEvent extends Event
{
    private Response $response;

    public function __construct(private readonly Request $request)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function hasResponse(): bool
    {
        return isset($this->response);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;

        $this->stopPropagation();
    }
}
