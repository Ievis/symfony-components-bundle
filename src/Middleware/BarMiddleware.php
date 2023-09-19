<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BarMiddleware
{
    public function next(Request $request)
    {
        return new Response('Bar is angry!');
    }
}