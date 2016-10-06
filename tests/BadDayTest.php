<?php

namespace tests;

use Nerd\Framework\Routing\Router;
use PHPUnit\Framework\TestCase;
use tests\fixtures\TestRequest;

class BadDayTest extends TestCase
{
    /**
     * @expectedException \Nerd\Framework\Routing\RouterException
     */
    public function testWhenUrlPatternStartsWithSlash()
    {
        $router = new Router();
        $router->get('/somewhere', function () {
        });
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouterException
     */
    public function testInvalidRouteHandler()
    {
        $router = new Router();

        $router->get('/', null);
        $router->handle(TestRequest::make('GET', '/'));
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouterException
     */
    public function testNoRoutes()
    {
        $router = new Router();
        $router->handle(TestRequest::make('GET', '/'));
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouterException
     */
    public function testInvalidMiddlewareHandler()
    {
        $router = new Router();
        $router->get('/', function () {
        });
        $router->middleware('.*', null);
        $router->handle(TestRequest::make('GET', '/'));
    }
}
