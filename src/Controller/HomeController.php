<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController
{
    #[Route('/', 'home')]
    public function index(): Response
    {
        return new Response('Hello, World!');
    }

    #[Route('/greeting/{name}', 'greeting')]
    public function greeting(string $name = 'Guest'): Response
    {
        return new Response(sprintf('Hello, %s!', $name));
    }
}
