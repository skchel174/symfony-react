<?php

declare(strict_types=1);

namespace Test\Unit\ControllerResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MethodsController
{
    public function index(Request $request, string $name = 'Anonymous'): Response
    {
        return new Response();
    }
}
