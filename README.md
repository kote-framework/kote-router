# kote/router
Small HTTP router with middleware support.

Example:

```php
$router = new \Kote\Router\Router();

// Define routes
$router->get('/', function () {
    echo "Welcome Home!";
});

$router->get('user/([a-z]+)', function ($name) {
    echo "Hello, $name!";
});

$router->post('user/([a-z]+)', function ($name) {
    // user update code
});

// Define middleware
$router->addMiddleware('.*', function ($next) {
    echo "<h3>This is global middleware</h3>";
    $next();
});

$router->addMiddleware('user/([a-z]+)', function ($next, $name) {
    if ($name == "admin") {
        echo "<h1>Access denied!</h1>";
        return;
    }
    $next();
});

$router->run();
```

You can also add global handlers for routes and middleware if you want to use your own handling service.

Example:
   
```php
\Kote\Router\Router::setGlobalRouteHandler(function ($action, $args) {
    return container()->invoke($action, $args);
});

\Kote\Router\Router::setGlobalMiddlewareHandler(function ($action, $next, $args) {
    container()->invoke($action, $args);
    next();
});
```

This will delegate all actions to global handlers.
