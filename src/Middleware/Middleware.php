<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Middleware implements MiddlewareInterface
{
    private null|Response $response = null;
    public bool $is_endless = false;
    public array $middlewares;

    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    public function __invoke(Request $request)
    {
        if($this->isEndless()) {
            return $this->response;
        }
        if ($this->nextIsPresent()) {
            $next = array_shift($this->middlewares);
            $next = new $next($this->middlewares);
            $this->response = $this->next($request, $next);
        } else {
            $this->is_endless = true;
            $this->response = $this->next($request, $this);
        }

        return $this->response;
    }

    public function next(Request $request, Middleware $next)
    {
        return $next($request);
    }

    public function hasResponse()
    {
        return $this->response instanceof Response;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function isEndless()
    {
        return $this->is_endless;
    }

    public function nextIsPresent()
    {
        return !empty($this->middlewares);
    }
}