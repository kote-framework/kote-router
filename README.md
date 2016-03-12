# kote/router
Small HTTP router with middleware support.

Example:

```php
$router = new \Kote\Router\Router();

// Define routes
$router->get('/', function () {
    echo "Welcome Home!";
});

$router->get('user/(.+)', function ($name) {
    echo "Hello, $name!";
});

$router->post('user/(.+)', function ($name) {
    // user update code
});

// Define middleware
$router->addMiddleware('.*', function ($next) {
    echo "<h3>This is global middleware</h3>";
    $next();
});

$router->addMiddleware('user/([a-z]+).*', function ($next, $name) {
    if ($name == "admin") {
        echo "<h1>Access denied!</h1>";
        return;
    }

    $next();
});

$router->run();
```

You can also add global routing handler if you want to use your own handling service.

Example:
   
```php
\Kote\Router\Router::setGlobalHandler(function ($action, $args) {
    return container()->invoke($action, $args);
});
```

This will invoke global handler on all requests instead of calling action.
