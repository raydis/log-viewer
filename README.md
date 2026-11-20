# RayTech Log Viewer (`raytech/raytech-log-viewer`)

<p align="center">
  <img src="public/img/default_logo.jpg" alt="RayTech Log Viewer Logo" width="120" style="border-radius: 12px; margin-bottom: 12px;">
</p>

<p align="center">
  <strong>A high-performance, modern Laravel plugin for viewing all application and server logs with clean header rows, click-to-expand stack traces, newest-first sorting, live auto-refresh, and zero database setup.</strong>
</p>

<p align="center">
  <a href="https://github.com/umesh240/log-viewer"><img src="https://img.shields.io/badge/version-1.2.2-brightgreen.svg" alt="Version"></a>
  <a href="https://packagist.org/packages/raytech/raytech-log-viewer"><img src="https://img.shields.io/badge/packagist-raytech%2Fraytech--log--viewer-orange.svg" alt="Packagist"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/php-%5E8.0-8892BF.svg" alt="PHP Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/laravel-%5E8.0%20%7C%20%5E9.0%20%7C%20%5E10.0%20%7C%20%5E11.0%20%7C%20%5E12.0-FF2D20.svg" alt="Laravel"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
</p>

---

## ⚡ Quick Start (Get Started in 30 Seconds)

### Option 1: Install from Packagist (Standard)
If published on Packagist, simply run in your Laravel project:

```bash
# 1. Require package
composer require raytech/raytech-log-viewer

# 2. Publish config & assets
php artisan raytech-log-viewer:install
```

---

### Option 2: Install Direct from GitHub (Public Repository)
Install directly from GitHub repository without needing a Packagist release:

```bash
# 1. Tell Composer where to find the GitHub repo
composer config repositories.raytech-log-viewer vcs https://github.com/umesh240/log-viewer.git

# 2. Require the package
composer require raytech/raytech-log-viewer:dev-main

# 3. Publish config & assets
php artisan raytech-log-viewer:install
```

> 🎯 **Done!** Open your browser and navigate to:  
> **`https://your-app.local/log-viewer`**

---

## 🌟 What Makes RayTech Log Viewer Special?

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [>]  [ local.ERROR ]   2026-11-11 05:06:16   Call to undefined function str()           [7 frames]  📋 │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ▼ Stack Exception: Call to undefined function str()                                                    │
│   in E:\wamp\www\resembler\storage\framework\lsp-ca4445c6fefa61f6.php on line 353                      │
│ ┌──────────────────────┬──────────────────────┬──────────────────────────────────────────────────────┐ │
│ │ [Stack Trace (7)]    │ [Context JSON]       │ [Raw Log]                                            │ │
│ └──────────────────────┴──────────────────────┴──────────────────────────────────────────────────────┘ │
│   #0 E:\wamp\www\resembler\app\Http\Controllers\HomeController.php:42 -> renderView()                 │
│   #1 E:\wamp\www\resembler\vendor\laravel\framework\src\Illuminate\Routing\Controller.php:54           │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

1. **Header Group Table Layout**: Each log entry is a clean, single-row summary displaying:
   - `log_type`: Tagged badge (`local.ERROR`, `production.WARNING`, `PHP Fatal error`, `INFO`, `DEBUG`).
   - `log_create_date`: Exact timestamp (`2026-11-11 05:06:16`).
   - `header_text`: The human-readable error description without raw JSON clutter.
2. **Click-to-Expand Body Content**: Clicking on any header row smoothly slides open:
   - **Exception Card**: Bold exception title, message, and exact file + line number.
   - **Interactive Stack Trace**: Frame-by-frame (`#0`, `#1`), file paths highlighted in blue, line numbers in orange, and method calls.
   - **Context Tab**: Pretty-printed JSON context data.
   - **Raw Log Tab**: Verbatim full log content with a one-click copy button.
3. **Latest Logs on Top (Newest First by Default)**: Shows your most recent errors immediately at the top of the screen (`desc` order). Toggle between **Newest First** and **Oldest First** with one click.
4. **Zero Database Setup**: Zero migrations, zero tables, zero models, zero queues. Works 100% directly from disk log files.
5. **Auto-Discovers All Server Logs**: Detects Laravel logs (`storage/logs/*.log`), PHP error logs (`ini_get('error_log')`), Apache, Nginx, WAMP, MariaDB, MySQL, and Linux system logs (`/var/log/*`).
6. **Unified Brand Logo & Favicon**: Single logo at `public/img/default_logo.jpg` used in both topbar header and browser tab favicon.
7. **Live Auto-Refresh**: Seamless AJAX polling (5s, 10s, 30s, or manual) with zero page flicker.
8. **Dark / Light Mode**: Instant theme switch with persistent state.

