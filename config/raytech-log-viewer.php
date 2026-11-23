<?php

return [
    /*
     * |--------------------------------------------------------------------------
     * | RayTech Log Viewer Enabled
     * |--------------------------------------------------------------------------
     * |
     * | Set to false to temporarily disable the log viewer interface and API
     * | endpoints across the application.
     * |
     */
    'enabled' => env('LOG_VIEWER_ENABLED', true),

    /*
     * |--------------------------------------------------------------------------
     * | Route Configuration
     * |--------------------------------------------------------------------------
     * |
     * | Configure the URL prefix, route name prefix, domain, and middleware for
     * | accessing the log viewer.
     * |
     * | - prefix: The URI path for the viewer (e.g. 'log-viewer' or 'error-log')
     * | - middleware: Can be a single middleware string ('web'), an array of middlewares
     * |   (['web', 'auth']), or a comma-separated string ('web,auth,adminGard').
     * |   Leave empty or null to allow public access.
     * |
     */
    'route' => [
        'prefix' => env('LOG_VIEWER_ROUTE_PREFIX', 'log-viewer'),
        'name' => 'log-viewer.',
        'domain' => env('LOG_VIEWER_DOMAIN', null),
        'middleware' => env('LOG_VIEWER_MIDDLEWARE', 'web'),
    ],

    /*
     * |--------------------------------------------------------------------------
     * | UI Theme & Branding
     * |--------------------------------------------------------------------------
     * |
     * | Customize the visual look and feel of the log viewer interface:
     * | - default_mode: 'light' or 'dark' (users can also toggle in real-time)
     * | - primary_color: Primary brand color used for active accents, buttons, and badges
     * | - title: HTML page title
     * | - brand_name: Header branding text displayed in topbar
     * | - brand_logo: Path or URL to brand logo / browser title icon (default: 'vendor/log-viewer/img/default_logo.jpg')
     * | - back_url: Target URL when clicking the back button
     * | - back_url_name: Text label displayed on the back button
     * | - show_back_button: Whether to show the back button in topbar
     * |
     */
    'theme' => [
        'default_mode' => env('LOG_VIEWER_THEME', 'light'),
        'primary_color' => env('LOG_VIEWER_PRIMARY_COLOR', '#1f6feb'),
        'title' => env('LOG_VIEWER_TITLE', 'Log Viewer'),
        'brand_name' => env('APP_NAME', 'RayTech'),
        'brand_logo' => env('LOG_VIEWER_LOGO', 'vendor/raytech-log-viewer/img/default_logo.jpg'),
        'back_url' => env('LOG_VIEWER_BACK_URL', '/'),
        'back_url_name' => env('LOG_VIEWER_BACK_URL_NAME', 'Back To Home'),
        'show_back_button' => env('LOG_VIEWER_SHOW_BACK_BUTTON', true),
    ],

    /*
     * |--------------------------------------------------------------------------
     * | Log File Detection & Directories
     * |--------------------------------------------------------------------------
     * |
     * | Configure how and where the plugin discovers log files:
     * |
     * | - scan_laravel_logs: Automatically scan storage/logs/*.log
     * | - show_server_logs: Show or hide Server Logs in the sidebar (default: false)
     * | - scan_server_logs: Automatically scan server logs (WAMP, Apache, Nginx, PHP, MySQL)
     * | - scan_php_error_log: Dynamically read ini_get('error_log')
     * | - extra_directories: Additional directories to scan for *.log files
     * | - extra_files: Specific log file paths or names (comma-separated in .env supported)
     * | - ignore_files: File patterns to exclude from the viewer
     * |
     */
    'logs' => [
        'scan_laravel_logs' => true,
        // Show or hide "Server Logs" (WAMP, Apache, Nginx, PHP error logs, MySQL, etc.)
        // When true: discovers and shows Server Logs in sidebar. When false: hides Server Logs completely.
        'show_server_logs' => env('LOG_VIEWER_SHOW_SERVER_LOGS', false),
        'scan_server_logs' => env('LOG_VIEWER_SCAN_SERVER_LOGS', false),
        'scan_php_error_log' => false,
        // Add additional folder paths to scan for .log files:
        // E.g.: [storage_path('logs/custom'), '/var/log/my-daemon']
        'extra_directories' => array_filter(explode(',', (string) env('LOG_VIEWER_DIRS', ''))),
        // Add individual log file paths:
        // E.g.: ['worker.log', storage_path('logs/cron.log'), 'E:/wamp/logs/my_app.log']
        'extra_files' => array_filter(explode(',', (string) (env('LOG_VIEWER_FILES') ?: env('SERVER_LOG_FILES', '')))),
        // File names or extensions to ignore:
        'ignore_files' => [
            '.gitignore',
            '.gitkeep',
            '*.gz',
            '*.zip',
        ],
    ],

    /*
     * |--------------------------------------------------------------------------
     * | Features & Action Controls
     * |--------------------------------------------------------------------------
     * |
     * | Control what users can do in the viewer interface:
     * | - allow_download: Enable or disable downloading the raw log file
     * | - allow_clear: Enable or disable truncating/clearing log files
     * | - allow_raw: Enable or disable viewing unformatted raw log text in browser
     * | - allow_copy: Enable or disable the "Copy visible logs" button
     * | - default_lines: Default number of lines to display on initial load
     * | - lines_options: Available choices in the "Lines" dropdown
     * | - auto_refresh_default: Default auto-refresh interval in milliseconds (0 = off, 10000 = 10s)
     * | - max_read_bytes: Max bytes to read from file tail (prevents memory exhaustion on huge files)
     * |
     */
    'features' => [
        'allow_download' => env('LOG_VIEWER_ALLOW_DOWNLOAD', true),
        'allow_clear' => env('LOG_VIEWER_ALLOW_CLEAR', true),
        'allow_raw' => env('LOG_VIEWER_ALLOW_RAW', true),
        'allow_copy' => true,
        'default_lines' => 500,
        'lines_options' => [100, 250, 500, 1000, 2500, 5000],
        'auto_refresh_default' => 10000,
        'max_read_bytes' => 4194304,  // 4MB tail buffer
        'default_order' => env('RAYTECH_LOG_VIEWER_ORDER', 'desc'),  // 'desc' = latest log on top, 'asc' = oldest first
    ],

    /*
     * |--------------------------------------------------------------------------
     * | Global Keyboard Shortcut (Hotkey)
     * |--------------------------------------------------------------------------
     * |
     * | Allows developers to press a keyboard shortcut (e.g. Ctrl + Shift + X)
     * | anywhere in the Laravel application to instantly open the log viewer in a
     * | new browser tab without needing any visible links in the user interface!
     * |
     * | - enabled: Enable or disable the global shortcut (default: false)
     * | - auto_inject: Automatically injects listener into HTML web responses via middleware (default: true)
     * | - key: The character key to trigger (default: 'x')
     * | - ctrl: Require Ctrl (or Cmd on Mac) key (default: true)
     * | - shift: Require Shift key (default: true)
     * | - alt: Require Alt key (default: false)
     * | - new_tab: Open viewer in a new browser tab ('_blank') (default: true)
     * |
     */
    'shortcut' => [
        'enabled' => env('LOG_VIEWER_SHORTCUT_ENABLED', false),
        'auto_inject' => env('LOG_VIEWER_SHORTCUT_AUTO_INJECT', true),
        'key' => env('LOG_VIEWER_SHORTCUT_KEY', 'x'),
        'ctrl' => env('LOG_VIEWER_SHORTCUT_CTRL', true),
        'shift' => env('LOG_VIEWER_SHORTCUT_SHIFT', true),
        'alt' => env('LOG_VIEWER_SHORTCUT_ALT', false),
        'new_tab' => env('LOG_VIEWER_SHORTCUT_NEW_TAB', true),
    ],

    /*
     * |--------------------------------------------------------------------------
     * | Password Protection & Security Session
     * |--------------------------------------------------------------------------
     * |
     * | When enabled, visitors must enter a password before accessing the log viewer.
     * | - If correct: access is granted for the session without re-asking.
     * | - If incorrect: user gets 3 attempts. After 3 failed attempts, they are
     * |   locked out and redirected to the home page!
     * | - Pressing the emergency destroy shortcut (Ctrl + Shift + D) instantly
     * |   destroys the session and closes all open log viewer tabs!
     * |
     * | - enabled: Enable password protection (default: false)
     * | - password: Password required to access log viewer
     * | - max_attempts: Maximum failed password attempts before lockout (default: 3)
     * | - lockout_redirect: URL to redirect user when max attempts are exceeded (default: '/')
     * | - lockout_duration: Lockout duration in seconds before attempts reset (default: 60)
     * | - destroy_shortcut: Hotkey to destroy session and close all open tabs
     * |
     */
    'security' => [
        'enabled' => env('LOG_VIEWER_PASSWORD_PROTECTED', false),
        'password' => env('LOG_VIEWER_PASSWORD', 'secret'),
        'max_attempts' => (int) env('LOG_VIEWER_MAX_ATTEMPTS', 3),
        'lockout_redirect' => env('LOG_VIEWER_LOCKOUT_REDIRECT', '/'),
        'lockout_duration' => (int) env('LOG_VIEWER_LOCKOUT_DURATION', 60),
        'session_key' => 'raytech_log_viewer_authenticated',
        'attempts_key' => 'raytech_log_viewer_failed_attempts',
        'lockout_key' => 'raytech_log_viewer_locked_until',
        'destroy_shortcut' => [
            'enabled' => env('LOG_VIEWER_DESTROY_SHORTCUT_ENABLED', true),
            'key' => env('LOG_VIEWER_DESTROY_SHORTCUT_KEY', 'd'),
            'ctrl' => true,
            'shift' => true,
            'alt' => false,
        ],
    ],

    /*
     * |--------------------------------------------------------------------------
     * | Asset CDN Sources
     * |--------------------------------------------------------------------------
     * |
     * | External assets for FontAwesome icons and SweetAlert2 dialogs.
     * | By default, high-speed CDN URLs are used so the plugin works instantly
     * | on any fresh Laravel installation without requiring local asset compiling.
     * |
     */
    'assets' => [
        'use_cdn' => true,
        'fontawesome_css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        'sweetalert2_css' => 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.min.css',
        'sweetalert2_js' => 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.all.min.js',
    ],
];
