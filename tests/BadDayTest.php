<?php

namespace tests;

use Nerd\Framework\Http\Request\RequestContract;
use Nerd\Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

class BadDayTest extends TestCase
{
    private function makeRequest($method, $path)
    {
        $request = $this->getMockBuilder(RequestContract::class)
                        ->setMethods([])
                        ->getMock();
        $request->method('getMethod')->willReturn($method);
        $request->method('getPath')->willReturn($path);
        return $request;
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouterException
     */
    public function testNoRoutes()
    {
        $router = new Router();
        $router->handle($this->makeRequest('GET', '/'));
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouterException
     * @expectedExceptionMessage Route could not be empty.
     */
    public function testTryToMakeEmptyRoute()
    {
        $router = new Router();

        $router->get('', function () {
            //
        });
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouterException
     * @expectedExceptionMessage Route could not start with '/' character.
     */
    public function testTryToMakeEmptyRouteFromSlash()
    {
        $router = new Router();

        $router->get('/something', function () {
            //
        });
    }
}
