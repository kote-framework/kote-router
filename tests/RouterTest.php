<?php

/**
 * @author Roman Gemini <roman_gemini@ukr.net>
 * @date 19.05.16
 * @time 19:25
 */
class RouterTest extends PHPUnit_Framework_TestCase
{
    private function getRouter()
    {
        return new \Kote\Router\Router();
    }

    private function getRouterWithTestRoutes()
    {
        $router = $this->getRouter();

        $router->get('/',                   function () { return 'test-get'; });
        $router->post('^test/post',         function () { return 'test-post'; });
        $router->put('^test/put',           function () { return 'test-put'; });
        $router->delete('^test/delete',     function () { return 'test-delete'; });
        $router->any('^test/any',           function () { return 'test-any'; });

        $router->get('foo',                 function () { return 'foo'; });
        $router->get('bar/',                function () { return 'bar'; });

        return $router;
    }

    public function testInstance()
    {
        $router = $this->getRouter();

        $this->assertInstanceOf(\Kote\Router\Router::class, $router);
    }

    public function testRouteHandling()
    {
        $router = $this->getRouterWithTestRoutes();

        $this->assertEquals('test-get',     $router->handle('GET', '/'));
        $this->assertEquals('test-post',    $router->handle('POST', 'test/post'));
        $this->assertEquals('test-put',     $router->handle('PUT', 'test/put'));
        $this->assertEquals('test-delete',  $router->handle('DELETE', 'test/delete'));

        $this->assertEquals('test-any',     $router->handle('GET', 'test/any'));
        $this->assertEquals('test-any',     $router->handle('POST', 'test/any'));
        $this->assertEquals('test-any',     $router->handle('PUT', 'test/any'));
        $this->assertEquals('test-any',     $router->handle('DELETE', 'test/any'));
    }

    public function testRouteSlashSuffix()
    {
        $router = $this->getRouterWithTestRoutes();

        $this->assertEquals('foo',          $router->handle('GET', 'foo'));
        $this->assertEquals('bar',          $router->handle('GET', 'bar/'));
    }

    /**
     * @expectedException \Kote\Router\RouteNotFoundException
     */
    public function testDocumentNotFound()
    {
        $router = $this->getRouterWithTestRoutes();

        $router->handle('GET', '404');
    }

    /**
     * @expectedException \Kote\Router\RouteNotFoundException
     */
    public function testRouteSlashDoNotCompletion1()
    {
        $router = $this->getRouterWithTestRoutes();

        $router->handle('GET', 'foo/');
    }

    /**
     * @expectedException \Kote\Router\RouteNotFoundException
     */
    public function testRouteSlashDoNotCompletion2()
    {
        $router = $this->getRouterWithTestRoutes();

        $router->handle('GET', 'bar');
    }

    public function testRouteParams()
    {
        $router = $this->getRouter();

        $router->get('^hello/(\w+)', function ($name) { return "Hello, $name"; });

        $this->assertEquals('Hello, Sam', $router->handle('GET', 'hello/Sam'));
        $this->assertEquals('Hello, Bill', $router->handle('GET', 'hello/Bill'));
    }

    public function testAddingMiddleware()
    {
        $router = $this->getRouter();

        $router->middleware('/', function () {});
    }

    public function testMiddlewareFunction()
    {
        $router = $this->getRouter();

        $router->get('.*', function () { return 'foo'; });

        $this->assertEquals('foo', $router->handle('GET', '/'));

        $router->middleware('^bar/.*', function ($next) {
            return 'bar';
        });

        $this->assertEquals('bar', $router->handle('GET', 'bar/baz'));
    }

    public function testMiddlewareRegExpParam()
    {
        $router = $this->getRouter();

        $router->get('.*', function () { return 'foo'; });

        $router->middleware('^profile/(.*)', function ($param, $next) {
            if ($param == 'admin') {
                return 'bar';
            }
            return $next();
        });

        $this->assertEquals('foo', $router->handle('GET', 'profile/john-smith'));
        $this->assertEquals('bar', $router->handle('GET', 'profile/admin'));
    }

    public function testMiddlewareCascade()
    {
        $router = $this->getRouter();

        $router->get('/',               function () { return 'foo'; });

        for ($i = 0; $i < 10; $i ++) {
            $router->middleware('/',    function ($next) { return $next(); });
        }

        $this->assertEquals('foo', $router->handle('GET', '/'));
    }

    public function testRoutesPriority()
    {
        $router = $this->getRouter();

        $router->get('stuff/\w+', function () { return 'stuff'; });
        $router->get('stuff/search', function () { return 'search'; });

        $this->assertEquals('stuff', $router->handle('GET', 'stuff/search'));

        $router->clear();

        $router->get('stuff/search', function () { return 'search'; });
        $router->get('stuff/\w+', function () { return 'stuff'; });

        $this->assertEquals('search', $router->handle('GET', 'stuff/search'));
    }

    /**
     * @expectedException \Kote\Router\RouteNotFoundException
     */
    public function testHardRoutePattern()
    {
        $router = $this->getRouter();

        $router->get('foo-(\w+)-bar/(\w+)/abc/(\d+)$', function ($a, $b, $c) { return "$a-$b-$c"; });

        $this->assertEquals('baz-buzz-15', $router->handle('GET', 'foo-baz-bar/buzz/abc/15'));

        $router->handle('GET', 'foo-baz-bar/buzz/abc/dd');
    }

    public function testGlobalRouteHandler()
    {
        $router = $this->getRouter();

        \Kote\Router\Router::setGlobalRouteHandler(function ($action) {
            return 'global-' . $action();
        });

        $router->get('/', function () { return 'home'; });

        $this->assertEquals('global-home', $router->handle('GET', '/'));

        \Kote\Router\Router::setGlobalRouteHandler(null);
    }

    public function testGlobalMiddlewareHandler()
    {
        $router = $this->getRouter();

        \Kote\Router\Router::setGlobalMiddlewareHandler(function ($middleware, $args, $next) {
            return 'global-' . $middleware($next);
        });

        $router->middleware('/', function ($next) { return 'middleware-' . $next(); });

        $router->get('/', function () { return 'home'; });

        $this->assertEquals('global-middleware-home', $router->handle('GET', '/'));

        \Kote\Router\Router::setGlobalMiddlewareHandler(null);
    }
}