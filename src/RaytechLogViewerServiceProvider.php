<?php

namespace Raytech\LogViewer;

use Illuminate\Support\ServiceProvider;
use Raytech\LogViewer\Console\ClearCommand;
use Raytech\LogViewer\Console\InstallCommand;
use Raytech\LogViewer\Console\UpdateCommand;
use Raytech\LogViewer\Console\UninstallCommand;
use Raytech\LogViewer\Services\LogFileManager;
use Raytech\LogViewer\Services\LogParser;

class RaytechLogViewerServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        // Recursively merge package configuration so newly added config keys
        // are always available even if the host application has an older published config file.
        $this->mergeConfigRecursively(
            __DIR__ . '/../config/raytech-log-viewer.php',
            'raytech-log-viewer'
        );

        // Bind singleton services
        $this->app->singleton('raytech-log-viewer', function () {
            return new RaytechLogViewer();
        });

        $this->app->singleton(LogFileManager::class, function () {
            return new LogFileManager();
        });

        $this->app->singleton(LogParser::class, function () {
            return new LogParser();
        });
    }

    /**
     * Recursively merge configuration with default package values.
     * Preserves user-defined config keys while automatically supplying newly added package defaults.
     *
     * @param string $path
     * @param string $key
     * @return void
     */
    protected function mergeConfigRecursively(string $path, string $key): void
    {
        $config = $this->app['config']->get($key, []);
        $default = require $path;
        $this->app['config']->set($key, array_replace_recursive($default, $config));
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register console commands & publishable resources
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/raytech-log-viewer.php' => config_path('raytech-log-viewer.php'),
            ], 'raytech-log-viewer-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/raytech-log-viewer'),
            ], 'raytech-log-viewer-views');

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/raytech-log-viewer'),
            ], 'raytech-log-viewer-assets');

            $this->commands([
                InstallCommand::class,
                UpdateCommand::class,
                UninstallCommand::class,
                ClearCommand::class,
            ]);
        }

        // Load package views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'raytech-log-viewer');

        // Load package routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
