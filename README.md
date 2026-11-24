# RayTech Log Viewer (`raytech/raytech-log-viewer`)

<p align="center">
  <img src="public/img/default_logo.jpg" alt="RayTech Log Viewer Logo" width="120" style="border-radius: 12px; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
</p>

<p align="center">
  <strong>A high-performance, enterprise-grade, modern Laravel plugin for viewing all application and server logs with clean header rows, click-to-expand stack traces, newest-first sorting, live auto-refresh, encrypted production assets, and zero database setup.</strong>
</p>

<p align="center">
  <a href="https://github.com/umesh240/log-viewer"><img src="https://img.shields.io/badge/version-1.8.0-brightgreen.svg" alt="Version"></a>
  <a href="https://packagist.org/packages/raytech/raytech-log-viewer"><img src="https://img.shields.io/badge/packagist-raytech%2Fraytech--log--viewer-orange.svg" alt="Packagist"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/php-%5E8.0%20%7C%20%5E8.1%20%7C%20%5E8.2%20%7C%20%5E8.3%20%7C%20%5E8.4%20%7C%20%5E8.5-8892BF.svg" alt="PHP Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/laravel-%5E8.0%20%7C%20%5E9.0%20%7C%20%5E10.0%20%7C%20%5E11.0%20%7C%20%5E12.0-FF2D20.svg" alt="Laravel"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
</p>

---

## 📑 Table of Contents

