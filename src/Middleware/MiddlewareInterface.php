<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareInterface
{
    public function next(Request $request, Middleware $next);

    public function isEndless();

    public function nextIsPresent();

    public function hasResponse();

    public function getResponse();
}