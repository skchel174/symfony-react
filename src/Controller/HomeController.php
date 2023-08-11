<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController
{
    #[Route('/', 'home')]
    public function index(): Response
    {
        return new Response('Hello, World!');
    }

    #[Route('/greeting/{name?}', 'greeting')]
    public function greeting(Request $request): Response
    {
        $name = $request->attributes->get('name') ?? 'Guest';
        $content = sprintf('Hello, %s!', $name);

        return new Response($content);
    }
}
