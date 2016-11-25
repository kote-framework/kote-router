<?php

namespace tests;

use Nerd\Framework\Http\Request\RequestContract;
use Nerd\Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

use function Nerd\Framework\Routing\RoutePatternMatcher\regex as r;
use function Nerd\Framework\Routing\RoutePatternMatcher\plain as p;
use function Nerd\Framework\Routing\RoutePatternMatcher\fast as f;

class RouterTest extends TestCase
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

    private function getRouter()
    {
        return new Router();
    }

    private function getRouterWithTestRoutes()
    {
        $router = $this->getRouter();

        $router->get(p('/'), function () {
            return 'test-get';
        });
        $router->post(p('test/post'), function () {
            return 'test-post';
        });
        $router->put(p('test/put'), function () {
            return 'test-put';
        });
        $router->delete(p('test/delete'), function () {
            return 'test-delete';
        });
        $router->any(p('test/any'), function () {
            return 'test-any';
        });

        $router->get(p('foo'), function () {
            return 'foo';
        });
        $router->get(p('bar/'), function () {
            return 'bar';
        });

        return $router;
    }

    public function testInstance()
    {
        $router = $this->getRouter();

        $this->assertInstanceOf(Router::class, $router);
    }

    public function testRouteHandling()
    {
        $router = $this->getRouterWithTestRoutes();

        $this->assertEquals('test-get', $router->handle($this->makeRequest('GET', '/')));
        $this->assertEquals('test-post', $router->handle($this->makeRequest('POST', 'test/post')));
        $this->assertEquals('test-put', $router->handle($this->makeRequest('PUT', 'test/put')));
        $this->assertEquals('test-delete', $router->handle($this->makeRequest('DELETE', 'test/delete')));

        $this->assertEquals('test-any', $router->handle($this->makeRequest('GET', 'test/any')));
        $this->assertEquals('test-any', $router->handle($this->makeRequest('POST', 'test/any')));
        $this->assertEquals('test-any', $router->handle($this->makeRequest('PUT', 'test/any')));
        $this->assertEquals('test-any', $router->handle($this->makeRequest('DELETE', 'test/any')));
    }

    public function testRouteSlashSuffix()
    {
        $router = $this->getRouterWithTestRoutes();

        $this->assertEquals('foo', $router->handle($this->makeRequest('GET', 'foo')));
        $this->assertEquals('bar', $router->handle($this->makeRequest('GET', 'bar/')));
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouteNotFoundException
     */
    public function testDocumentNotFound()
    {
        $router = $this->getRouterWithTestRoutes();

        $router->handle($this->makeRequest('GET', '404'));
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouteNotFoundException
     */
    public function testRouteSlashDoNotCompletion1()
    {
        $router = $this->getRouterWithTestRoutes();

        $router->handle($this->makeRequest('GET', 'foo/'));
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouteNotFoundException
     */
    public function testRouteSlashDoNotCompletion2()
    {
        $router = $this->getRouterWithTestRoutes();

        $router->handle($this->makeRequest('GET', 'bar'));
    }

    public function testRouteParams()
    {
        $router = $this->getRouter();

        $router->get(p('hello/:name'), function ($name) {
            return "Hello, $name";
        });

        $this->assertEquals('Hello, Sam', $router->handle($this->makeRequest('GET', 'hello/Sam')));
        $this->assertEquals('Hello, Bill', $router->handle($this->makeRequest('GET', 'hello/Bill')));
    }

    public function testMiddlewareFunction()
    {
        $router = $this->getRouter();

        $router->get(p('/'), function () {
            return 'bar';
        });

        $this->assertEquals('bar', $router->handle($this->makeRequest('GET', '/')));

        $router->middleware(p('/'), function ($next) {
            return 'foo' . $next();
        });

        $this->assertEquals('foobar', $router->handle($this->makeRequest('GET', '/')));
    }

    public function testMiddlewareRegExpParam()
    {
        $router = $this->getRouter();

        $router->get(p('profile/:param'), function () {
            return 'foo';
        });

        $router->middleware(p('profile/:param'), function ($param, $next) {
            if ($param == 'admin') {
                return 'bar';
            }
            return $next();
        });

        $this->assertEquals('foo', $router->handle($this->makeRequest('GET', 'profile/john-smith')));
        $this->assertEquals('bar', $router->handle($this->makeRequest('GET', 'profile/admin')));
    }

    public function testMiddlewareCascade()
    {
        $router = $this->getRouter();

        $middleware = function ($next) {
            return $next();
        };

        $router->get(p('/'), function () {
            return 'foo';
        });

        for ($i = 0; $i < 10; $i ++) {
            $router->middleware(p('/'), $middleware);
        }

        $this->assertEquals('foo', $router->handle($this->makeRequest('GET', '/')));
    }

    /**
     * @expectedException \Nerd\Framework\Routing\RouteNotFoundException
     */
    public function testHardRoutePattern()
    {
        $router = $this->getRouter();

        $router->get(r('foo-(\w+)-bar/(\w+)/abc/(\d+)'), function ($a, $b, $c) {
            return "$a-$b-$c";
        });

        $this->assertEquals('baz-buzz-15', $router->handle($this->makeRequest('GET', 'foo-baz-bar/buzz/abc/15')));

        $router->handle($this->makeRequest('GET', 'foo-baz-bar/buzz/abc/dd'));
    }

    public function testGlobalRouteHandler()
    {
        $router = $this->getRouter();

        Router::setGlobalRouteHandler(function ($action) {
            return 'global-' . $action();
        });

        $router->get(p('/'), function () {
            return 'home';
        });

        $this->assertEquals('global-home', $router->handle($this->makeRequest('GET', '/')));

        Router::setGlobalRouteHandler(null);
    }

    public function testGlobalMiddlewareHandler()
    {
        $router = $this->getRouter();

        Router::setGlobalMiddlewareHandler(function ($middleware, $args, $next) {
            return 'global-' . $middleware($next);
        });

        $router->middleware(p('/'), function ($next) {
            return 'middleware-' . $next();
        });

        $router->get(p('/'), function () {
            return 'home';
        });

        $this->assertEquals('global-middleware-home', $router->handle($this->makeRequest('GET', '/')));

        Router::setGlobalMiddlewareHandler(null);
    }
}
