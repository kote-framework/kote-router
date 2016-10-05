<?php

namespace tests;

use Nerd\Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

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
        $router->handle('GET', '/');
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouterException
     */
    public function testNoRoutes()
    {
        $router = new Router();
        $router->handle('GET', '/');
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
        $router->handle('GET', '/');
    }
}
