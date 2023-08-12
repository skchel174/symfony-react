<?php

declare(strict_types=1);

namespace Test\Unit\ControllerResolver;

use App\ControllerResolver\ArgumentsResolver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArgumentsControllerTest extends TestCase
{
    /**
     * @dataProvider controllersProvider
     */
    public function testGetArguments(callable $controller): void
    {
        $request = new Request(attributes: ['name' => $name = 'John Doe']);

        $resolver = new ArgumentsResolver();
        $arguments = $resolver->getArguments($request, $controller);

        $this->assertIsArray($arguments);
        $this->assertNotEmpty($arguments);
        $this->assertEquals($request, $arguments['request']);
        $this->assertEquals($name, $arguments['name']);
    }

    /**
     * @dataProvider controllersProvider
     */
    public function testGetDefaultArgument(callable $controller): void
    {
        $request = new Request();
        $resolver = new ArgumentsResolver();
        $arguments = $resolver->getArguments($request, $controller);

        $this->assertEquals('Anonymous', $arguments['name']);
    }

    public function testGetNotProvidedArgument(): void
    {
        $request = new Request();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Controller for URI "%s" requires that you provide a value for the "%s" argument',
            $request->getPathInfo(),
            'name',
        ));

        $resolver = new ArgumentsResolver();
        $resolver->getArguments($request, fn(string $name) => new Response());
    }

    public static function controllersProvider(): array
    {
        return [
            'array controller' => [[new MethodsController(), 'index']],
            'invokable controller' => [new InvokableController(), '__invoke'],
            'callable controller' => [fn (Request $request, string $name = 'Anonymous') => new Response()],
        ];
    }
}
