<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController
{
    public function index(): Response
    {
        return new Response('Hello, World!');
    }

    public function greeting(Request $request): Response
    {
        $name = $request->attributes->get('name') ?? 'Guest';
        $content = sprintf('Hello, %s!', $name);

        return new Response($content);
    }
}
