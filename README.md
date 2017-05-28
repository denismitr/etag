# Laravel Etag Middleware
This a middleware specifically for the Laravel framework.
It hashaes the response content and adds it to ETag header of HTTP response.
Then it listenes to __If-Match__ and __If-Not-Match__ headers of the requests to see if a new content needs to be delivered.

## Author
__Denis Mitrofanov__

[TheCollection.ru](https://thecollection.ru)

## Installation
--------

Use composer to install the package:

```bash
composer require denismitr/etag
```

Include in your `app/Http/Kernel.php` to the appropriate section (all requests or named middleware):

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
        \Denismitr\Etags\ETagMiddleware::class
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
    'etag' => \Denismitr\Etags\ETagMiddleware::class,
];
```