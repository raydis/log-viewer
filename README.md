# RayTech Log Viewer (`raytech/raytech-log-viewer`)

> **A high-performance, modern Laravel plugin for viewing all application and server logs with zero database setup, latest-logs-on-top ordering, interactive accordion summaries, dedicated CSS/JS assets, live AJAX refresh, instant search, and customizable themes.**

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Version](https://img.shields.io/badge/version-1.2.0-brightgreen.svg)
![PHP Version](https://img.shields.io/badge/php-%5E8.0-8892BF.svg)
![Laravel](https://img.shields.io/badge/laravel-%5E8.0%20%7C%20%5E9.0%20%7C%20%5E10.0%20%7C%20%5E11.0%20%7C%20%5E12.0-FF2D20.svg)

---

## 🌟 Key Features

- 🔝 **Latest Logs on Top (Newest First by Default)**:
  - Displays the newest log entries at the very top of the screen (`desc` order) so developers immediately see recent errors and events without scrolling to the bottom.
  - One-click toolbar toggle to switch anytime between **Newest First** and **Oldest First**.
  - Configurable via `default_order => 'desc'` in config or `.env`.
- 📂 **Interactive Accordion (Short Summary to Full Detail)**:
  - Each log entry is rendered as a clean, single-line preview row displaying **Timestamp**, **Severity Badge**, and a **Short Text Summary**.
  - Click anywhere on an entry row to smoothly expand the **full error message**, **multi-line stack trace**, and formatted **JSON context**.
  - **Expand All / Collapse All** toolbar button for fast bulk inspection.
  - Per-entry **Copy Log** button to copy complete error messages and stack traces to clipboard with one click.
- 🖼️ **Consolidated Logo & Browser Tab Favicon**:
  - Unified single logo asset located at `public/img/default_logo.jpg` (no duplicate files).
  - Automatically rendered in the topbar brand header.
  - Automatically embedded in the HTML `<head>` as the browser tab favicon icon (`rel="icon"` and `rel="shortcut icon"`).
  - Fully customizable via config (`'brand_logo'`) or `.env` (`LOG_VIEWER_LOGO`).
- 🎨 **Modular External CSS & JS Assets**:
  - Clean separation of concerns with dedicated stylesheets (`public/css/raytech-log-viewer.css`) and JavaScript logic (`public/js/raytech-log-viewer.js`).
  - Automatic cache-busting version query string (`?v=1.1.0`).
  - Built-in automatic asset sync: If published assets are older than the package assets, the controller automatically updates them seamlessly.
- ⚡ **Zero Database Setup**:
  - Does not require any database tables, migrations, models, or Redis queues. Works 100% directly from log files on disk.
- 🚀 **Lightning Fast Chunked Tail Reader**:
  - Handles multi-gigabyte log files in milliseconds without running out of PHP memory (`memory_limit`).
- 🔍 **Auto-Detection for All Logs**:
  - **Laravel Logs**: Automatically discovers all logs in `storage/logs/*.log`.
  - **Server Logs**: Automatically detects PHP error logs (`ini_get('error_log')`), Apache (`bin/apache/*/logs`, `httpd`), Nginx, WAMP, MariaDB, MySQL, and Linux paths (`/var/log/*`).
  - **Custom Log Files & Dirs**: Add any custom files or directories via configuration or `.env`.
- 🔄 **Real-Time Live Auto-Refresh**:
  - Seamless AJAX polling (5s, 10s, 30s, or manual) with zero page reload or screen flickering.
- 🎯 **Smart Log Level Detection**:
  - Detects `EMERGENCY`, `ALERT`, `CRITICAL`, `ERROR`, `WARNING`, `INFO`, `DEBUG`, and `TRACE`.
  - Filter by severity tag with instant badge counts.
- 🔎 **Instant Client-Side Search**:
  - Search through thousands of lines with live keyword highlighting and counter badges (<kbd>Ctrl</kbd> + <kbd>F</kbd>).
  - Auto-expands matching collapsed entries when searching.
- 🌓 **Dark / Light Mode**:
  - Instant theme toggle with `localStorage` persistence.
- 🛡️ **Flexible Security & Access Control**:
  - Works with any middleware (`web`, `auth`, custom role guards).
  - Supports programmatic closures (`RaytechLogViewer::auth(...)`) in your `AppServiceProvider`.
  - Can be completely public or restricted to specific user roles.
- 📋 **Productivity Actions**:
  - Download raw log file.
  - View raw unformatted log in a new tab.
  - Clear / truncate log file safely with confirmation dialog.
  - Word wrap toggle, sidebar file explorer toggle (<kbd>Ctrl</kbd> + <kbd>B</kbd>), and fullscreen mode.

---

## 📦 Installation Options

Choose the installation option that best fits your workflow:

### Option 1: Direct from Public GitHub Repository (Recommended)

The repository is public on GitHub at `https://github.com/umesh240/log-viewer.git`. You do **not** need any personal access tokens, SSH keys, or usernames.

#### Method A: Using Composer CLI Commands (Quickest)

Run these two commands in the root of your Laravel application:

```bash
# 1. Register the public GitHub repository in Composer
composer config repositories.raytech-log-viewer vcs https://github.com/umesh240/log-viewer.git

# 2. Require the package from the main branch
composer require raytech/raytech-log-viewer:dev-main
```

> **📌 Installing a Specific Tag/Release**:  
> To install a stable tagged version instead of the development branch:  
> ```bash
> composer require raytech/raytech-log-viewer:^1.1
> ```

---

#### Method B: By Editing `composer.json` Manually

Open your Laravel application's `composer.json` and add the repository configuration to the `"repositories"` array (create it if it does not exist) and require the package:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/umesh240/log-viewer.git"
        }
    ],
    "require": {
        "raytech/raytech-log-viewer": "dev-main"
    }
}
```

Then run:

```bash
composer update raytech/raytech-log-viewer
```

#### 💡 What do `vcs` and `dev-main` mean?
- **`"type": "vcs"`**: Stands for *Version Control System*. It instructs Composer to inspect Git at the given URL directly instead of querying only the global Packagist directory.
- **`"dev-main"`**: Instructs Composer to checkout code directly from the `main` branch. Composer prefixes Git branches with `dev-` so it knows it is tracking a branch rather than a numbered SemVer release.
- **Version Tags**: When git tags are created (such as `v1.1.0`), you can also use standard version constraints like `"^1.1"` or `"1.1.0"`.

---

### Option 2: Local Path Repository (For Local Package Development)

If you are modifying the package locally on the same computer (e.g., at `E:/wamp/www/log_plugin`), you can link it directly into your Laravel project via a symlink:

In your Laravel project's `composer.json`:

```json
{
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
}
```

Then run:

```bash
composer update raytech/raytech-log-viewer
```

Any code changes you make in `E:/wamp/www/log_plugin` will immediately reflect in your Laravel application without needing any git commit or push!

---

### Option 3: Standard Packagist Installation (When Published)

Once the package is submitted to [packagist.org](https://packagist.org):

```bash
composer require raytech/raytech-log-viewer
```

---

### Option 4: Manual Copy (Without Composer Repositories)

If your server has no Git or Internet access, you can drop the code directly into your Laravel application:

1. Copy the plugin files into `packages/raytech/log-viewer`:
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

2. Register the PSR-4 namespace in your Laravel application's `composer.json`:
   ```json
   "autoload": {
       "psr-4": {
           "App\\": "app/",
           "Raytech\\LogViewer\\": "packages/raytech/log-viewer/src/"
       }
   }
   ```

3. Run:
   ```bash
   composer dump-autoload
   ```

4. *(Laravel 8 / 9 / 10)* Register the Service Provider in `config/app.php` under `'providers'`:
   ```php
   Raytech\LogViewer\RaytechLogViewerServiceProvider::class,
   ```
   *(In Laravel 11 / 12, Laravel auto-discovers it automatically or add to `bootstrap/providers.php`).*

---

## 🚀 Quick Setup

After installing the package, run the install command:

```bash
php artisan raytech-log-viewer:install
```

This command will:
1. Publish `config/raytech-log-viewer.php` to your application's `config/` directory.
2. Publish CSS, JS, and default logo assets to `public/vendor/raytech-log-viewer/`.

That's it! Now open your browser and navigate to:

```
https://your-domain.local/log-viewer
```

*(You can customize the URI prefix to `/raytech-log-viewer`, `/admin/logs`, `/error-log`, or anything else in the configuration).*

---

## 🔄 Updating to Latest Code & Assets

When updates or new features are pushed to the repository, follow these simple steps:

### Step 1: Update the Code via Composer

Run:

```bash
composer update raytech/raytech-log-viewer
```

#### How Composer Compares and Updates:
- **Branch Tracking (`dev-main`)**: Composer queries GitHub for the latest Git commit hash on `main`. If a newer commit exists, it downloads and replaces the package files.
- **Tagged Releases (`^1.1`)**: Composer compares semantic version numbers (`v1.1.0`, `v1.1.1`, etc.) and downloads the latest release matching your constraint.

> **💡 If Composer says "Nothing to modify" despite newer commits on GitHub**:  
> Composer caches remote repository metadata locally. Simply clear the cache and re-run:  
> ```bash
> composer clear-cache
> composer update raytech/raytech-log-viewer
> ```

---

### Step 2: Update Published Assets (CSS, JS, Logo)

Whenever styles, scripts, or logos are updated in the package, republish the assets:

```bash
php artisan raytech-log-viewer:update
```

*(Alternatively: `php artisan vendor:publish --tag=raytech-log-viewer-assets --force`)*

---

### Step 3: Updating Configuration File (`config/raytech-log-viewer.php`)

#### ❓ Why does `composer update` NOT update `config/raytech-log-viewer.php`?
- **By Design**: `composer update` is a PHP Composer command, not a Laravel command. Composer downloads files strictly to `vendor/raytech/raytech-log-viewer/`. It **never touches** your Laravel application's `config/` directory.
- **Protects Custom Settings**: When you first install the plugin, `php artisan raytech-log-viewer:install` copies the configuration to your root `config/raytech-log-viewer.php`. If Composer automatically replaced that file on update, it would **erase all your custom settings** (such as custom routes, auth middlewares, and custom log directories)!

#### How to update `config/raytech-log-viewer.php`:

##### Option A: Update via Artisan Command (Recommended)
You can update your published configuration file to the latest version with one command:

```bash
# Overwrite and update config/raytech-log-viewer.php to latest default:
php artisan raytech-log-viewer:update --config

