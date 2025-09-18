<?php

namespace WeStacks\LaravelModule\Scripts;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class Installer
{
    public static function postCreateProject(): void
    {
        $projectName = basename(getcwd());
        $studly = Str::studly($projectName);   // MyProject
        $slug = Str::slug($projectName);     // my-project
        $snake = Str::snake($projectName);    // my_project

        self::updateComposerJson($studly, $slug);
        self::updatePackageJson($slug);
        self::updateViteConfig($slug);
        self::renameConfig($slug, $snake);
        self::updateServiceProvider($studly, $slug);
        self::updateSrcNamespace($studly, $slug);

        exec('rm -rf "'.__DIR__.'"');
        exec('composer dump-autoload');
    }

    protected static function updateComposerJson(string $studly, string $slug): void
    {
        $composer = json_decode(file_get_contents('composer.json'), true);

        $composer['autoload']['psr-4'] = [
            "{$studly}\\" => 'src/',
            "{$studly}\\Database\\Factories\\" => 'database/factories/',
            "{$studly}\\Database\\Seeders\\" => 'database/seeders/',
        ];

        $composer['autoload-dev']['psr-4'] = [
            "Tests\\" => 'tests/',
        ];

        $composer['extra']['laravel']['providers'] = ["{$studly}\\Providers\\ModuleServiceProvider"];

        $composer['name'] = "modules/{$slug}";

        file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected static function updatePackageJson(string $slug): void
    {
        if (! file_exists('package.json')) {
            return;
        }

        $package = json_decode(file_get_contents('package.json'), true);
        $package['name'] = "@modules/{$slug}";

        file_put_contents('package.json', json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected static function updateViteConfig(string $slug): void
    {
        if (! file_exists('vite.config.js')) {
            return;
        }

        $vite = file_get_contents('vite.config.js');
        $vite = str_replace('vendor/module', "vendor/{$slug}", $vite);

        file_put_contents('vite.config.js', $vite);
    }

    protected static function renameConfig(string $slug, string $snake): void
    {
        if (! file_exists('config/module.php')) {
            return;
        }

        $newPath = "config/{$slug}.php";

        $content = file_get_contents('config/module.php');
        $content = str_replace(
            ['MODULE_TABLE_PREFIX', 'module_'],
            [strtoupper($snake).'_TABLE_PREFIX', $snake.'_'],
            $content
        );

        file_put_contents($newPath, $content);
        unlink('config/module.php');
    }

    protected static function updateServiceProvider(string $studly, string $slug): void
    {
        $providerPath = 'src/Providers/ModuleServiceProvider.php';
        if (! file_exists($providerPath)) {
            return;
        }

        $content = file_get_contents($providerPath);

        $replacements = [
            "mergeConfigFrom(__DIR__.'/../../config/module.php', 'module');" => "mergeConfigFrom(__DIR__.'/../../config/{$slug}.php', '{$slug}');",
            "loadViewsFrom(__DIR__.'/../../resources/views', 'module');" => "loadViewsFrom(__DIR__.'/../../resources/views', '{$slug}');",
            "__DIR__.'/../../config/module.php' => config_path('module.php')" => "__DIR__.'/../../config/{$slug}.php' => config_path('{$slug}.php')",
            "__DIR__.'/../../resources/views' => resource_path('views/vendor/module')" => "__DIR__.'/../../resources/views' => resource_path('views/vendor/{$slug}')",
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        file_put_contents('src/Providers/ModuleServiceProvider.php', $content);
    }

    protected static function updateSrcNamespace(string $namespace, string $slug): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__.'/../../src', \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            self::updateFileNamespace($file, $namespace, $slug);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__.'/../../database', \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            self::updateFileNamespace($file, $namespace, $slug);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__.'/../../.module/bootstrap', \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            self::updateFileNamespace($file, $namespace, $slug);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__.'/../../routes', \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            self::updateFileNamespace($file, $namespace, $slug);
        }
    }

    protected static function updateFileNamespace(\SplFileInfo $file, string $namespace, string $slug): void
    {
        if ($file->getExtension() !== 'php' || dirname($file->getPathname()) === __DIR__) {
            return;
        }

        $content = file_get_contents($file->getPathname());
        $content = str_replace('namespace Module', "namespace {$namespace}", $content);
        $content = str_replace('use Module', "use {$namespace}", $content);
        $content = str_replace('module::', "$slug::", $content);

        file_put_contents($file->getPathname(), $content);
    }
}
