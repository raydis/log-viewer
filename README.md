# RayTech Log Viewer (`raytech/raytech-log-viewer`)

> **A high-performance, modern Laravel plugin for viewing all application and server logs with zero database setup, live AJAX refresh, instant search, and customizable themes.**

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%5E8.0-8892BF.svg)
![Laravel](https://img.shields.io/badge/laravel-%5E8.0%20%7C%20%5E9.0%20%7C%20%5E10.0%20%7C%20%5E11.0%20%7C%20%5E12.0-FF2D20.svg)

---

## 🌟 Key Features

- ⚡ **Zero Database Setup**: Does not require any database tables, migrations, or models.
- 🚀 **Lightning Fast Chunked Tail Reader**: Handles multi-gigabyte log files in milliseconds without running out of PHP memory.
- 🔍 **Auto-Detection for All Logs**:
  - **Laravel Logs**: Automatically discovers all logs in `storage/logs/*.log`.
  - **Server Logs**: Automatically detects PHP error logs (`ini_get('error_log')`), Apache (`bin/apache/*/logs`, `httpd`), Nginx, WAMP, MariaDB, MySQL, and Linux paths (`/var/log/*`).
  - **Custom Log Files**: Add any custom files or directories via configuration or `.env`.
- 🔄 **Real-Time Live Auto-Refresh**: Seamless AJAX polling (5s, 10s, 30s, or manual) with zero page reload or screen flickering.
- 🎯 **Smart Log Level Detection**:
  - Highlights `EMERGENCY`, `ALERT`, `CRITICAL`, `ERROR`, `WARNING`, `INFO`, `DEBUG`, and `TRACE`.
  - Filters by severity tag with instant badge counts.
- 🔎 **Instant Client-Side Search**: Search through thousands of lines with live keyword highlighting and counter badges (`Ctrl + F`).
- 🎨 **Fully Customizable Themes**:
  - Dark / Light mode toggle with local storage persistence.
  - Set custom brand colors, titles, and headers in `config/raytech-log-viewer.php`.
- 📋 **Productivity Tools**:
  - Copy visible log lines to clipboard.
  - Download raw log file.
  - View raw unformatted log in new tab.
  - Clear / truncate log file safely with confirmation dialog.
  - Word wrap toggle, sidebar file explorer toggle (`Ctrl + B`), and fullscreen mode.
- 🛡️ **Flexible Security & Access Control**:
  - Works with any middleware (`web`, `auth`, custom role guards).
  - Supports programmatic closures (`RaytechLogViewer::auth(...)`) in service providers.
  - Can be completely public or restricted to specific user types.

---

## 📦 Installation

### Option 1: Direct from GitHub (Public Repository)

Install directly from the public GitHub repository without needing any personal tokens or authentication:

#### Method A: Using Two Composer Commands (Quickest)

Run these two commands in your Laravel project root:

```bash
# 1. Add the GitHub repository to your project
composer config repositories.log-viewer vcs https://github.com/umesh240/log-viewer.git

# 2. Require the package
composer require raytech/raytech-log-viewer:dev-main
```

*(If your default branch is `master`, use `composer require raytech/raytech-log-viewer:dev-master`).*

---

#### Method B: By Editing `composer.json`

Add the repository in your Laravel application's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/umesh240/log-viewer.git"
    }
],
"require": {
    "raytech/raytech-log-viewer": "dev-main"
}
```

Then run:

```bash
composer update raytech/raytech-log-viewer
```

---

### Option 2: Install from Local Path Repository (Recommended for Local Development)

When developing locally on the same machine without pushing every change:

In your Laravel project's `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "E:/wamp/www/log_plugin",
        "options": {
            "symlink": true
        }
    }
],
"require": {
    "raytech/raytech-log-viewer": "*"
}
```

Then run:

```bash
composer update raytech/raytech-log-viewer
```

---

### Option 3: Standard Composer Require (When Published on Packagist)

Once submitted to [packagist.org](https://packagist.org):

```bash
composer require raytech/raytech-log-viewer
```

---

### Option 4: Manual Copy (Without Composer Repositories)

You can also copy the plugin folder directly into your Laravel project:

1. Copy the plugin directory into your Laravel project under `packages/raytech/log-viewer`:
   ```
   your-laravel-app/
   └── packages/
       └── raytech/
           └── log-viewer/
               ├── config/
               ├── public/
               ├── resources/
               ├── routes/
               ├── src/
               └── composer.json
   ```

2. Register the namespace in your Laravel project's `composer.json` under `autoload`:
   ```json
   "autoload": {
       "psr-4": {
           "App\\": "app/",
           "Raytech\\LogViewer\\": "packages/raytech/log-viewer/src/"
       }
   }
   ```

3. Run `composer dump-autoload`

4. In Laravel 9/10, add the Service Provider to `config/app.php` (under `'providers'`):
   ```php
   Raytech\LogViewer\RaytechLogViewerServiceProvider::class,
   ```
   *(In Laravel 11/12, add it to `bootstrap/providers.php`).*

---

## 🚀 Quick Setup

After installing, run the install artisan command to publish the configuration file:

```bash
php artisan raytech-log-viewer:install
```

That's it! Open your browser and navigate to:

```
https://your-domain.local/raytech-log-viewer
```

*(You can customize the URL prefix to `/error-log`, `/admin/logs`, or anything else in the config file).*

---

## ⚙️ Configuration Reference

The configuration file is published at `config/raytech-log-viewer.php`. You can customize every aspect:

### 1. Route & URL Prefix

```php
'route' => [
    // Change the URI prefix (e.g. 'error-log' or 'admin/logs')
    'prefix' => env('RAYTECH_LOG_VIEWER_ROUTE_PREFIX', 'raytech-log-viewer'),
    
    // Middleware protection: null, 'auth', ['web', 'auth'], or 'adminGard,web'
    'middleware' => env('RAYTECH_LOG_VIEWER_MIDDLEWARE', 'web'),
    
    'domain' => null,
],
```

### 2. Theme & Custom Branding

```php
'theme' => [
    // Default appearance mode: 'light' or 'dark'
    'default_mode' => env('RAYTECH_LOG_VIEWER_THEME', 'light'),

    // Primary brand color for active badges, buttons, and highlights
    'primary_color' => env('RAYTECH_LOG_VIEWER_PRIMARY_COLOR', '#1f6feb'),

    // Page title and brand header text
    'title' => env('RAYTECH_LOG_VIEWER_TITLE', 'RayTech Log Viewer'),
    'brand_name' => env('RAYTECH_LOG_VIEWER_BRAND', 'RayTech'),

    // Brand logo path/URL displayed in header & browser tab favicon
    // Default fallback: 'vendor/raytech-log-viewer/img/default_logo.jpg'
    'brand_logo' => env('RAYTECH_LOG_VIEWER_LOGO', env('RAYTECH_LOG_LOGOPATH', 'vendor/raytech-log-viewer/img/default_logo.jpg')),

    // Back button target URL and display label
    'back_url' => env('RAYTECH_LOG_VIEWER_BACK_URL', '/'),
    'back_url_name' => env('RAYTECH_LOG_VIEWER_BACK_URL_NAME', 'Back To Home'),
    'show_back_button' => env('RAYTECH_LOG_VIEWER_SHOW_BACK_BUTTON', true),
],
```

### 3. Extra Log Files & Custom Directories

Track any additional server logs, microservice logs, or custom background workers:

```php
'logs' => [
    'scan_laravel_logs' => true,
    'scan_server_logs' => true,
    'scan_php_error_log' => true,

    // Add custom directory paths:
    'extra_directories' => [
        storage_path('logs/workers'),
        'E:/wamp/logs',
        '/var/log/custom-apps',
    ],

    // Add individual custom log file paths:
    'extra_files' => [
        'cron.log',
        'worker.log',
        'payment_gateway.log',
    ],

    // Ignore file patterns:
    'ignore_files' => [
        '.gitignore',
        '.gitkeep',
        '*.gz',
        '*.zip',
    ],
],
```

Or configure via your `.env` file:

```env
RAYTECH_LOG_VIEWER_ROUTE_PREFIX=raytech-log-viewer
RAYTECH_LOG_VIEWER_THEME=light
RAYTECH_LOG_VIEWER_PRIMARY_COLOR=#1f6feb
RAYTECH_LOG_VIEWER_MIDDLEWARE=web,auth
RAYTECH_LOG_VIEWER_FILES=cron.log,payment.log,worker.log
RAYTECH_LOG_VIEWER_DIRS=E:/wamp/logs
```

### 4. Action Features & Permissions

```php
'features' => [
    'allow_download' => true,     // Allow downloading log files
    'allow_clear' => true,        // Allow truncating/clearing log files
    'allow_raw' => true,          // Allow viewing raw text in browser tab
    'allow_copy' => true,         // Allow clipboard copy button
    'default_lines' => 500,       // Default line limit
    'lines_options' => [100, 250, 500, 1000, 2500, 5000],
    'auto_refresh_default' => 10000, // 10 seconds auto-refresh (0 = off)
],
```

---

## 🔒 Access Control & Security

You can restrict access to the log viewer using any of the following methods:

### Method A: Config / Environment Middleware

In `config/raytech-log-viewer.php`:

```php
'middleware' => ['web', 'auth'],
```

Or in `.env`:

```env
RAYTECH_LOG_VIEWER_MIDDLEWARE="web,auth,adminGard"
```

### Method B: Programmatic Closure (in `AppServiceProvider.php`)

You can define custom authorization rules in the `boot()` method of your `app/Providers/AppServiceProvider.php`:

```php
use Raytech\LogViewer\Facades\RaytechLogViewer;