# Or update both assets and config together:
php artisan raytech-log-viewer:update --all
```

*(Alternatively: `php artisan vendor:publish --tag=raytech-log-viewer-config --force`)*

##### Option B: Zero-Maintenance Automatic Fallback (No overwrite needed!)
The package uses **recursive configuration merging**. Even if you **never** update your published `config/raytech-log-viewer.php`, the package will automatically merge the latest defaults for any new keys (e.g. `default_order`, `back_url_name`, `show_back_button`) while keeping all your custom configurations 100% intact!

If you want to manually add the newest configuration variables to your existing config file:

```php
// In 'theme' array:
'brand_logo' => env('LOG_VIEWER_LOGO', 'vendor/raytech-log-viewer/img/default_logo.jpg'),
'back_url' => env('LOG_VIEWER_BACK_URL', '/'),
'back_url_name' => env('LOG_VIEWER_BACK_URL_NAME', 'Back To Home'),
'show_back_button' => env('LOG_VIEWER_SHOW_BACK_BUTTON', true),

// In 'features' array:
'default_order' => env('RAYTECH_LOG_VIEWER_ORDER', 'desc'), // 'desc' = latest log on top
```

---

### ⚡ Automatic Updates (Zero Maintenance)

#### Option A: Built-in Controller Auto-Sync
The package includes an intelligent asset controller fallback. When assets are loaded via `/log-viewer/assets/...`, the controller automatically compares file modification timestamps. If the package files are newer than the published files in `public/vendor/raytech-log-viewer/`, it **automatically syncs them in the background** without any manual command needed!

#### Option B: Automated Composer Hook
To guarantee that assets are always updated whenever anyone runs `composer update`, add this to your Laravel project's `composer.json` under `"scripts"`:

```json
"scripts": {
    "post-update-cmd": [
        "@php artisan vendor:publish --tag=raytech-log-viewer-assets --force"
    ]
}
```

---

## ⚙️ Configuration Reference

The published configuration file is located at `config/raytech-log-viewer.php`.

### 1. Route & Middleware Settings

```php
'route' => [
    // The URI prefix to access the viewer (e.g. 'log-viewer', 'error-log', 'admin/logs')
    'prefix' => env('LOG_VIEWER_ROUTE_PREFIX', 'log-viewer'),
    
    // Route name prefix
    'name' => 'log-viewer.',
    
    // Optional domain constraint (e.g. 'logs.yourdomain.com')
    'domain' => env('LOG_VIEWER_DOMAIN', null),
    
    // Middleware protection: 'web', ['web', 'auth'], or 'web,auth,adminGard'
    // Leave null or empty to allow open access
    'middleware' => env('LOG_VIEWER_MIDDLEWARE', 'web'),
],
```

### 2. UI Theme, Branding & Logo Settings

```php
'theme' => [
    // Default appearance: 'light' or 'dark'
    'default_mode' => env('LOG_VIEWER_THEME', 'light'),

    // Primary accent color for active badges, buttons, and highlights
    'primary_color' => env('LOG_VIEWER_PRIMARY_COLOR', '#1f6feb'),

    // Browser tab title and topbar header text
    'title' => env('LOG_VIEWER_TITLE', 'RayTech Log Viewer'),
    'brand_name' => env('APP_NAME', 'RayTech'),

    // Brand logo path or URL displayed in header & browser tab favicon
    // Default: 'vendor/raytech-log-viewer/img/default_logo.jpg'
    'brand_logo' => env('LOG_VIEWER_LOGO', 'vendor/raytech-log-viewer/img/default_logo.jpg'),

    // Back button target URL and text label
    'back_url' => env('LOG_VIEWER_BACK_URL', '/'),
    'back_url_name' => env('LOG_VIEWER_BACK_URL_NAME', 'Back To Home'),
    'show_back_button' => env('LOG_VIEWER_SHOW_BACK_BUTTON', true),
],
```

### 3. Log Sorting & Action Controls

```php
'features' => [
    // Default log ordering:
    // 'desc' = Newest logs on top (Recommended)
    // 'asc'  = Oldest logs on top (Classic tail order)
    'default_order' => env('RAYTECH_LOG_VIEWER_ORDER', 'desc'),

    'allow_download' => env('LOG_VIEWER_ALLOW_DOWNLOAD', true), // Allow downloading log files
    'allow_clear' => env('LOG_VIEWER_ALLOW_CLEAR', true),       // Allow truncating/clearing log files
    'allow_raw' => env('LOG_VIEWER_ALLOW_RAW', true),           // Allow viewing raw text in browser tab
    'allow_copy' => true,                                      // Allow clipboard copy buttons
    'default_lines' => 500,                                    // Default lines loaded on initial page load
    'lines_options' => [100, 250, 500, 1000, 2500, 5000],
    'auto_refresh_default' => 10000,                           // 10s auto-refresh interval (0 = disabled)
    'max_read_bytes' => 4194304,                               // 4MB tail buffer (prevents memory spikes)
],
```

### 4. Extra Log Directories & Custom Files

```php
'logs' => [
    'scan_laravel_logs' => true,      // Scan storage/logs/*.log
    'scan_server_logs' => true,       // Scan Apache, Nginx, WAMP, MySQL logs
    'scan_php_error_log' => true,      // Read ini_get('error_log')

    // Add extra directory paths to scan:
    'extra_directories' => [
        storage_path('logs/workers'),
        'E:/wamp/logs',
        '/var/log/my-daemons',
    ],

    // Add specific log file paths:
    'extra_files' => [
        'cron.log',
        'payment_gateway.log',
        storage_path('logs/worker.log'),
    ],

    // File patterns to ignore:
    'ignore_files' => [
        '.gitignore',
        '.gitkeep',
        '*.gz',
        '*.zip',
    ],
],
```

---

## 🌿 Environment Variables (`.env`)

You can customize the log viewer directly in your `.env` file without altering PHP files:

```env
# URL & Access
LOG_VIEWER_ENABLED=true
LOG_VIEWER_ROUTE_PREFIX=log-viewer
LOG_VIEWER_MIDDLEWARE="web,auth"