---

## 📦 Installation & Setup Guide

### Method 1: Packagist Installation
Run inside your Laravel project root:
```bash
composer require raytech/raytech-log-viewer
php artisan raytech-log-viewer:install
```

---

### Method 2: GitHub Repository Installation

#### A. Command Line (Quickest)
```bash
# Add public GitHub repository to your composer config
composer config repositories.raytech-log-viewer vcs https://github.com/umesh240/log-viewer.git

# Install latest code from main branch:
composer require raytech/raytech-log-viewer:dev-main

# (Or install a tagged version, e.g.: composer require raytech/raytech-log-viewer:^1.2)

# Publish assets & configuration:
php artisan raytech-log-viewer:install
```

#### B. Manual `composer.json`
Add the repository to your Laravel project's `composer.json`:

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
php artisan raytech-log-viewer:install
```

---

### Method 3: Local Development (Path Symlink)
If you are developing or modifying the package locally on the same machine:

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
php artisan raytech-log-viewer:install
```
*Any changes made inside `E:/wamp/www/log_plugin` take effect immediately.*

---

## 🔄 Updating to Latest Release

When new commits or tags are pushed to the repository:

### Step 1: Update Code via Composer
```bash
composer update raytech/raytech-log-viewer
```

> 💡 **Tip**: If Composer says *"Nothing to modify"*, clear Composer's local cache:
> ```bash
> composer clear-cache
> composer update raytech/raytech-log-viewer
> ```

### Step 2: Update Assets (CSS, JS, Logo)
```bash
php artisan raytech-log-viewer:update
```

### Step 3: Update Config (Optional)
Composer updates code in `vendor/` and intentionally **never touches** your root `config/` to protect your custom settings. If you want to overwrite your published config with the latest package defaults:
```bash
php artisan raytech-log-viewer:update --config
```
*Or update both assets and config together:*
```bash
php artisan raytech-log-viewer:update --all
```

---

## 🗑️ How to Uninstall

If you ever wish to completely remove the plugin:

```bash
# 1. Cleanly delete published assets, views, and configuration
php artisan raytech-log-viewer:uninstall

# 2. Remove the Composer dependency
composer remove raytech/raytech-log-viewer

# 3. (If installed via GitHub VCS) remove repository entry from composer config
composer config --unset repositories.raytech-log-viewer
```

---

## ⚙️ Configuration & Environment (`.env`)

The published configuration file is located at `config/raytech-log-viewer.php`. You can customize almost everything directly in your `.env`:

| `.env` Variable | Default | Description |
|---|---|---|
| `LOG_VIEWER_ENABLED` | `true` | Enable or disable the log viewer route |
| `LOG_VIEWER_ROUTE_PREFIX` | `log-viewer` | URL path prefix (e.g. `/log-viewer`, `/admin/logs`) |
| `LOG_VIEWER_MIDDLEWARE` | `web` | Middlewares to protect the route (e.g. `web,auth`) |
| `RAYTECH_LOG_VIEWER_ORDER` | `desc` | Default sort order (`desc` = latest logs on top) |
| `LOG_VIEWER_THEME` | `light` | Default theme (`light` or `dark`) |
| `LOG_VIEWER_PRIMARY_COLOR` | `#1f6feb` | Primary accent color |
| `LOG_VIEWER_TITLE` | `RayTech Log Viewer` | Browser tab title and topbar heading |
| `LOG_VIEWER_LOGO` | `vendor/raytech-log-viewer/img/default_logo.jpg` | Logo path (used in header & favicon) |
| `LOG_VIEWER_BACK_URL` | `/` | Back button link destination |
| `LOG_VIEWER_BACK_URL_NAME` | `Back To Home` | Back button text label |
| `LOG_VIEWER_SHOW_BACK_BUTTON`| `true` | Show or hide the back button |
| `LOG_VIEWER_ALLOW_DOWNLOAD` | `true` | Allow downloading raw log files |
| `LOG_VIEWER_ALLOW_CLEAR` | `true` | Allow clearing / truncating log files |
| `LOG_VIEWER_ALLOW_RAW` | `true` | Allow viewing unformatted raw text in new tab |
| `LOG_VIEWER_DIRS` | `null` | Extra directories to scan (comma-separated) |
| `LOG_VIEWER_FILES` | `null` | Extra file paths to scan (comma-separated) |

