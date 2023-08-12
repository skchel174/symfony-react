<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\RequestEvent;
use RuntimeException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

readonly class RoutingListener
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        try {
            $context = new RequestContext();
            $context->fromRequest($request);
            $this->router->setContext($context);
            $parameters = $this->router->match($context->getPathInfo());

            $request->attributes->add($parameters);
        } catch (ResourceNotFoundException $e) {
            throw new RuntimeException(
                message: sprintf('Not route found for %s %s', $request->getMethod(), $request->getUri()),
                previous: $e
            );
        }
    }
}
