# kote-router
[![Build Status](https://travis-ci.org/kote-framework/kote-router.svg?branch=master)](https://travis-ci.org/kote-framework/kote-router)
[![Code Climate](https://codeclimate.com/github/kote-framework/kote-router/badges/gpa.svg)](https://codeclimate.com/github/kote-framework/kote-router)    [![Issue Count](https://codeclimate.com/github/kote-framework/kote-router/badges/issue_count.svg)](https://codeclimate.com/github/kote-framework/kote-router)

## Examples

```php
$router = new \Kote\Router\Router();

// Define routes
$router->get('/', function () { return "Welcome Home!"; });

$router->get('user/([a-z]+)', function ($name) { return "Hello, $name!"; });

$router->post('user/([a-z]+)', function ($name) { return "Hello from POST method!"; });

// Add middleware
$router->middleware('.*', function ($next) {
    $result  = "<h3>This is global middleware</h3>";
    $result .= $next();
    return $result;
});

$router->middleware('user/([a-z]+)', function ($next, $name) {
    if ($name == "admin") {
        return "<h1>Access denied!</h1>";
    }
    return $next();
});

$result = $router->run();

echo $result;
```

## Global handlers example
   
```php
\Kote\Router\Router::setGlobalRouteHandler(function ($action, $args) {
    return myRouteHandler($action, $args);
});

\Kote\Router\Router::setGlobalMiddlewareHandler(function ($action, $next, $args) {
    return myMiddlewareHandler($action, $next, $args);
});
```
