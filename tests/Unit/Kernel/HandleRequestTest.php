<?php

declare(strict_types=1);

namespace Test\Unit\Kernel;

use App\Event\ExceptionEvent;
use App\Event\RequestEvent;
use App\Event\ResponseEvent;
use App\Kernel;
use App\Service\ControllerResolver\ControllerResolverInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRequestTest extends TestCase
{
    public function testHandleRequest(): void
    {
        $request = new Request();
        $response = new Response();

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg instanceof RequestEvent))

            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg instanceof ResponseEvent));

        $resolver = $this->createMock(ControllerResolverInterface::class);
        $resolver->expects($this->once())
            ->method('getController')
            ->with($request)
            ->willReturn(fn () => $response);

        $kernel = new Kernel($dispatcher, $resolver);
        $result = $kernel->handleRequest($request);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($result, $response);
    }

    public function testWhenRequestEventHasResponse(): void
    {
        $request = new Request();
        $response = new Response();

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function ($arg) use ($response) {
                $arg->setResponse($response);
                return $arg instanceof RequestEvent;
            }))

            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg instanceof ResponseEvent));

        $resolver = $this->createMock(ControllerResolverInterface::class);
        $resolver->expects($this->never())
            ->method('getController');

        $kernel = new Kernel($dispatcher, $resolver);
        $result = $kernel->handleRequest($request);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($result, $response);
    }

    public function testHandleException(): void
    {
        $request = new Request();
        $response = new Response();

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg instanceof RequestEvent))

            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function ($arg) use ($response) {
                $arg->setResponse($response);
                return $arg instanceof ExceptionEvent;
            }))

            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg instanceof ResponseEvent));

        $resolver = $this->createMock(ControllerResolverInterface::class);
        $resolver->expects($this->once())
            ->method('getController')
            ->willThrowException(new RuntimeException());

        $kernel = new Kernel($dispatcher, $resolver);
        $result = $kernel->handleRequest($request);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($result, $response);
    }
}
