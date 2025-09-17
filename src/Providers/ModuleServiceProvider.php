<?php

namespace Module\Providers;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadProviders();
        $this->mergeConfigFrom(__DIR__.'/../../config/module.php', 'module');
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadConsole();
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'module');
        $this->publishes([
            __DIR__.'/../../config/module.php' => config_path('module.php'),
            __DIR__.'/../../resources/views' => resource_path('views/vendor/module'),
        ]);
    }

    protected function loadProviders(): void
    {
        foreach (require __DIR__.'/../../.module/bootstrap/providers.php' as $provider) {
            $this->app->register($provider);
        }
    }

    protected function loadRoutes(): void
    {
        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');
    }

    protected function loadConsole(): void
    {
        $commands = collect([
            __DIR__.'/../../routes/console.php',
            __DIR__.'/../Console/Commands',
        ]);

        [$commands, $paths] = $commands->partition(fn ($command) => class_exists($command));
        [$routes, $paths] = $paths->partition(fn ($path) => is_file($path));

        $this->commands($commands->all());
        $routes->each(static fn ($path) => require_once $path);
        $this->app->booted(static fn ($app) => $app[Kernel::class]->addCommandPaths($paths->all()));
    }
}
