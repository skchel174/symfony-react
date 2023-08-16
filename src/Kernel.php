<?php

declare(strict_types=1);

namespace App;

use App\Event\RequestEvent;
use App\Event\ResponseEvent;
use App\Event\ExceptionEvent;
use App\Service\ControllerResolver\ControllerResolverInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Kernel
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ControllerResolverInterface $controllerResolver
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handleRequest(Request $request): Response
    {
        try {
            $requestEvent = new RequestEvent($request);
            $this->eventDispatcher->dispatch($requestEvent);

            if ($requestEvent->hasResponse()) {
                return $this->handleResponse($requestEvent->getResponse(), $request);
            }

            $request = $requestEvent->getRequest();
            $controller = $this->controllerResolver->getController($request);
            $response = $controller();

            return $this->handleResponse($response, $request);
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $request);
        }
    }

    private function handleResponse(Response $response, Request $request): Response
    {
        $event = new ResponseEvent($request, $response);
        $this->eventDispatcher->dispatch($event);

        return $event->getResponse();
    }

    /**
     * @throws Throwable
     */
    private function handleThrowable(Throwable $e, Request $request): Response
    {
        $errorEvent = new ExceptionEvent($e, $request);
        $this->eventDispatcher->dispatch($errorEvent);

        if (!$errorEvent->hasResponse()) {
            throw $e;
        }

        $response = $errorEvent->getResponse();

        return $this->handleResponse($response, $request);
    }
}