- [Overview & UI Preview](#-overview--ui-preview)
- [Key Features](#-key-features)
- [Quick Start](#-quick-start)
- [Installation Guide](#-installation-guide)
  - [Packagist (Standard)](#1-packagist-standard)
  - [GitHub Repository (VCS)](#2-github-repository-vcs)
  - [Local Development (Path Symlink)](#3-local-development-path-symlink)
- [Configuration & Environment (`.env`)](#-configuration--environment-env)
- [Security & Access Control](#-security--access-control)
  - [Middleware Protection](#option-a-middleware-protection)
  - [Programmatic Auth Closure](#option-b-programmatic-auth-closure)
  - [IP Address Whitelisting](#ip-address-whitelisting)
  - [Password Protection & Brute-Force Lockout](#password-protection--brute-force-lockout)
  - [Sensitive Data Masking / Redaction](#sensitive-data-masking--redaction)
  - [Sensitive File Inclusion Blocker](#sensitive-file-inclusion-blocker)
  - [Encrypted Shortcut Script Injection](#encrypted-shortcut-script-injection)
- [Artisan Commands Reference](#-artisan-commands-reference)
  - [Install & Update](#install--update)
  - [Asset Compilation & Obfuscation (`build`)](#asset-compilation--obfuscation-build)
  - [Log Cleaning & Age Purging (`clear`)](#log-cleaning--age-purging-clear)
  - [Password Hashing (`hash`)](#password-hashing-hash)
  - [Clean Uninstall](#clean-uninstall)
- [Keyboard Shortcuts & UI Controls](#-keyboard-shortcuts--ui-controls)
- [Project Architecture](#-project-architecture)
- [Upgrading to Latest Release](#-upgrading-to-latest-release)
- [FAQ & Troubleshooting](#-faq--troubleshooting)
- [License & Credits](#-license--credits)

---

## 🖥️ Overview & UI Preview

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [>]  [ local.ERROR ]   2026-11-24 10:14:02   Call to undefined function str()           [7 frames]  📋 │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ▼ Stack Exception: Call to undefined function str()                                                    │
│   in storage/framework/views/lsp-ca4445c6fefa61f6.php on line 353                                     │
│ ┌──────────────────────┬──────────────────────┬──────────────────────────────────────────────────────┐ │
│ │ [Stack Trace (7)]    │ [Context JSON]       │ [Raw Log]                                            │ │
│ └──────────────────────┴──────────────────────┴──────────────────────────────────────────────────────┘ │
│   #0 app/Http/Controllers/HomeController.php:42 -> renderView()                                       │
│   #1 vendor/laravel/framework/src/Illuminate/Routing/Controller.php:54                                 │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

RayTech Log Viewer turns complex, messy, multi-gigabyte Laravel and server log files into an organized, high-density, interactive control center. It offers full accordion inspection, syntax highlighting, live auto-refresh, smart error counting, and deep search capabilities—all operating **completely independent of your application database**.

---

## 🌟 Key Features

### 📊 Clean Table Summary & Accordion Details
- **Single-Line Header Rows**: Each log record renders as a clean summary row displaying severity environment (`local.ERROR`, `production.CRITICAL`), precise timestamp, and a human-readable summary without raw JSON clutter.
- **Click-to-Expand Details**: Clicking any row smoothly reveals the full diagnostic card:
  - **Exception Card**: Red badge exception title, message, and exact `in <file> on line <line>` trigger location.
  - **Stack Trace Frames**: Formatted stack trace with blue file paths, orange line numbers, and method calls.
  - **Context JSON Tab**: Formatted, readable JSON context payload with key-value syntax styling.
  - **Raw Log Tab**: Complete verbatim record with one-click copy button.

### 🔝 Newest Logs on Top (Configurable Order)
- Displays latest log entries at the very top of the table (`desc` order) by default so developers immediately see recent errors and events without scrolling to the bottom.
- One-click toolbar toggle to switch anytime between **Newest First** and **Oldest First**. Persisted across the session.

### ⚡ Zero Database Setup & Extreme Performance
- **Zero Database Footprint**: Requires no database tables, migrations, models, or Redis queues. Operates directly on disk.
- **64KB Reverse Chunk Buffer**: Tail reader processes multi-gigabyte log files in milliseconds without exhausting PHP memory (`memory_limit`).
- **Gzip / Deflate HTTP Output Compression**: Compresses AJAX payloads by **85%–95%**, accelerating transfers on mobile and remote connections.
- **On-Demand / Lazy DOM Hydration**: Detailed stack traces render on demand, keeping initial DOM elements minimal for instant time-to-first-render.
- **60 FPS Search**: Debounced search synchronized with `window.requestAnimationFrame()` using native DOM `<mark>` nodes without `innerHTML` injection.

### 🔍 Auto-Detection for Laravel & Server Logs
- **Laravel Logs**: Discovers all files in `storage/logs/*.log`.
- **Server Logs**: Automatically detects PHP error logs (`ini_get('error_log')`), Apache (`bin/apache/*/logs`, `httpd`), Nginx, WAMP, MariaDB, MySQL, and Linux system paths (`/var/log/*`).
- **Custom Files & Directories**: Register additional paths via config or `.env` (`LOG_VIEWER_DIRS`, `LOG_VIEWER_FILES`).

### 🗜️ Compressed Log Support (`*.log.gz` / `*.gz`)
- Automatically discovers compressed log archives in both Laravel and server directories with distinctive purple `GZ` badges in the sidebar.
- Bounded sliding-window streaming tail decompression allows instant browsing without extracting full archives to disk.

### 📅 Calendar Date Picker Jump
- Jump directly to any daily log file (e.g. `laravel-2026-11-24.log`) with a single click using the compact calendar picker in the sidebar.
- Auto-selects the date of the active daily log and filters the sidebar in real time.

### 🏷️ Multi-Level Log Severity Filtering
- Detects `EMERGENCY`, `ALERT`, `CRITICAL`, `ERROR`, `WARNING`, `NOTICE`, `INFO`, `DEBUG`, and `TRACE`.
- Select multiple severity tags simultaneously (e.g. view `ERROR` + `WARN` together while hiding `INFO`/`DEBUG`). Click "All" to reset.

### 🔄 Live Auto-Refresh & State Persistence
- Seamless AJAX polling (5s, 10s, 30s, or manual) with spinning sync icon and zero page flickering.
- **Session Persistence**: Retains sort order, word wrap, active line limits (100, 500, 1000, 2500, 5000), auto-refresh intervals, and multi-level filters across refreshes within the active browser session.

### 📤 Export Filtered Logs (JSON & CSV)
- Export currently filtered or visible log records directly from the toolbar into clean `.json` or RFC 4180 `.csv` format (with UTF-8 BOM encoding for Microsoft Excel).

### 🔔 Real-Time Error Alerts
- Visual toast notification and browser tab title flashing (`🔴 (X) New Error!`) when new errors arrive during live auto-refresh.

### ⌨️ Vim & Power-User Keyboard Navigation
- Full keyboard navigation with <kbd>j</kbd>/<kbd>k</kbd> or arrow keys, <kbd>Enter</kbd> or <kbd>Space</kbd> to expand/collapse, <kbd>c</kbd> to copy, <kbd>/</kbd> to search, and <kbd>Esc</kbd> to clear.

### 🔐 Multi-Layer Security & Hardening
- **Password Protection**: Optional password challenge with secure Bcrypt/SHA-256 verification and session caching.
- **Brute-Force Lockout**: Locks out visitors and redirects to home page after 3 failed attempts (backed by Laravel `RateLimiter`).
- **Emergency Panic Hotkey (<kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>D</kbd>)**: Destroys the authenticated session and closes all open log viewer tabs across the browser instantly via `BroadcastChannel` and `localStorage`.
- **Sensitive Data Masking**: Automatically redacts passwords, bearer tokens, API keys, database credentials, card numbers, and `APP_KEY` as `[REDACTED]`.
- **Sensitive File Inclusion Blocker**: Strictly prevents inclusion or disclosure of `.env*`, `*.php`, `*.key`, `*.pem`, `*.crt`, `*.sql`, `*.sqlite`, `id_rsa*`.
- **IP Address Whitelisting**: Restrict access to exact IPs, wildcards (`192.168.1.*`), or CIDR subnets (`10.0.0.0/8`).
- **Encrypted Global Hotkey (<kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>X</kbd>)**: Opens `/log-viewer` in a new tab from any application page. Injected as heavily encrypted bytecode so the URL, trigger key, and logout endpoint are **100% hidden in HTML page source**.
- **Production Asset Encryption**: Serves compiled, encrypted, anti-beautification `.min.js` and `.min.css` bundles with zero Node.js/npm build dependencies (`php artisan raytech-log-viewer:build`).

---

## ⚡ Quick Start

### 1. Require Package
```bash
composer require raytech/raytech-log-viewer
```

### 2. Publish Config & Assets
```bash
php artisan raytech-log-viewer:install
```

### 3. Open in Browser
Navigate to:
```
https://your-app.local/log-viewer
```

---

## 📦 Installation Guide

### 1. Packagist (Standard)
```bash
composer require raytech/raytech-log-viewer
php artisan raytech-log-viewer:install
```

---

### 2. GitHub Repository (VCS)
To install the latest code directly from GitHub without waiting for a Packagist tag:

```bash
# Add GitHub VCS repository to your composer config:
composer config repositories.raytech-log-viewer vcs https://github.com/umesh240/log-viewer.git

# Require latest code from main branch:
composer require raytech/raytech-log-viewer:dev-main

# Publish assets & configuration:
php artisan raytech-log-viewer:install
```

Or manually add to your `composer.json`:
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

---

### 3. Local Development (Path Symlink)
If you are developing the package locally:

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
Run `composer update raytech/raytech-log-viewer && php artisan raytech-log-viewer:install`.

---

## ⚙️ Configuration & Environment (`.env`)

The published configuration file is located at `config/raytech-log-viewer.php`. All core settings can be customized in `.env`:

| `.env` Variable | Default | Description |
|---|---|---|
| `LOG_VIEWER_ENABLED` | `true` | Enable or disable the log viewer route |
| `LOG_VIEWER_ROUTE_PREFIX` | `log-viewer` | URL path prefix (e.g. `/log-viewer`, `/admin/logs`) |
| `LOG_VIEWER_MIDDLEWARE` | `web` | Middlewares protecting the route (e.g. `web,auth,admin`) |
| `LOG_VIEWER_ORDER` | `desc` | Default sort order (`desc` = latest logs on top, `asc` = oldest first) |
| `LOG_VIEWER_THEME` | `light` | Default theme (`light` or `dark`) |
| `LOG_VIEWER_PRIMARY_COLOR` | `#1f6feb` | Primary accent color |
| `LOG_VIEWER_TITLE` | `RayTech Log Viewer` | Browser tab title and topbar heading |
| `LOG_VIEWER_LOGO` | `vendor/raytech-log-viewer/img/default_logo.jpg` | Logo path (used for topbar header & browser favicon) |
| `LOG_VIEWER_BACK_URL` | `/` | Destination URL for the "Back to Home" button |
| `LOG_VIEWER_BACK_URL_NAME` | `Back To Home` | Label text for the back button |
| `LOG_VIEWER_SHOW_BACK_BUTTON` | `true` | Show or hide the back button in the topbar |
| `LOG_VIEWER_SHOW_SERVER_LOGS` | `false` | Enable or disable scanning server logs (Apache, Nginx, PHP error logs) |
| `LOG_VIEWER_USE_CDN` | `false` | Use external CDNs (`true`) or 100% offline bundled local assets (`false`) |
| `LOG_VIEWER_ALLOWED_IPS` | `null` | IP whitelist (`null` = open to all). Supports exact IPs, wildcards (`192.168.1.*`), and CIDR (`10.0.0.0/8`) |
| `LOG_VIEWER_REDACTION_ENABLED` | `true` | Automatically sanitize sensitive passwords, tokens, API keys, and cards |
| `LOG_VIEWER_PASSWORD_PROTECTED` | `false` | Enable or disable password challenge screen |
| `LOG_VIEWER_PASSWORD` | `test26 hash` | Hashed password (generate via `php artisan raytech-log-viewer:hash <password>`) |
| `LOG_VIEWER_MAX_ATTEMPTS` | `3` | Max failed password attempts before locking out |
| `LOG_VIEWER_LOCKOUT_REDIRECT` | `/` | Redirect URL upon lockout (default: home page) |
| `LOG_VIEWER_DESTROY_SHORTCUT_KEY` | `d` | Emergency hotkey key (<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>D</kbd>) to destroy session and close all open tabs |
| `LOG_VIEWER_SHORTCUT_ENABLED` | `false` | Enable global hotkey (<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>X</kbd>) across your app |
| `LOG_VIEWER_SHORTCUT_ENCRYPT` | `true` | Encrypt & obfuscate the injected hotkey script so secret keys and URLs are hidden in HTML source |
| `LOG_VIEWER_SHORTCUT_KEY` | `x` | Character key for the global open shortcut |
| `LOG_VIEWER_ALLOW_DOWNLOAD` | `true` | Allow downloading raw log files |
| `LOG_VIEWER_ALLOW_CLEAR` | `true` | Allow clearing / truncating log files |
| `LOG_VIEWER_ALLOW_DAYS_CLEANUP` | `false` | Guard allowing terminal cleanup of logs older than X days (`--days=30`) |
| `LOG_VIEWER_ALLOW_RAW` | `true` | Allow opening unformatted raw text in a new tab |
| `LOG_VIEWER_DIRS` | `null` | Comma-separated list of extra directories to scan |
| `LOG_VIEWER_FILES` | `null` | Comma-separated list of extra log files to scan |

---

## 🔒 Security & Access Control

### Option A: Middleware Protection
Protect the route using standard Laravel middleware in `.env`:
```env
LOG_VIEWER_MIDDLEWARE="web,auth,can:view-logs"
```

---

### Option B: Programmatic Auth Closure
Define access restrictions programmatically in `app/Providers/AppServiceProvider.php`:

```php
use Raytech\LogViewer\Facades\RaytechLogViewer;

public function boot(): void
{
    RaytechLogViewer::auth(function ($request) {
        $user = $request->user();
        return $user && in_array($user->email, [
            'admin@yourdomain.com',
            'lead-dev@yourdomain.com',
        ]);
    });
}
```

---

### IP Address Whitelisting
Restrict access to authorized office IPs, developer machines, or VPN subnets:

```env
LOG_VIEWER_ALLOWED_IPS="127.0.0.1, ::1, 192.168.1.*, 10.0.0.0/8"
```
*When `LOG_VIEWER_ALLOWED_IPS` is `null` or empty, access is open to all (or governed by middleware).*

---

### Password Protection & Brute-Force Lockout
Enable a standalone password protection screen independent of Laravel database authentication:

1. Generate a secure hash:
   ```bash
   php artisan raytech-log-viewer:hash mySuperSecretPassword!
   ```
2. Add to `.env`:
   ```env
   LOG_VIEWER_PASSWORD_PROTECTED=true
   LOG_VIEWER_PASSWORD="$2y$12$e8g..."
   ```
3. Rate limiting automatically locks out any visitor after 3 failed attempts and redirects them away.

---

### Sensitive Data Masking / Redaction
Sensitive values in log summaries, stack traces, and context JSON are automatically replaced with `[REDACTED]`:
- Passwords (`password`, `password_confirmation`, `secret`)
- Bearer tokens (`Bearer eyJ...`, `token`, `access_token`)
- API keys (`api_key`, `client_secret`, `secret_key`)
- Database credentials (`DB_PASSWORD`, `DATABASE_URL`)
- Credit card numbers (13–16 digit PANs with separators)
- Laravel application key (`APP_KEY`)

To customize redaction rules, edit `'redaction'` in `config/raytech-log-viewer.php`.

---

### Sensitive File Inclusion Blocker
The scanner strictly blocks inclusion or inspection of sensitive system and credential files:
```
.env, .env.*, *.php, *.key, *.pem, *.crt, *.sql, *.sqlite, *.db, id_rsa, id_rsa.*
```
Any custom path pointing to sensitive files will be rejected with `Access denied`.

---

### Encrypted Shortcut Script Injection
When `LOG_VIEWER_SHORTCUT_ENABLED=true`, a secret hotkey listener (<kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>X</kbd>) is injected before `</body>`:
- **Encrypted by default (`LOG_VIEWER_SHORTCUT_ENCRYPT=true`)**: The script is compiled and obfuscated as a dynamic hex ciphertext stream.
- **Zero plain text**: Anyone inspecting "View Page Source" will **not** find `/log-viewer`, the trigger key, the emergency logout route, or your host domain.

---

## 💻 Artisan Commands Reference

### Install & Update

| Command | Description |
|---|---|
| `php artisan raytech-log-viewer:install` | Publishes config & assets to `public/vendor/raytech-log-viewer/` |
| `php artisan raytech-log-viewer:install --force` | Overwrites existing published config and assets |
| `php artisan raytech-log-viewer:update` | Updates published CSS, JS, and image assets |
| `php artisan raytech-log-viewer:update --config` | Updates published `config/raytech-log-viewer.php` with latest defaults |
| `php artisan raytech-log-viewer:update --all` | Updates both assets and configuration simultaneously |

---

### Asset Compilation & Obfuscation (`build`)
RayTech Log Viewer includes a built-in pure PHP asset compiler. You can edit the clean source files (`public/js/raytech-log-viewer.js` and `public/css/raytech-log-viewer.css`) and recompile encrypted production bundles **with zero Node.js or npm dependencies**:

```bash
# Recompile both CSS (minified) and JS (encrypted & obfuscated):
php artisan raytech-log-viewer:build

# Recompile CSS only:
php artisan raytech-log-viewer:build --css

# Recompile JS only:
php artisan raytech-log-viewer:build --js
```

---

### Log Cleaning & Age Purging (`clear`)

```bash
# Truncate active laravel.log file:
php artisan raytech-log-viewer:clear

# Truncate a specific log file:
php artisan raytech-log-viewer:clear laravel-2026-11-24.log

# Truncate ALL discovered log files:
php artisan raytech-log-viewer:clear --all

# Safely purge log files older than X days (guarded by LOG_VIEWER_ALLOW_DAYS_CLEANUP=true):
php artisan raytech-log-viewer:clear --days=30
```

> 🛡️ **Safety Guard**: `php artisan raytech-log-viewer:clear --days=30` will refuse to run unless explicitly enabled in your configuration (`'allow_days_cleanup' => true` or `LOG_VIEWER_ALLOW_DAYS_CLEANUP=true`), preventing accidental data loss in production.

---

### Password Hashing (`hash`)

Generate secure password hashes for `LOG_VIEWER_PASSWORD` in `.env`:

```bash
# Generate Bcrypt hash:
php artisan raytech-log-viewer:hash myPassword123

# Generate SHA-256 hash:
php artisan raytech-log-viewer:hash myPassword123 --algo=sha256
```

---

### Clean Uninstall

```bash
# Cleanly removes all published assets, views, and configuration:
php artisan raytech-log-viewer:uninstall

# Removes published assets while keeping your customized config file:
php artisan raytech-log-viewer:uninstall --keep-config
```

---

## ⌨️ Keyboard Shortcuts & UI Controls

### Global & Security
| Shortcut | Action |
|---|---|
| <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>X</kbd> *(or <kbd>Cmd</kbd> on Mac)* | **Global Secret Hotkey**: Opens Log Viewer in a new tab from any application page |
| <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>D</kbd> | **Emergency Panic Destroy**: Immediately destroys authenticated session and closes all open log viewer tabs across the browser |
| Click `Lock Session` (Toolbar) | Destroys current session and returns to password screen |

### Navigation & Inspection
| Shortcut / Control | Action |
|---|---|
| Click row / <kbd>Enter</kbd> / <kbd>Space</kbd> | Toggle expand/collapse detailed stack trace card |
| <kbd>j</kbd> / <kbd>k</kbd> or <kbd>↓</kbd> / <kbd>↑</kbd> | Navigate to next / previous log entry |
| <kbd>c</kbd> | Copy selected log record body to clipboard |
| <kbd>/</kbd> or <kbd>Ctrl</kbd> + <kbd>F</kbd> | Focus client-side search input |
| <kbd>Esc</kbd> | Clear search input or deselect active log entry |
| <kbd>Ctrl</kbd> + <kbd>B</kbd> | Collapse / Expand sidebar |
| Click Moon / Sun (Topbar) | Toggle Dark / Light theme |
| Click `Newest First` / `Oldest First` | Toggle chronological sort order |
| Click `Expand All` / `Collapse All` | Bulk expand or collapse all visible rows |
| Click `Copy Log` | Copy visible filtered logs or individual record to clipboard |

---

## 📂 Project Architecture

```
log_plugin/
├── composer.json                       # Package manifest, dependencies, auto-discovery
├── README.md                           # Complete documentation & guide
├── LICENSE                             # MIT License
├── config/
│   └── raytech-log-viewer.php          # Comprehensive publishable configuration
├── routes/
│   └── web.php                         # Route definitions (viewer, API, assets, auth, clear)
├── public/
│   ├── css/
│   │   ├── raytech-log-viewer.css      # Clean, readable CSS source (development)
│   │   └── raytech-log-viewer.min.css  # Safe minified production stylesheet
│   ├── js/
│   │   ├── raytech-log-viewer.js       # Clean, readable JS source (development)
│   │   └── raytech-log-viewer.min.js   # Encrypted, obfuscated production bundle
│   ├── img/
│   │   └── default_logo.jpg            # Canonical logo asset (topbar header & tab favicon)
│   └── vendor/
│       └── fontawesome-free/           # Bundled offline icons (zero CDN dependency)
├── src/
│   ├── RaytechLogViewerServiceProvider.php # Auto-discovery, Blade directives & bindings
│   ├── RaytechLogViewer.php            # Core runtime manager & auth gates
│   ├── Facades/
│   │   └── RaytechLogViewer.php        # Laravel Facade accessor
│   ├── Services/
│   │   ├── AssetCompiler.php           # Pure PHP JS obfuscator & CSS minifier
│   │   ├── LogFileManager.php          # File scanner & fast chunked tail reader
│   │   └── LogParser.php               # Parser (levels, dates, messages, stack traces)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── RaytechLogViewerController.php # Web controller & protected asset stream
│   │   └── Middleware/
│   │       └── InjectLogViewerShortcutMiddleware.php # Auto-injects encrypted global shortcut
│   └── Console/
│       ├── InstallCommand.php          # raytech-log-viewer:install
│       ├── UpdateCommand.php           # raytech-log-viewer:update
│       ├── BuildCommand.php            # raytech-log-viewer:build
│       ├── ClearCommand.php            # raytech-log-viewer:clear
│       ├── HashPasswordCommand.php     # raytech-log-viewer:hash
│       └── UninstallCommand.php        # raytech-log-viewer:uninstall
└── resources/
    └── views/
        ├── index.blade.php             # Main high-density Log Viewer UI (loads .min assets)
        └── auth.blade.php              # Password challenge view (loads .min assets)
```

---

## 🔄 Upgrading to Latest Release

When updating the package:

```bash
# 1. Update composer package
composer update raytech/raytech-log-viewer

# 2. Re-publish latest encrypted assets (CSS, JS, logo)
php artisan raytech-log-viewer:update

# 3. (Optional) Re-publish latest config keys
php artisan raytech-log-viewer:update --config
```

---

## ❓ FAQ & Troubleshooting

#### Q: How can I change the secret hotkey from <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>X</kbd> to something else?
A: In your `.env`, set:
```env
LOG_VIEWER_SHORTCUT_KEY="l"
LOG_VIEWER_SHORTCUT_CTRL=true
LOG_VIEWER_SHORTCUT_SHIFT=true
LOG_VIEWER_SHORTCUT_ALT=false
```
Now pressing <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>L</kbd> will open the viewer.

#### Q: Will anyone see my secret hotkey in "View Source"?
A: **No.** When `LOG_VIEWER_SHORTCUT_ENCRYPT=true` (enabled by default), the injected script is completely encrypted as bytecode. There are no plain text URLs, keys, or endpoints in the rendered HTML.

#### Q: I modified `raytech-log-viewer.js` or `raytech-log-viewer.css`. How do I update production?
A: Run:
```bash
php artisan raytech-log-viewer:build
php artisan raytech-log-viewer:update
```
Your changes in the clean `.js` or `.css` files will be compiled into encrypted `.min.js` and minified `.min.css` bundles and deployed to your public vendor directory.

#### Q: Does this package slow down my application?
A: **No.** The tail reader reads files in reverse 64KB binary chunks and stops as soon as the line limit is satisfied. It never loads entire log files into PHP memory.

#### Q: Can I run this in air-gapped or offline enterprise environments?
A: **Yes 100%.** All FontAwesome icons, fonts, styles, and scripts are bundled locally inside the package. No external CDN calls are made when `LOG_VIEWER_USE_CDN=false`.

---

## 📄 License & Credits

**RayTech Log Viewer** is open-source software licensed under the [MIT license](LICENSE).

Copyright &copy; 2026 **RayTech**. All rights reserved.
