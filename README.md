# Laravel Etag Middleware for JSON APIs
This a middleware specifically for the Laravel framework and __RESTful APIs__ build with it.
It hashaes the response content and adds it to ETag header of HTTP response.
Then it listenes to __If-Match__ and __If-None-Match__ headers of the requests that expect to
receive json in response in order to see if a new content needs to be delivered or just 304 Not Modified response is sufficient.

## Author
__Denis Mitrofanov__

[TheCollection.ru](https://thecollection.ru)

## Installation
--------

Use composer to install the package:

```bash
composer require denismitr/etag
```

Include in your `app/Http/Kernel.php` to the appropriate section
(all requests if all your routes are API or named middleware + API middleware group to make it work for every api route
or just named middleware):

Global middleware
-------
```php
/**
 * The application's global HTTP middleware stack.
 *
 * These middleware are run during every request to your application.
 *
 * @var array
 */
protected $middleware = [
    ...
    \Denismitr\ETags\ETagMiddleware::class
];
```
Named middleware
---------------
```php
/**
 * The application's route middleware.
 *
 * These middleware may be assigned to groups or used individually.
 *
 * @var array
 */
protected $routeMiddleware = [
    ...
    'etag' => \Denismitr\ETags\ETagMiddleware::class,
];

/**
 * The application's route middleware groups.
 *
 * @var array
 */
protected $middlewareGroups = [
    'web' => [
        ...
    ],

    'api' => [
        ...
        'etag'
    ],
];
```