Laravel React Compiler Middleware
=================================

[![Latest Stable Version](http://img.shields.io/packagist/v/estey/laravel-react-middleware.svg)](https://packagist.org/packages/estey/laravel-react-middleware) [![Build Status](https://travis-ci.org/BradEstey/laravel-react-middleware.svg)](https://travis-ci.org/BradEstey/laravel-react-middleware) [![Coverage Status](https://coveralls.io/repos/github/BradEstey/laravel-react-middleware/badge.svg?branch=master)](https://coveralls.io/github/BradEstey/laravel-react-middleware?branch=master)

This is a [Laravel](https://laravel.com) middleware used to help with [React Server-size Rendering](https://facebook.github.io/react/docs/environments.html#node). This middleware makes an HTTP request to a local [Node.js](https://nodejs.org) server compiling the React application. It then injects the returned HTML into your Laravel view. Use [Blade Templates](https://laravel.com/docs/5.2/blade) as you would normally.

- [How it Works](#how-it-works)
- [Installation](#installation)
- [Configuration](#configuration)

How it Works
------------

First install Node.js on your production server or on a server on the same local network. Then write a small Node.js application to render your React application. For simple applications, you can use `ReactDOM.server.renderToString(App)`, for more complex React applications you may need to do something more like [this](https://github.com/reactjs/react-router/blob/master/docs/guides/ServerRendering.md).

On the Laravel side, build a Blade or plain PHP layout file as usual.

``` html
<!-- Stored in resources/views/layouts/master.blade.php -->

<html>
    <head>
        <title>{{ $title or 'Untitled Document' }}</title>
    </head>
    <body>
        <div id="app">
            {!! $content or '' !!}
        </div>
    </body>
</html>
```

Render your views in your controller method as usual.

``` php
return view('layouts.main', ['title' => 'Your title here.']);
```

Add this middleware to whichever routes contain a React app.

``` php
Route::get('/', ['middleware' => 'react', 'uses' => 'HomeController@index']);
```

From there, the React middleware will make a request to your Node.js compiler server and replace in the `$content` variable with the returned HTML.

Installation
------------

Install this package via [Composer](https://getcomposer.org).

``` bash
$ composer require estey/laravel-react-middleware
```

Add the service provider to your `config/app.php` file.

``` php
'providers' => [
    ...
    Estey\ReactMiddleware\ReactCompilerServiceProvider::class,
];
```

Add the middleware class to your `app/Http/Kernel.php` file.

``` php
protected $routeMiddleware = [
    ...
    'react' => \Estey\ReactMiddleware\CompileReact::class,
];
```

Configuration
-------------

To publish the configuration file, run:

``` bash
$ php artisan vendor:publish --provider="Estey\ReactMiddleware\ReactCompilerServiceProvider"
```

In the config file you can change the host and port of the compiler server and change the connection and response timeout times. The default behavior is for timeouts set be to `0`, which wait indefinitely. I highly recommend that you change these to be more aggressive.

---

To change the variable name that will contain the HTML and be passed to your view, use the first parameter of the middleware definition.

``` php
Route::get('/', ['middleware' => 'react:layout', 'uses' => 'HomeController@index']);
```

AJAX requests to this route will return the data array passed to your view as plain JSON. So the example above would return:

``` json
{ "title": "Your title here." }
```

To disable the JSON response on AJAX requests, pass "false" to the second parameter.

``` php
Route::get('/', ['middleware' => 'react:content,false', 'uses' => 'HomeController@index']);
```