---

## 🔒 Access Control & Security

### Option A: Standard Laravel Middleware
Protect the route using standard auth middleware in `.env`:
```env
LOG_VIEWER_MIDDLEWARE="web,auth,admin"
```

### Option B: Programmatic Closure (`RaytechLogViewer::auth`)
Add custom authorization logic in `app/Providers/AppServiceProvider.php`:

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

### Option C: Public Access (Open)
```php
RaytechLogViewer::allowAll();
```

---

## 💻 Artisan Commands Reference

| Command | Description |
|---|---|
| `php artisan raytech-log-viewer:install` | Publishes config & assets (`public/vendor/raytech-log-viewer/`) |
| `php artisan raytech-log-viewer:install --force` | Overwrites existing config and assets |
| `php artisan raytech-log-viewer:update` | Re-publishes newest CSS, JS, and image assets |
| `php artisan raytech-log-viewer:update --config` | Updates published `config/raytech-log-viewer.php` |
| `php artisan raytech-log-viewer:update --all` | Updates both assets and configuration |
| `php artisan raytech-log-viewer:clear laravel.log` | Truncates / empties a specific log file |
| `php artisan raytech-log-viewer:clear --all` | Truncates / empties all discovered log files |
| `php artisan raytech-log-viewer:uninstall` | Removes all published assets, views, and config |
| `php artisan raytech-log-viewer:uninstall --keep-config` | Removes assets but keeps your custom config |

---

## ⌨️ Keyboard Shortcuts & UI Controls

| Action | Control / Shortcut |
|---|---|
| **Toggle Details** | Click anywhere on the log header row (`header_group`) |
| **Sort Order** | Click `Newest First` / `Oldest First` toggle button |
| **Bulk Expand / Collapse** | Click `Expand All` / `Collapse All` toolbar button |
| **Search Logs** | <kbd>Ctrl</kbd> + <kbd>F</kbd> or <kbd>Cmd</kbd> + <kbd>F</kbd> |
| **Clear Search** | <kbd>Esc</kbd> |
| **Toggle Sidebar** | <kbd>Ctrl</kbd> + <kbd>B</kbd> or <kbd>Cmd</kbd> + <kbd>B</kbd> |
| **Toggle Dark / Light Mode** | Click the Moon/Sun icon in topbar |
| **Copy Log** | Click the copy icon on any row or inside the expanded details tab |

---

## 📂 Project Architecture

```
log_plugin/
├── composer.json                       # Package definition & discovery
├── README.md                           # Documentation
├── LICENSE                             # MIT License
├── config/
│   └── raytech-log-viewer.php          # Publishable configuration
├── routes/
│   └── web.php                         # Route definitions
├── public/
│   ├── css/
│   │   └── raytech-log-viewer.css      # Modern stylesheet (Light & Dark mode)
│   ├── js/
│   │   └── raytech-log-viewer.js       # Table accordion & live AJAX logic
│   └── img/
│       └── default_logo.jpg            # Canonical logo (header & favicon)
├── src/
│   ├── RaytechLogViewerServiceProvider.php # Auto-discovery Service Provider
│   ├── RaytechLogViewer.php            # Core runtime manager & auth gates
│   ├── Facades/
│   │   └── RaytechLogViewer.php        # Laravel Facade
│   ├── Services/
│   │   ├── LogFileManager.php          # File scanner & fast chunked tail reader
│   │   └── LogParser.php               # Parser (log_type, date, header_text, stack trace)
│   ├── Http/
│   │   └── Controllers/
│   │       └── RaytechLogViewerController.php # Web controller & asset auto-sync
│   └── Console/
│       ├── InstallCommand.php          # raytech-log-viewer:install
│       ├── UpdateCommand.php           # raytech-log-viewer:update
│       ├── UninstallCommand.php        # raytech-log-viewer:uninstall
│       └── ClearCommand.php            # raytech-log-viewer:clear
└── resources/
    └── views/
        └── index.blade.php             # Modern high-density Blade template
```

---

## 📄 License

This package is open-source software licensed under the [MIT license](LICENSE).
