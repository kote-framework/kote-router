# kote-router
Lightweight HTTP router with middleware support.

[![Code Climate](https://codeclimate.com/github/kote-framework/kote-router/badges/gpa.svg)](https://codeclimate.com/github/kote-framework/kote-router) 
[![Issue Count](https://codeclimate.com/github/kote-framework/kote-router/badges/issue_count.svg)](https://codeclimate.com/github/kote-framework/kote-router)

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
$router->middleware('.*', function ($next) {
    echo "<h3>This is global middleware</h3>";
    $next();
});

$router->middleware('user/([a-z]+)', function ($next, $name) {
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
    return container()->invoke($action, $next, $args);
});
```

This will delegate all actions to global handlers.