public function boot(): void
{
    RaytechLogViewer::auth(function ($request) {
        // Return true to allow, false or abort(403) to deny, or redirect()
        $user = $request->user();
        return $user && $user->is_super_admin;
    });
}
```

### Method C: Public Access (Allow All)

```php
RaytechLogViewer::allowAll();
```

---

## ⌨️ Keyboard Shortcuts

| Shortcut | Action |
|---|---|
| <kbd>Ctrl</kbd> + <kbd>B</kbd> / <kbd>Cmd</kbd> + <kbd>B</kbd> | Toggle file explorer sidebar |
| <kbd>Ctrl</kbd> + <kbd>F</kbd> / <kbd>Cmd</kbd> + <kbd>F</kbd> | Focus instant line search input |
| <kbd>Esc</kbd> | Clear current search filter |

---

## 💻 Artisan Console Commands

### Install / Publish Configuration:

```bash
php artisan raytech-log-viewer:install
# Or force overwrite:
php artisan raytech-log-viewer:install --force
```

### Clear / Truncate Log Files from CLI:

```bash
# Clear a specific log file:
php artisan raytech-log-viewer:clear laravel.log

# Clear all detected log files:
php artisan raytech-log-viewer:clear --all
```

---

## 📂 Package Architecture

```
log_plugin/
├── composer.json                       # Composer package definition & auto-discovery
├── README.md                           # Documentation
├── config/
│   └── raytech-log-viewer.php          # Publishable configuration
├── routes/
│   └── web.php                         # Route definitions
├── src/
│   ├── RaytechLogViewerServiceProvider.php # Laravel Service Provider
│   ├── RaytechLogViewer.php            # Core facade target & runtime manager
│   ├── Facades/
│   │   └── RaytechLogViewer.php        # Laravel Facade
│   ├── Services/
│   │   ├── LogFileManager.php          # Discovery, chunked tail reading & clearing
│   │   └── LogParser.php               # Level detection, parsing & statistics
│   ├── Http/
│   │   └── Controllers/
│   │       └── RaytechLogViewerController.php # Full Controller with AJAX support
│   └── Console/
│       ├── InstallCommand.php          # php artisan raytech-log-viewer:install
│       └── ClearCommand.php            # php artisan raytech-log-viewer:clear
└── resources/
    └── views/
        └── index.blade.php             # Modern high-density Blade template
```

---

## 📄 License

This package is open-source software licensed under the [MIT license](LICENSE).
