<?php

namespace App\Service\ControllerResolver;

use Symfony\Component\HttpFoundation\Request;

interface ArgumentsResolverInterface
{
    public function getArguments(Request $request, callable $controller): array;
}