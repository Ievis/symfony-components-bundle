<?php

namespace App\Middleware;

use App\Entity\Repository\ScheduleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FooMiddleware
{
    public function next(Request $request, ScheduleRepository $repository)
    {

    }
}