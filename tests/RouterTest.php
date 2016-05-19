<?php

/**
 * @author Roman Gemini <roman_gemini@ukr.net>
 * @date 19.05.16
 * @time 19:25
 */
class RouterTest extends PHPUnit_Framework_TestCase
{
    public function testRouterInstantiation()
    {
        $router = new \Kote\Router\Router();

        $this->assertInstanceOf(\Kote\Router\Router::class, $router);
    }

    public function testAddingRootRoute()
    {
        $router = new \Kote\Router\Router();

        $router->get('/',                   function () { return 'root'; });

        $this->assertEquals('root', $router->handle('GET', '/'));
    }

    public function testAddingManyRoutes()
    {
        $router = new \Kote\Router\Router();

        $router->get('^foo/bar',            function () { return 'foo-bar'; });
        $router->get('^bar/baz',            function () { return 'bar-baz'; });
        $router->post('^images/post',       function () { return 'posted-image'; });

        $this->assertEquals('foo-bar',      $router->handle('GET', 'foo/bar'));
        $this->assertEquals('bar-baz',      $router->handle('GET', 'bar/baz'));
        $this->assertEquals('posted-image', $router->handle('POST', 'images/post'));
    }

    public function testAddingAnyMethodRoute()
    {
        $router = new \Kote\Router\Router();

        $router->any('^foo/bar',            function () { return 'foo-bar'; });

        $this->assertEquals('foo-bar',      $router->handle('GET', 'foo/bar'));
        $this->assertEquals('foo-bar',      $router->handle('POST', 'foo/bar'));
        $this->assertEquals('foo-bar',      $router->handle('PUT', 'foo/bar'));
        $this->assertEquals('foo-bar',      $router->handle('DELETE', 'foo/bar'));
    }

    public function testRouteSlashSuffix()
    {
        $router = new \Kote\Router\Router();

        $router->get('^foo/bar',            function () { return 'slash-less'; });
        $router->get('^foo/bar/',           function () { return 'slashed'; });

        $this->assertEquals('slash-less',   $router->handle('GET', 'foo/bar'));
        $this->assertEquals('slashed',      $router->handle('GET', 'foo/bar/'));
    }

    /**
     * @expectedException \Kote\Router\RouteNotFoundException
     */
    public function testDocumentNotFound()
    {
        $router = new \Kote\Router\Router();

        $router->get('^doc', function () {  });

        $router->handle('GET', 'doc2');
    }

    /**
     * @expectedException \Kote\Router\MethodNotImplementedException
     */
    public function testMethodNotImplemented()
    {
        $router = new \Kote\Router\Router();

        $router->get('^doc', function () {  });

        $router->handle('POST', 'doc');
    }

    public function testRouteParams()
    {
        $router = new \Kote\Router\Router();

        $router->get('^hello/(\w+)', function ($name) { return "Hello, $name"; });

        $this->assertEquals('Hello, Sam', $router->handle('GET', 'hello/Sam'));
        $this->assertEquals('Hello, Bill', $router->handle('GET', 'hello/Bill'));
    }

    public function testAddingMiddleware()
    {
        $router = new \Kote\Router\Router();

        $router->middleware('/', function () {});
    }

    public function testMiddlewareFunction()
    {
        $router = new \Kote\Router\Router();

        $router->get('.*', function () { return 'foo'; });

        $this->assertEquals('foo', $router->handle('GET', '/'));

        $router->middleware('^bar/.*', function ($next) {
            return 'bar';
        });

        $this->assertEquals('bar', $router->handle('GET', 'bar/baz'));
    }

    public function testMiddlewareRegExpParam()
    {
        $router = new \Kote\Router\Router();

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
        $router = new \Kote\Router\Router();

        $router->get('/',               function () { return 'foo'; });

        for ($i = 0; $i < 10; $i ++) {
            $router->middleware('/',    function ($next) { return $next(); });
        }

        $this->assertEquals('foo', $router->handle('GET', '/'));
    }

    public function testRoutesPriority()
    {
        $router = new \Kote\Router\Router();

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
        $router = new \Kote\Router\Router();

        $router->get('foo-(\w+)-bar/(\w+)/abc/(\d+)$', function ($a, $b, $c) { return "$a-$b-$c"; });

        $this->assertEquals('baz-buzz-15', $router->handle('GET', 'foo-baz-bar/buzz/abc/15'));

        $router->handle('GET', 'foo-baz-bar/buzz/abc/dd');
    }
}