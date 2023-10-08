<?php

namespace App;

use App\Providers\RouteServiceProvider;
use App\Providers\ServiceProvider;
use App\Resource\JsonResource;
use App\Service\ControllerInfo;
use App\View\View;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class Application
{
    public ContainerBuilder $container;
    public Request $request;
    public ControllerInfo $controller_info;
    public null|Response $response = null;
    public RedirectResponse|View|JsonResource $content;

    public function __construct(Request $request)
    {
        $this->setConfig();
        $this->setRequest($request);
        $this->loadContainer();
        $this->getControllerInfo();
    }

    private function loadContainer()
    {
        $provider = new ServiceProvider(new ContainerBuilder(), $this->request);
        $provider->process();
        $this->container = $provider->getContainer();
    }

    private function getControllerInfo()
    {
        $context = $this->container->get(RequestContext::class);
        $matcher = $this->container->get(UrlMatcher::class);
        try {
            $route_parameters = $matcher->match($context->getPathInfo());
        } catch (ResourceNotFoundException) {
            $this->response = new Response('Not found!');
            return;
        } catch (MethodNotAllowedException) {
            $this->response = new Response('Method not allowed!');
            return;
        }

        $this->controller_info = new ControllerInfo($route_parameters);
        $this->controller_info->setReflectionParameters($this->container);
    }

    public function handle(): Response
    {
        $middlewares = $this->registerMiddlewares();
        $this->applyMiddlewares($middlewares);
        if ($this->hasResponse()) {
            return $this->response;
        }
        $this->registerControllerDefinition();

        try {
            $this->content = $this->container->get($this->controller_info->getController());
        } catch (ValidationFailedException) {
            $this->response = new Response('Validation errors');
            return $this->response;
        }
        if ($this->content instanceof RedirectResponse) {
            $this->response = $this->content;

            return $this->content;
        }

        return new Response();
    }

    public function terminate(Response $response)
    {
        if ($this->hasResponse()) {
            $this->response->send();
            return;
        }
        $response->headers = $this->getHeaders();
        $response->setContent($this->getContent());
        $response->setStatusCode(200);
        $response->send();
    }

    private function registerMiddlewares()
    {
        $route = RouteServiceProvider::getRoute($this->controller_info->getRouteName());
        $middlewares = array_filter($route->getOption('middlewares') ?? []) ?? [];

        foreach ($middlewares as $middleware) {
            $parameters = [];
            $definition = new Definition($middleware);
            $reflection_parameters = $this->container
                ->getReflectionClass($middleware)
                ->getMethod('next')
                ->getParameters();
            $this->provideServices($parameters, $reflection_parameters);

            $definition->addMethodCall('next', $parameters, true);
            $this->container->setDefinition($middleware, $definition);
        }

        return $middlewares;
//        $middleware = new Middleware($middlewares);
//        $this->response = $middleware($this->request);
    }

    private function applyMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            $response = $this->container->get($middleware);
            if ($response instanceof Response) {
                $this->response = $response;

                return;
            }
        }
    }

    private function registerControllerDefinition()
    {
        $controller = $this->controller_info->getController();
        $method = $this->controller_info->getMethod();
        $parameters = $this->controller_info->getParameters();

        $definition = new Definition($controller, [
            'em' => $this->container->get(EntityManager::class),
            'validator' => $this->container->get(RecursiveValidator::class),
        ]);
        $this->provideServices($parameters, $this->controller_info->getReflectionParameters());

        $definition->addMethodCall($method, $parameters, true);
        $this->container->setDefinition($controller, $definition);
    }

    private function provideServices(array &$parameters, array $reflection_parameters)
    {
        foreach ($reflection_parameters as $reflection_parameter) {
            $reflection_parameter = $reflection_parameter->getType()->getName();
            if ($this->container->has($reflection_parameter)) {
                $parameters[] = $this->container->get($reflection_parameter);
            }
        }
    }

    public function getHeaders()
    {
        $headers = [];
        $headers['Content-Type'] = $this->expectsJson()
            ? 'application/json'
            : 'text/html';

        return new ResponseHeaderBag($headers);
    }

    public function getContent()
    {
        return $this->expectsJson()
            ? $this->content->getJson()
            : $this->content->getHtml();
    }

    public function expectsJson()
    {
        return $this->content instanceof JsonResource;
    }

    public function hasResponse()
    {
        return $this->response instanceof Response;
    }

    private function setConfig()
    {
        new Config();
    }

    private function setRequest(Request $request)
    {
        $this->request = $request;
    }
}