<?php

declare(strict_types=1);

namespace Test\Unit\ControllerResolver;

use App\Controller\HomeController;
use App\ControllerResolver\ControllerResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ControllerResolverTest extends TestCase
{
    /**
     * @dataProvider controllersProvider
     */
    public function testGetController(array $attributes): void
    {
        $request = new Request(attributes: $attributes);

        $resolver = new ControllerResolver();
        $controller = $resolver->getController($request);

        $this->assertIsCallable($controller);
        $this->assertInstanceOf(Response::class, $controller($request));
    }

    public function testEmptyRequestParameters(): void
    {
        $resolver = new ControllerResolver();
        $controller = $resolver->getController(new Request());

        $this->assertFalse($controller);
    }

    public function testNotExistsClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Controller "%s" not exists', $controller = 'InvalidController'));

        $resolver = new ControllerResolver();
        $resolver->getController(new Request(attributes: ['_controller' => $controller]));
    }

    public function testNotExistsMethod(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Controller "%s" does not have a method "%s"', HomeController::class, 'method'));

        $resolver = new ControllerResolver();
        $request = new Request(attributes: ['_controller' => [HomeController::class, 'method']]);
        $resolver->getController($request);
    }

    public function testResolveNotInvokable(): void
    {
        $request = new Request(attributes: ['_controller' => new stdClass()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Failed to resolve controller for URI "%s"', $request->getPathInfo()));

        $resolver = new ControllerResolver();
        $resolver->getController($request);
    }

    public static function controllersProvider(): array
    {
        return [
            'string controller' => [['_controller' => MethodsController::class . '::index']],
            'array controller' => [['_controller' => [MethodsController::class, 'index']]],
            'invokable controller' => [['_controller' => InvokableController::class]],
            'callable controller' => [['_controller' => fn () => new Response()]],
        ];
    }
}