# Visual Branding & Favicon
LOG_VIEWER_THEME=light
LOG_VIEWER_PRIMARY_COLOR=#1f6feb
LOG_VIEWER_TITLE="My App - Log Viewer"
LOG_VIEWER_LOGO="vendor/raytech-log-viewer/img/default_logo.jpg"
LOG_VIEWER_BACK_URL="/admin/dashboard"
LOG_VIEWER_BACK_URL_NAME="Back to Dashboard"
LOG_VIEWER_SHOW_BACK_BUTTON=true

# Log Ordering ('desc' for latest on top, 'asc' for oldest first)
RAYTECH_LOG_VIEWER_ORDER=desc

# Feature Toggles
LOG_VIEWER_ALLOW_DOWNLOAD=true
LOG_VIEWER_ALLOW_CLEAR=true
LOG_VIEWER_ALLOW_RAW=true

# Custom Directories & Files (comma-separated)
LOG_VIEWER_DIRS="E:/wamp/logs,storage/logs/custom"
LOG_VIEWER_FILES="cron.log,payment.log"
```

---

## 🔒 Access Control & Security

### Method A: Config / Environment Middleware
Protect with standard Laravel middleware in `.env`:
```env
LOG_VIEWER_MIDDLEWARE="web,auth,admin"
```

### Method B: Programmatic Closure (`RaytechLogViewer::auth`)
Define custom authorization logic in the `boot()` method of `app/Providers/AppServiceProvider.php`:

```php
use Raytech\LogViewer\Facades\RaytechLogViewer;

