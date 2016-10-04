# kote-router
[![Build Status](https://travis-ci.org/nerd-framework/nerd-routing.svg?branch=master)](https://travis-ci.org/nerd-framework/nerd-routing)
[![Coverage Status](https://coveralls.io/repos/github/nerd-framework/nerd-routing/badge.svg?branch=master)](https://coveralls.io/github/nerd-framework/nerd-routing?branch=master)


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
