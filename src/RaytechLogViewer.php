<?php

namespace Raytech\LogViewer;

use Raytech\LogViewer\Services\LogFileManager;
use Raytech\LogViewer\Services\LogParser;

class RaytechLogViewer
{
    /**
     * Current package version.
     */
    public const VERSION = '1.2.5';

    /**
     * Optional custom access restriction callback(s) or middleware(s).
     *
     * @var callable|string|array|bool|null
     */
    protected static $accessRestriction = null;

    /**
     * Get the current package version.
     *
     * @return string
     */
    public static function version(): string
    {
        return self::VERSION;
    }

    /**
     * Set access restriction callback or middleware.
     * Can be:
     *  - null: use config or allow all
     *  - false: explicitly allow all without restrictions
     *  - string: single middleware ("auth") or comma-separated ("admin,web")
     *  - array: ['admin', 'web'] or mixed array with closures
     *  - closure: function($request, $next) { ... }
     *
     * @param callable|string|array|bool|null $restriction
     * @return void
     */
    public static function auth($restriction): void
    {
        static::$accessRestriction = $restriction;
    }

    /**
     * Alias for auth().
     *
     * @param callable|string|array|bool|null $restriction
     * @return void
     */
    public static function setAccessRestriction($restriction = null): void
    {
        static::$accessRestriction = $restriction;
    }

    /**
     * Explicit helper to allow access to all without any middleware restrictions.
     *
     * @return void
     */
    public static function allowAll(): void
    {
        static::$accessRestriction = false;
    }

    /**
     * Append one or multiple middlewares to the existing access restrictions.
     *
     * @param callable|string|array $restriction
     * @return void
     */
    public static function addAccessRestriction($restriction): void
    {
        if (static::$accessRestriction === null || static::$accessRestriction === false) {
            static::$accessRestriction = is_array($restriction) ? $restriction : [$restriction];
        } elseif (is_array(static::$accessRestriction)) {
            if (is_array($restriction)) {
                static::$accessRestriction = array_merge(static::$accessRestriction, $restriction);
            } else {
                static::$accessRestriction[] = $restriction;
            }
        } else {
            static::$accessRestriction = [
                static::$accessRestriction,
                ...(is_array($restriction) ? $restriction : [$restriction]),
            ];
        }
    }

    /**
     * Retrieve current programmatic access restriction.
     *
     * @return callable|string|array|bool|null
     */
    public static function getAccessRestriction()
    {
        return static::$accessRestriction;
    }

    /**
     * Register a custom log file dynamically at runtime.
     *
     * @param string $path
     * @return void
     */
    public static function addLogFile(string $path): void
    {
        LogFileManager::registerFile($path);
    }

    /**
     * Register a custom log directory dynamically at runtime.
     *
     * @param string $directory
     * @return void
     */
    public static function addLogDirectory(string $directory): void
    {
        LogFileManager::registerDirectory($directory);
    }

    /**
     * Generate the URL for the log viewer.
     *
     * @param array $parameters
     * @return string
     */
    public static function url(array $parameters = []): string
    {
        $routeName = config('raytech-log-viewer.route.name', 'raytech-log-viewer.') . 'index';
        if (function_exists('route') && \Illuminate\Support\Facades\Route::has($routeName)) {
            return route($routeName, $parameters);
        }

        $prefix = config('raytech-log-viewer.route.prefix', 'raytech-log-viewer');
        $query = !empty($parameters) ? '?' . http_build_query($parameters) : '';
        return url($prefix . $query);
    }

    /**
     * Retrieve all detected log files.
     *
     * @return array
     */
    public static function allFiles(): array
    {
        return (new LogFileManager())->getAllLogFiles();
    }
}
