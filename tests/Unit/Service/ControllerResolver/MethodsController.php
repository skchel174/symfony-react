<?php

declare(strict_types=1);

namespace Test\Unit\Service\ControllerResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MethodsController
{
    public function index(Request $request, string $email, string $name = 'Anonymous'): Response
    {
        $request->attributes->add([
            '_email' => $email,
            '_name' => $name,
        ]);

        return new Response();
    }
}
