<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Module\Providers\ModuleServiceProvider;

$module = dirname(__DIR__);

return Application::configure(basePath: dirname($module))
    ->withRouting(
        web: __DIR__.'/../../routes/web.php',
        commands: __DIR__.'/../../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([ModuleServiceProvider::class], false)
    ->create()
    ->useConfigPath($module.'/config')
    ->useBootstrapPath(__DIR__)
    ->usePublicPath($module.'/public')
    ->useStoragePath($module.'/storage')
    ->useAppPath(dirname($module).'/src');
