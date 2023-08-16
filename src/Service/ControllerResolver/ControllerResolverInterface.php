<?php

namespace App\Service\ControllerResolver;

use Symfony\Component\HttpFoundation\Request;

interface ControllerResolverInterface
{
    public function getController(Request $request): callable;
}
