# kote/router
Small PHP HTTP Router

Usage:

```php
$router = new \Kote\Router\Router();

$router->get('/', function () {
    echo "Welcome Home!";
});

$router->get('user/(.+)', function ($name) {
    echo "Hello, $name!";
});

$router->get('user/(.+)/info', function ($name) {
    echo "Your info, $name!";
});

$router->run();
```

You can also add global routing handler if you want to use your own handling service:
   
```php
\Kote\Router\Router::setGlobalHandler(function ($action, $args) {
    return container()->invoke($action, $args);
});
```

This will invoke global handler on all requests instead of calling action.
