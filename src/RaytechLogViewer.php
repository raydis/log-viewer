<?php

namespace Raytech\LogViewer;

use Raytech\LogViewer\Services\AssetCompiler;
use Raytech\LogViewer\Services\LogFileManager;
use Raytech\LogViewer\Services\LogParser;

class RaytechLogViewer
{
    /**
     * Current package version.
     */
    public const VERSION = '1.3.0';

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

    /**
     * Generate a cryptographic hash or encrypted string for password protection.
     *
     * @param string $plainText
     * @param string $algo 'bcrypt' (default), 'sha256', 'sha512', or 'encrypt'
     * @return string
     */
    public static function hashPassword(string $plainText, string $algo = 'bcrypt'): string
    {
        return match (strtolower($algo)) {
            'sha256' => hash('sha256', $plainText),
            'sha512' => hash('sha512', $plainText),
            'encrypt' => \Illuminate\Support\Facades\Crypt::encryptString($plainText),
            default => \Illuminate\Support\Facades\Hash::make($plainText),
        };
    }

    /**
     * Render the lightweight global hotkey / shortcut JavaScript snippet.
     *
     * @return string
     */
    public static function renderShortcutScript(): string
    {
        $enabled = (bool) config('raytech-log-viewer.shortcut.enabled', false);
        if (!$enabled) {
            return '';
        }

        $routePrefix = trim((string) config('raytech-log-viewer.route.prefix', 'log-viewer'), '/');
        $url = url($routePrefix);
        $key = strtolower((string) config('raytech-log-viewer.shortcut.key', 'x'));
        $ctrlRequired = config('raytech-log-viewer.shortcut.ctrl', true) ? 'true' : 'false';
        $shiftRequired = config('raytech-log-viewer.shortcut.shift', true) ? 'true' : 'false';
        $altRequired = config('raytech-log-viewer.shortcut.alt', false) ? 'true' : 'false';
        $target = config('raytech-log-viewer.shortcut.new_tab', true) ? '_blank' : '_self';

        $destroyKey = strtolower((string) config('raytech-log-viewer.security.destroy_shortcut.key', 'd'));
        $destroyEnabled = config('raytech-log-viewer.security.destroy_shortcut.enabled', true) ? 'true' : 'false';
        $logoutUrl = url($routePrefix . '/logout');
        $csrfToken = function_exists('csrf_token') ? csrf_token() : '';

        $js = <<<JS
            (function() {
                if (window.__raytechLogViewerShortcutBound) return;
                window.__raytechLogViewerShortcutBound = true;
                document.addEventListener('keydown', function(e) {
                    var ctrlMatch = {$ctrlRequired} ? (e.ctrlKey || e.metaKey) : (!e.ctrlKey && !e.metaKey);
                    var shiftMatch = {$shiftRequired} ? e.shiftKey : !e.shiftKey;
                    var altMatch = {$altRequired} ? e.altKey : !e.altKey;
                    var keyMatch = (e.key && e.key.toLowerCase() === '{$key}');

                    // Open Log Viewer in new tab
                    if (ctrlMatch && shiftMatch && altMatch && keyMatch) {
                        if (!window.location.pathname.includes('/{$routePrefix}')) {
                            e.preventDefault();
                            window.open('{$url}', '{$target}');
                        }
                    }

                    // Emergency Destroy Session & Close all log tabs (Ctrl+Shift+{$destroyKey})
                    if ({$destroyEnabled}) {
                        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key && e.key.toLowerCase() === '{$destroyKey}') {
                            e.preventDefault();
                            try {
                                if (window.BroadcastChannel) {
                                    new BroadcastChannel('log_viewer_sync').postMessage({ action: 'destroy_and_close' });
                                }
                                localStorage.setItem('log_viewer_session_destroyed', Date.now().toString());
                                localStorage.setItem('raytech_log_viewer_session_destroyed', Date.now().toString());
                                fetch('{$logoutUrl}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{$csrfToken}',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                }).catch(function() {});
                            } catch(err) {}
                        }
                    }
                });
            })();
            JS;

        $shouldEncrypt = (bool) config('raytech-log-viewer.shortcut.encrypt', true);
        if ($shouldEncrypt) {
            $encryptedJs = AssetCompiler::obfuscateJs($js);
            return "<script>{$encryptedJs}</script>";
        }

        return "<!-- RayTech Log Viewer Global Hotkey (Ctrl+Shift+{$key}) & Emergency Close (Ctrl+Shift+{$destroyKey}) -->\n<script>\n{$js}\n</script>";
    }
}
