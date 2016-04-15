<?php

namespace Estey\ReactMiddleware;

use Illuminate\Support\ServiceProvider;

class ReactCompilerServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/Config/react.php' => config_path('react.php')
        ]);
    }
}