public function boot(): void
{
    RaytechLogViewer::auth(function ($request) {
        $user = $request->user();
        return $user && $user->is_admin;
    });
}
```

### Method C: Public Access (Allow All)
```php
RaytechLogViewer::allowAll();
```

---

## ⌨️ Keyboard Shortcuts & UI Controls

| Control / Shortcut | Action |
|---|---|
| <kbd>Click Row</kbd> | Toggle accordion expand / collapse for full error message and stack trace |
| **Newest First / Oldest First** | Toggle chronological sorting (newest logs on top or bottom) |
| **Expand All / Collapse All** | Expand or collapse all visible log entries at once |
| <kbd>Ctrl</kbd> + <kbd>F</kbd> / <kbd>Cmd</kbd> + <kbd>F</kbd> | Focus instant line search input |
| <kbd>Esc</kbd> | Clear search filter |
| <kbd>Ctrl</kbd> + <kbd>B</kbd> / <kbd>Cmd</kbd> + <kbd>B</kbd> | Toggle log files sidebar |
| <kbd>Copy Button</kbd> | Copy individual log stack trace or all visible logs to clipboard |

---

## 💻 Artisan Console Commands

### Install Configuration & Assets:
```bash
php artisan raytech-log-viewer:install
# Force overwrite existing files:
php artisan raytech-log-viewer:install --force
```

### Force Update Assets & Config:
```bash
# Update assets:
php artisan raytech-log-viewer:update

