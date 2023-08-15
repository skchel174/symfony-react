<?php

declare(strict_types=1);

namespace Test\Unit\Service\ControllerResolver;

use App\Service\ControllerResolver\ControllerResolver;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetControllerTest extends TestCase
{
    /**
     * @dataProvider controllersProvider
     */
    public function testResolveObjectController(string|array $controller, string $class, object $instance): void
    {
        $request = new Request(attributes: [
            '_controller' => $controller,
            'email' => $email = 'demo@text.mail'
        ]);

        $container = $this->createContainer($class, $instance);

        $resolver = new ControllerResolver($container);
        $controller = $resolver->getController($request);

        $this->assertIsCallable($controller);
        $this->assertInstanceOf(Response::class, $controller());
        $this->assertEquals($email, $request->attributes->get('_email'));
        $this->assertEquals('Anonymous', $request->attributes->get('_name'));
    }

    public function testResolveFunctionController(): void
    {
        $controller = static function (Request $request, string $email, string $name = 'Anonymous') {
            $request->attributes->add([
                '_email' => $email,
                '_name' => $name,
            ]);

            return new Response();
        };

        $request = new Request(attributes: [
            '_controller' => $controller,
            'email' => $email = 'demo@text.mail'
        ]);

        $container = $this->createMock(ContainerInterface::class);

        $resolver = new ControllerResolver($container);
        $controller = $resolver->getController($request);

        $this->assertIsCallable($controller);
        $this->assertInstanceOf(Response::class, $controller());
        $this->assertEquals($email, $request->attributes->get('_email'));
        $this->assertEquals('Anonymous', $request->attributes->get('_name'));
    }

    public function testNotFoundControllerForPath(): void
    {
        $request = new Request();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Not found controller for path "%s"', $request->getUri()));

        $container = $this->createMock(ContainerInterface::class);

        $resolver = new ControllerResolver($container);
        $resolver->getController($request);
    }

    public function testControllerClassNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Controller "%s" not exists', $controller = 'NotExistController'));

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $resolver = new ControllerResolver($container);
        $resolver->getController(new Request(attributes: ['_controller' => $controller]));
    }

    public function testControllerMethodNotExists(): void
    {
        $controllerClass = MethodsController::class;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf('Controller "%s" does not have a method "%s"', $controllerClass, 'method')
        );

        $container = $this->createContainer($controllerClass, new $controllerClass());

        $resolver = new ControllerResolver($container);
        $request = new Request(attributes: ['_controller' => [$controllerClass, 'method']]);
        $resolver->getController($request);
    }

    public static function controllersProvider(): array
    {
        return [
            'string controller' => [
                MethodsController::class . '::index',
                MethodsController::class,
                new MethodsController(),
            ],

            'array controller' => [
                [MethodsController::class, 'index'],
                MethodsController::class,
                new MethodsController(),
            ],

            'invokable controller' => [
                InvokableController::class,
                InvokableController::class,
                new InvokableController(),
            ],
        ];
    }

    private function createContainer(string $controllerClass, object $controllerInstance): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with($controllerClass)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with($controllerClass)
            ->willReturn($controllerInstance);

        return $container;
    }
}