# Update config file:
php artisan raytech-log-viewer:update --config

# Update both assets and config:
php artisan raytech-log-viewer:update --all
```

### Clear / Truncate Log Files:
```bash
# Clear a specific log file:
php artisan raytech-log-viewer:clear laravel.log

# Clear all discovered log files:
php artisan raytech-log-viewer:clear --all
```

### Uninstall & Clean Up Published Files:
```bash
# Remove published assets, views, and configuration:
php artisan raytech-log-viewer:uninstall

# Force removal without confirmation prompt:
php artisan raytech-log-viewer:uninstall --force

# Remove assets and views but keep config/raytech-log-viewer.php:
php artisan raytech-log-viewer:uninstall --keep-config
```

---

## 🗑️ Uninstalling the Plugin

If you ever wish to completely remove the plugin from your Laravel project, follow these two simple steps:

### Step 1: Remove Published Files (Artisan)
Run the built-in uninstall command to cleanly delete published assets (`public/vendor/raytech-log-viewer/`), views, and configuration:

```bash
php artisan raytech-log-viewer:uninstall
```

*(Pass `--keep-config` if you want to preserve your custom configuration file).*

### Step 2: Remove the Package Dependency (Composer)
Remove the package from your `composer.json` and vendor directory:

```bash
composer remove raytech/raytech-log-viewer
```

### Step 3 (Optional): Remove VCS Repository
If you registered the GitHub VCS repository via Composer CLI during installation, you can clean it up by running:

```bash
composer config --unset repositories.raytech-log-viewer
```
*(Or manually remove the repository block from `composer.json`).*

---

## 📂 Package Architecture

```
log_plugin/
├── composer.json                       # Composer package definition & discovery
├── README.md                           # Documentation
├── LICENSE                             # MIT License
├── config/
│   └── raytech-log-viewer.php          # Publishable configuration
├── routes/
│   └── web.php                         # Route definitions
├── public/
│   ├── css/
│   │   └── raytech-log-viewer.css      # Dedicated modern CSS stylesheet
│   ├── js/
│   │   └── raytech-log-viewer.js       # Dedicated accordion & AJAX JS logic
│   └── img/
│       └── default_logo.jpg            # Single canonical brand & favicon logo
├── src/
│   ├── RaytechLogViewerServiceProvider.php # Laravel Service Provider
│   ├── RaytechLogViewer.php            # Core facade target & runtime manager
│   ├── Facades/
│   │   └── RaytechLogViewer.php        # Laravel Facade
│   ├── Services/
│   │   ├── LogFileManager.php          # Discovery, chunked tail reading & clearing
│   │   └── LogParser.php               # Level detection, accordion parsing & ordering
│   ├── Http/
│   │   └── Controllers/
│   │       └── RaytechLogViewerController.php # Controller with AJAX & asset auto-sync
│   └── Console/
│       ├── InstallCommand.php          # php artisan raytech-log-viewer:install
│       ├── UpdateCommand.php           # php artisan raytech-log-viewer:update
│       ├── UninstallCommand.php        # php artisan raytech-log-viewer:uninstall
│       └── ClearCommand.php            # php artisan raytech-log-viewer:clear
└── resources/
    └── views/
        └── index.blade.php             # High-density Blade template with accordion
```

---

## ❓ Frequently Asked Questions

#### Q1: Why did Composer show `illuminate/support` conflicts on Laravel 8?
**Answer**: Previous versions required `^9.0 || ^10.0 || ^11.0 || ^12.0`. In **v1.1.0**, compatibility has been expanded to `"illuminate/support": "^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0"`. It now works smoothly on Laravel 8, 9, 10, 11, and 12.

#### Q2: Composer says "Nothing to install, update or remove" when I update from GitHub.
**Answer**: Composer caches repository information. Run:
```bash
composer clear-cache
composer update raytech/raytech-log-viewer
```

#### Q3: How do I change the logo to my own company logo?
**Answer**: Simply place your logo in `public/img/my_logo.png` and add this in your `.env`:
```env
LOG_VIEWER_LOGO="img/my_logo.png"
```
It will automatically show in the topbar and in the browser tab favicon.

---

## 📄 License

This package is open-source software licensed under the [MIT license](LICENSE).
