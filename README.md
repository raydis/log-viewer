# RayTech Log Viewer (`raytech/raytech-log-viewer`)

<p align="center">
  <img src="public/img/default_logo.png" alt="RayTech Log Viewer Logo" width="120" style="border-radius: 12px; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
</p>

<p align="center">
  <strong>A high-performance, enterprise-ready modern Laravel log viewer. Features clean header rows, click-to-expand stack traces, newest-first sorting, live auto-refresh, instant search, multi-severity filtering, and zero database setup.</strong>
</p>

<p align="center">
  <a href="https://github.com/raytech-us/log-viewer"><img src="https://img.shields.io/badge/version-1.2.0-brightgreen.svg" alt="Version"></a>
  <a href="https://packagist.org/packages/raytech/raytech-log-viewer"><img src="https://img.shields.io/badge/packagist-raytech%2Fraytech--log--viewer-orange.svg" alt="Packagist"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg" alt="PHP Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/laravel-8.0%2B-FF2D20.svg" alt="Laravel"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
</p>

---

## 📑 Table of Contents

- [Requirements](#-requirements)
- [Quick Start](#-quick-start)
- [Installation Guide](#-installation-guide)
  - [Method 1: Packagist (Standard)](#method-1-packagist-standard)
  - [Method 2: GitHub Repository (VCS)](#method-2-github-repository-vcs)
  - [Method 3: Local Path / Monorepo](#method-3-local-path--monorepo)
- [Publishing Configuration & Assets](#-publishing-configuration--assets)
- [User Guide: How to Use the Log Viewer](#-user-guide-how-to-use-the-log-viewer)
  - [Accessing the Interface](#1-accessing-the-interface)
  - [Log Navigation & File Types](#2-log-navigation--file-types)
  - [Calendar Date Picker for Daily Logs](#3-calendar-date-picker-for-daily-logs)
  - [Header Summary & Click-to-Expand Details](#4-header-summary--click-to-expand-details)
  - [Sorting: Newest First vs Oldest First](#5-sorting-newest-first-vs-oldest-first)
  - [Instant 60 FPS Search](#6-instant-60-fps-search)
  - [Multi-Level Severity Filtering](#7-multi-level-severity-filtering)
  - [Live Auto-Refresh & Real-Time Alerts](#8-live-auto-refresh--real-time-alerts)
  - [Exporting & Downloading Logs](#9-exporting--downloading-logs)
  - [Clearing / Truncating Logs](#10-clearing--truncating-logs)
  - [Dark & Light Themes](#11-dark--light-themes)
- [Configuration & Environment Variables](#-configuration--environment-variables)
  - [One-Click Copy Complete .env Configuration](#-one-click-copy-complete-env-configuration)
  - [Configuration Reference Tables](#configuration-reference-tables)
- [Security & Access Control](#-security--access-control)
  - [Option A: Route Middleware Protection](#option-a-route-middleware-protection)
  - [Option B: Programmatic Gate Closure](#option-b-programmatic-gate-closure)
  - [Option C: IP Address Whitelisting](#option-c-ip-address-whitelisting)
  - [Option D: Standalone Password Protection & Lockout](#option-d-standalone-password-protection--lockout)
  - [Emergency Panic Destroy Shortcut](#emergency-panic-destroy-shortcut)
  - [Sensitive Data Masking (Redaction)](#sensitive-data-masking-redaction)
  - [Sensitive File Protection](#sensitive-file-protection)
- [Global Secret Hotkey (Open from Anywhere)](#-global-secret-hotkey-open-from-anywhere)
- [Artisan Commands Reference](#-artisan-commands-reference)
- [Keyboard Shortcuts Cheat Sheet](#-keyboard-shortcuts-cheat-sheet)
- [Upgrading](#-upgrading)
- [FAQ & Troubleshooting](#-faq--troubleshooting)
- [License](#-license)

---

## 📋 Requirements

| Requirement | Supported Versions |
|---|---|
| **PHP** | `8.0+` (PHP 8.0, 8.1, 8.2, 8.3, 8.4, 8.5+) |
| **Laravel Framework** | `8.0+` (Laravel 8, 9, 10, 11, 12, 13+) |
| **Database** | **None** (zero tables, zero migrations, zero queries) |
| **Asset Compilation** | **None** (all styles, scripts, and fonts are 100% pre-packaged and offline) |

---

## ⚡ Quick Start

```bash
# 1. Require package via Composer (or :dev-main for development branch)
composer require raytech/raytech-log-viewer
# Or: composer require raytech/raytech-log-viewer:dev-main

# 2. Publish config and assets
php artisan raytech-log-viewer:install

# 3. Open in your browser
# https://your-domain.test/log-viewer
```

---

## 📦 Installation Guide

### Method 1: Packagist (Standard)

Install via Composer in any standard Laravel application:

```bash
# Standard release:
composer require raytech/raytech-log-viewer

# Or if requiring the development branch directly:
composer require raytech/raytech-log-viewer:dev-main

# Publish configuration and assets:
php artisan raytech-log-viewer:install
```

---

### Method 2: GitHub Repository (VCS)

If installing directly from a GitHub repository before a release tag is registered:

1. Add the repository to your application's `composer.json`:
   ```bash
   composer config repositories.raytech-log-viewer vcs https://github.com/raytech-us/log-viewer.git
   ```

2. Require the development or main branch:
   ```bash
   composer require raytech/raytech-log-viewer:dev-main
   ```

3. Publish configuration and assets:
   ```bash
   php artisan raytech-log-viewer:install
   ```

---

### Method 3: Local Path / Monorepo

If linking the package locally for private or local development:

1. Add the path repository to your application's `composer.json`:
   ```json
   {
       "repositories": [
           {
               "type": "path",
               "url": "../path/to/log_plugin",
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

2. Run:
   ```bash
   composer update raytech/raytech-log-viewer
   php artisan raytech-log-viewer:install
   ```

---

## 🚀 Publishing Configuration & Assets

Run the installation command to publish the package configuration and public assets:

```bash
php artisan raytech-log-viewer:install
```

To overwrite existing published files (e.g. after updating), use the `--force` flag:

```bash
php artisan raytech-log-viewer:install --force
```

This publishes:
- `config/raytech-log-viewer.php` - Main configuration file.
- `public/vendor/raytech-log-viewer/` - Minified CSS, JS, branding images, and local offline FontAwesome webfonts.

---

## 🖥️ User Guide: How to Use the Log Viewer

### 1. Accessing the Interface

Navigate to the log viewer URL in your browser:
```
https://your-laravel-domain.test/log-viewer
```
*(The URL path is configurable via `LOG_VIEWER_ROUTE_PREFIX` in `.env`)*.

---

### 2. Log Navigation & File Types

The left sidebar categorizes all available logs:

- **Laravel Application Logs**: All log files found in `storage/logs/` (e.g. `laravel.log`, `laravel-2026-11-24.log`, `worker.log`).
- **Compressed Log Archives (`*.log.gz` / `*.gz`)**: Rotated compressed logs are automatically detected and badged with a purple `GZ` indicator. You can click and inspect them immediately without manually uncompressing them to disk.
- **Server Logs** *(Optional)*: If enabled (`LOG_VIEWER_SHOW_SERVER_LOGS=true`), automatically discovers web server logs (Apache, Nginx, PHP error logs, WAMP/Laragon/XAMPP, MySQL/MariaDB error logs).
- **Custom Files & Folders**: Any additional paths specified in your config or `.env` (`LOG_VIEWER_DIRS`, `LOG_VIEWER_FILES`).

Click any log file in the sidebar to load its entries.

---

### 3. Calendar Date Picker for Daily Logs

When viewing daily rotated logs (such as `laravel-YYYY-MM-DD.log`):
- Click the compact **Calendar Picker** in the sidebar.
- Select any date to instantly jump to the log file for that day.
- Dates with existing logs are highlighted for fast selection.

---

### 4. Header Summary & Click-to-Expand Details

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [>]  [ local.ERROR ]   2026-11-24 10:14:02   Call to undefined function str()           [7 frames]  📋 │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ▼ Stack Exception: Call to undefined function str()                                                    │
│   in storage/framework/views/app-view.php on line 35                                                   │
│ ┌──────────────────────┬──────────────────────┬──────────────────────────────────────────────────────┐ │
│ │ [Stack Trace (7)]    │ [Context JSON]       │ [Raw Log]                                            │ │
│ └──────────────────────┴──────────────────────┴──────────────────────────────────────────────────────┘ │
│   #0 app/Http/Controllers/HomeController.php:42 -> renderView()                                       │
│   #1 vendor/laravel/framework/src/Illuminate/Routing/Controller.php:54                                 │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

- **Single-Line Header Row**: Every log entry displays an environment & severity badge (e.g., `local.ERROR`, `production.CRITICAL`), a timestamp, and a clean message preview.
- **Click to Expand**: Click anywhere on a row (or press <kbd>Enter</kbd> / <kbd>Space</kbd>) to expand the full accordion card:
  - **Exception Location**: Highlights the exception class, message, and trigger file with line number.
  - **Stack Trace Frames**: Formatted trace with distinct colors for file paths, line numbers, and function calls.
  - **Context JSON Tab**: Color-coded JSON viewer for extra payload data passed with the log.
  - **Raw Log Tab**: The original log line as written to disk.
- **Expand All / Collapse All**: Use the toolbar buttons to open or close all visible rows at once.

---

### 5. Sorting: Newest First vs Oldest First

- By default, the newest log entries appear at the very top (`desc` order) so you can immediately see the most recent errors without scrolling down.
- Click **Newest First** / **Oldest First** in the top toolbar to switch sort directions anytime. Your choice is preserved across page refreshes.

---

### 6. Instant 60 FPS Search

- Press <kbd>/</kbd> or click the search bar in the toolbar.
- Type any keyword, class name, error message, or timestamp.
- Results filter in real-time with matched terms highlighted in yellow.
- Press <kbd>Esc</kbd> to quickly clear the search filter.

---

### 7. Multi-Level Severity Filtering

Filter visible records by severity level:
- Click any severity pill in the toolbar: **Emergency**, **Alert**, **Critical**, **Error**, **Warning**, **Notice**, **Info**, **Debug**, or **Trace**.
- **Multi-Select**: Click multiple levels to view them together (e.g., display both `ERROR` and `WARNING` while hiding `INFO`).
- Click **All** to clear filters and show all severity levels.

---

### 8. Live Auto-Refresh & Real-Time Alerts

- In the toolbar dropdown, choose your auto-refresh interval: **Manual**, **5 seconds**, **10 seconds**, or **30 seconds**.
- The page updates in the background via smooth AJAX requests without flickering or page reloads.
- **Real-Time Error Alert**: If new errors arrive while the tab is active or in the background, a toast alert notifies you and the browser tab flashes with a red alert badge (`🔴 (X) New Error!`).

---

### 9. Exporting & Downloading Logs

- **Export CSV**: Click **Export** &rarr; **CSV** to download visible/filtered log entries as an RFC 4180 compliant CSV file (with UTF-8 BOM encoding for seamless opening in Microsoft Excel).
- **Export JSON**: Click **Export** &rarr; **JSON** to download records as structured, formatted JSON.
- **Download Full Log**: Click **Download** in the toolbar to save the complete, original `.log` or `.gz` file to your computer.
- **View Raw Text**: Click **Raw** to open the unformatted log text in a new browser tab.
- **Copy to Clipboard**:
  - Click the clipboard icon 📋 on any row (or press <kbd>c</kbd>) to copy that log entry.
  - Click **Copy Log** in the toolbar to copy all currently visible records.

---

### 10. Clearing / Truncating Logs

- Click **Clear Log** in the top toolbar.
- A confirmation prompt will ask to confirm the truncation.
- Upon confirmation, the active log file is truncated to 0 bytes without deleting the file itself.
- *(Can be disabled by setting `LOG_VIEWER_ALLOW_CLEAR=false` in `.env`)*.

---

### 11. Dark & Light Themes

- Click the **Sun / Moon** icon in the topbar to toggle between Dark Mode and Light Mode.
- Your theme selection is saved in browser storage and remembered across sessions.

---

## ⚙️ Configuration & Environment Variables

All settings can be configured via environment variables in your application's `.env` file or by editing `config/raytech-log-viewer.php`.

> [!TIP]
> **Keep Only What You Need**: You **do not need to copy or keep all variables** in your `.env` file! All settings already come with safe, production-tested default values out of the box. You only need to add and customize the specific variables required for your project (e.g., setting `LOG_VIEWER_MIDDLEWARE` or changing `LOG_VIEWER_ROUTE_PREFIX`).

---

### 📋 One-Click Copy Complete .env Configuration

If you prefer to have all available settings readily documented in your `.env` file, click the copy button on the snippet below and paste it directly into your `.env`:

```dotenv
# ==============================================================================
# RayTech Log Viewer - Complete Environment Configuration
# ==============================================================================
# Note: You only need to keep the variables you want to customize.
# Any variable omitted here will automatically use its package default.
# ==============================================================================

# --- General & Routing ---
LOG_VIEWER_ENABLED=true
LOG_VIEWER_ROUTE_PREFIX=log-viewer
LOG_VIEWER_MIDDLEWARE=web
# LOG_VIEWER_DOMAIN=admin.your-app.com

# --- UI Theme & Branding ---
LOG_VIEWER_THEME=light
LOG_VIEWER_PRIMARY_COLOR="#1f6feb"
LOG_VIEWER_TITLE="RayTech Log Viewer"
LOG_VIEWER_LOGO="vendor/raytech-log-viewer/img/default_logo.png"
LOG_VIEWER_SHOW_BACK_BUTTON=true
LOG_VIEWER_BACK_URL="/"
LOG_VIEWER_BACK_URL_NAME="Back To Home"

# --- Log Discovery & Directories ---
LOG_VIEWER_SHOW_SERVER_LOGS=false
LOG_VIEWER_SCAN_SERVER_LOGS=false
# LOG_VIEWER_DIRS="/var/log/custom,storage/logs/workers"
# LOG_VIEWER_FILES="storage/logs/cron.log,storage/logs/queue.log"

# --- Features & Action Controls ---
LOG_VIEWER_ORDER=desc
LOG_VIEWER_ALLOW_DOWNLOAD=true
LOG_VIEWER_ALLOW_CLEAR=true
LOG_VIEWER_ALLOW_DAYS_CLEANUP=false
LOG_VIEWER_ALLOW_RAW=true
LOG_VIEWER_USE_CDN=false

# --- Global Secret Shortcut (Open from anywhere via Ctrl+Shift+X) ---
LOG_VIEWER_SHORTCUT_ENABLED=false
LOG_VIEWER_SHORTCUT_AUTO_INJECT=true
LOG_VIEWER_SHORTCUT_ENCRYPT=true
LOG_VIEWER_SHORTCUT_KEY=x
LOG_VIEWER_SHORTCUT_CTRL=true
LOG_VIEWER_SHORTCUT_SHIFT=true
LOG_VIEWER_SHORTCUT_ALT=false
LOG_VIEWER_SHORTCUT_NEW_TAB=true

# --- Security, Password Protection & Rate Limiting ---
LOG_VIEWER_PASSWORD_PROTECTED=false
# Default hash of 'test26'. Generate custom hash: php artisan raytech-log-viewer:hash <password>
LOG_VIEWER_PASSWORD="$2y$10$ZfUboNTMgBUhU5kVfoWyTu8X4B4WMr7trhWrjZGnpm4u9PLiDfv2W"
# IP Whitelist: leave empty to allow all, or set exact IPs, wildcards ('192.168.1.*'), or CIDR ('10.0.0.0/8')
# LOG_VIEWER_ALLOWED_IPS="127.0.0.1, ::1, 192.168.1.*"
LOG_VIEWER_MAX_ATTEMPTS=3
LOG_VIEWER_LOCKOUT_REDIRECT="/"
LOG_VIEWER_LOCKOUT_DURATION=60
LOG_VIEWER_DESTROY_SHORTCUT_ENABLED=true
LOG_VIEWER_DESTROY_SHORTCUT_KEY=d
LOG_VIEWER_REDACTION_ENABLED=true
```

---

### Configuration Reference Tables

#### General & Routing

| `.env` Variable | Default | Description |
|---|---|---|
| `LOG_VIEWER_ENABLED` | `true` | Enable or disable the log viewer entirely |
| `LOG_VIEWER_ROUTE_PREFIX` | `log-viewer` | URL path prefix (e.g. `/log-viewer` or `/admin/logs`) |
| `LOG_VIEWER_MIDDLEWARE` | `web` | Middlewares protecting the route (e.g. `web,auth,admin`) |
| `LOG_VIEWER_DOMAIN` | `null` | Optional domain or subdomain restriction |

#### Appearance & Branding

| `.env` Variable | Default | Description |
|---|---|---|
| `LOG_VIEWER_THEME` | `light` | Default theme mode (`light` or `dark`) |
| `LOG_VIEWER_PRIMARY_COLOR` | `#1f6feb` | Primary accent color for buttons and badges |
| `LOG_VIEWER_TITLE` | `RayTech Log Viewer` | Browser tab title and topbar heading |
| `LOG_VIEWER_LOGO` | `vendor/raytech-log-viewer/img/default_logo.png` | Logo path (used for header and favicon) |
| `LOG_VIEWER_SHOW_BACK_BUTTON` | `true` | Show or hide the "Back to App" button |
| `LOG_VIEWER_BACK_URL` | `/` | Destination URL when clicking the back button |
| `LOG_VIEWER_BACK_URL_NAME` | `Back To Home` | Label text on the back button |

#### Log Files & Discovery

| `.env` Variable | Default | Description |
|---|---|---|
| `LOG_VIEWER_SHOW_SERVER_LOGS` | `false` | Show or hide server logs in the sidebar |
| `LOG_VIEWER_SCAN_SERVER_LOGS` | `false` | Scan Apache, Nginx, PHP, WAMP, MySQL log directories |
| `LOG_VIEWER_DIRS` | `null` | Comma-separated list of extra directories to scan |
| `LOG_VIEWER_FILES` | `null` | Comma-separated list of specific log files to include |

#### Features & Behavior

| `.env` Variable | Default | Description |
|---|---|---|
| `LOG_VIEWER_ORDER` | `desc` | Default sort order (`desc` = newest logs on top, `asc` = oldest first) |
| `LOG_VIEWER_ALLOW_DOWNLOAD` | `true` | Allow downloading raw log files |
| `LOG_VIEWER_ALLOW_CLEAR` | `true` | Allow truncating log files |
| `LOG_VIEWER_ALLOW_RAW` | `true` | Allow viewing unformatted raw logs in browser |
| `LOG_VIEWER_ALLOW_DAYS_CLEANUP`| `false` | Guard allowing age-based log purging via artisan command |
| `LOG_VIEWER_USE_CDN` | `false` | Use local offline assets (`false`) or external CDN (`true`) |

#### Global Secret Shortcut

| `.env` Variable | Default | Description |
|---|---|---|
| `LOG_VIEWER_SHORTCUT_ENABLED` | `false` | Enable global hotkey (<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>X</kbd>) across app |
| `LOG_VIEWER_SHORTCUT_AUTO_INJECT` | `true` | Automatically inject script into HTML web responses |
| `LOG_VIEWER_SHORTCUT_ENCRYPT` | `true` | Obfuscate script so secret keys and URLs are hidden in HTML source |
| `LOG_VIEWER_SHORTCUT_KEY` | `x` | Trigger key character |
| `LOG_VIEWER_SHORTCUT_CTRL` | `true` | Require <kbd>Ctrl</kbd> (or <kbd>Cmd</kbd> on Mac) |
| `LOG_VIEWER_SHORTCUT_SHIFT` | `true` | Require <kbd>Shift</kbd> key |
| `LOG_VIEWER_SHORTCUT_ALT` | `false` | Require <kbd>Alt</kbd> key |
| `LOG_VIEWER_SHORTCUT_NEW_TAB` | `true` | Open log viewer in a new browser tab |

#### Security, Password & Lockout

| `.env` Variable | Default | Description |
|---|---|---|
| `LOG_VIEWER_PASSWORD_PROTECTED` | `false` | Enable standalone password challenge screen |
| `LOG_VIEWER_PASSWORD` | `test26 hash` | Bcrypt/SHA-256 hashed password |
| `LOG_VIEWER_ALLOWED_IPS` | `null` | IP whitelist (`null` = open to all). Supports exact IPs, wildcards, and CIDR |
| `LOG_VIEWER_MAX_ATTEMPTS` | `3` | Max failed password attempts before lockout |
| `LOG_VIEWER_LOCKOUT_REDIRECT` | `/` | Redirect URL upon lockout |
| `LOG_VIEWER_LOCKOUT_DURATION` | `60` | Lockout duration in seconds |
| `LOG_VIEWER_DESTROY_SHORTCUT_KEY` | `d` | Emergency panic key (<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>D</kbd>) to kill session & close tabs |
| `LOG_VIEWER_REDACTION_ENABLED` | `true` | Redact sensitive passwords, tokens, API keys, database credentials, and cards |

---

## 🔒 Security & Access Control

RayTech Log Viewer offers multiple layers of access control:

### Option A: Route Middleware Protection

Protect access using your existing Laravel authentication or role middleware by setting `LOG_VIEWER_MIDDLEWARE` in `.env`:

```env
# Require authenticated user with an admin role
LOG_VIEWER_MIDDLEWARE="web,auth,can:view-logs"
```

---

### Option B: Programmatic Gate Closure

Define custom access rules in `app/Providers/AppServiceProvider.php` using the `RaytechLogViewer` facade:

```php
use Raytech\LogViewer\Facades\RaytechLogViewer;

public function boot(): void
{
    RaytechLogViewer::auth(function ($request) {
        $user = $request->user();
        
        // Return true to allow, false to deny access
        return $user && in_array($user->email, [
            'admin@yourcompany.com',
            'developer@yourcompany.com',
        ]);
    });
}
```

---

### Option C: IP Address Whitelisting

Restrict log viewer access to specific developer IP addresses, office networks, or VPN subnets in `.env`:

```env
# Exact IPs, wildcards, and CIDR subnets are supported:
LOG_VIEWER_ALLOWED_IPS="127.0.0.1, ::1, 192.168.1.*, 10.0.0.0/8"
```

*When `LOG_VIEWER_ALLOWED_IPS` is left empty or not set, access is permitted from any IP (subject to route middleware).*

---

### Option D: Standalone Password Protection & Lockout

Enable an independent password challenge screen that operates without database users:

1. **Generate a secure hash**:
   ```bash
   php artisan raytech-log-viewer:hash yourPasswordHere
   ```

2. **Add to `.env`**:
   ```env
   LOG_VIEWER_PASSWORD_PROTECTED=true
   LOG_VIEWER_PASSWORD="$2y$12$..."
   LOG_VIEWER_MAX_ATTEMPTS=3
   LOG_VIEWER_LOCKOUT_REDIRECT="/"
   ```

3. **Brute-Force Protection**: Visitors receive up to 3 attempts. After 3 incorrect passwords, they are automatically locked out and redirected to the configured destination URL.

---

### Emergency Panic Destroy Shortcut

If password protection is enabled, you can immediately lock and close all open log viewer tabs across the entire browser in an emergency:

- Press <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>D</kbd> (or click **Lock Session** in the topbar).
- The authenticated session is instantly destroyed and all open log viewer browser tabs automatically close.

---

### Sensitive Data Masking (Redaction)

To prevent accidental leakage of sensitive credentials, the viewer automatically masks sensitive parameters in error messages, stack traces, and context JSON with `[REDACTED]`:
- Passwords & confirmation tokens (`password`, `password_confirmation`, `secret`)
- Authorization headers & Bearer tokens (`Bearer eyJ...`, `access_token`, `api_key`)
- Database credentials & connection strings (`DB_PASSWORD`, `DATABASE_URL`)
- Credit card numbers (13–16 digit PAN patterns)
- Laravel encryption keys (`APP_KEY`)

To customize redaction, configure `'redaction'` in `config/raytech-log-viewer.php`.

---

### Sensitive File Protection

The log scanner strictly rejects and forbids any attempts to view sensitive configuration or key files:
```
.env, .env.*, *.php, *.key, *.pem, *.crt, *.sql, *.sqlite, *.db, id_rsa, id_rsa.*
```
Any custom path pointing to these files will be blocked with an `Access Denied` error.

---

## ⚡ Global Secret Hotkey (Open from Anywhere)

You can open the Log Viewer from **any page** in your application using a hidden global keyboard shortcut without exposing any visible links to regular users:

1. Enable the shortcut in `.env`:
   ```env
   LOG_VIEWER_SHORTCUT_ENABLED=true
   LOG_VIEWER_SHORTCUT_KEY="x"
   LOG_VIEWER_SHORTCUT_CTRL=true
   LOG_VIEWER_SHORTCUT_SHIFT=true
   LOG_VIEWER_SHORTCUT_ALT=false
   ```

2. Press <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>X</kbd> on any page of your app to instantly open `/log-viewer` in a new tab.

> 🔒 **Hidden in HTML Source**: When `LOG_VIEWER_SHORTCUT_ENCRYPT=true` (default), the injected shortcut script is obfuscated so the trigger key, target URL, and endpoints are hidden from browser "View Page Source".

---

## 💻 Artisan Commands Reference

The package provides several command-line tools for maintenance and automation:

### 1. Installation

```bash
# Publish configuration and assets
php artisan raytech-log-viewer:install

# Overwrite existing configuration and assets
php artisan raytech-log-viewer:install --force
```

---

### 2. Updating Assets & Config

```bash
# Update published public assets (CSS, JS, images)
php artisan raytech-log-viewer:update

# Update published config/raytech-log-viewer.php with latest defaults
php artisan raytech-log-viewer:update --config

# Update both assets and configuration simultaneously
php artisan raytech-log-viewer:update --all
```

---

### 3. Log Cleaning & Age Purging

```bash
# Truncate the main laravel.log file
php artisan raytech-log-viewer:clear

# Truncate a specific log file
php artisan raytech-log-viewer:clear laravel-2026-11-24.log

# Truncate ALL detected log files
php artisan raytech-log-viewer:clear --all

# Purge daily rotated log files older than X days (e.g. 30 days)
php artisan raytech-log-viewer:clear --days=30
```

> 🛡️ **Safety Guard**: The `--days` option requires `LOG_VIEWER_ALLOW_DAYS_CLEANUP=true` in your `.env` to prevent accidental deletion in production environments.

---

### 4. Password Hash Generator

Generate secure hashes for `LOG_VIEWER_PASSWORD`:

```bash
# Generate Bcrypt hash (Default)
php artisan raytech-log-viewer:hash myPassword123

# Generate SHA-256 hash
php artisan raytech-log-viewer:hash myPassword123 --algo=sha256

# Generate Laravel encrypted string
php artisan raytech-log-viewer:hash myPassword123 --algo=encrypt
```

---

### 5. Uninstalling

To cleanly and completely remove RayTech Log Viewer from your Laravel application:

```bash
# Step 1: Remove all published assets, views, and configuration files
php artisan raytech-log-viewer:uninstall

# (Optional) If you want to retain your customized config file:
# php artisan raytech-log-viewer:uninstall --keep-config

# Step 2: Completely remove the package dependency from Composer
composer remove raytech/raytech-log-viewer
```

> [!NOTE]
> The uninstaller safely removes only published package files and flushes caches. It never touches or deletes anything inside your `.env` file. Running `composer remove` completely deletes all package source code and routes from the host application.

---

## ⌨️ Keyboard Shortcuts Cheat Sheet

| Shortcut | Action |
|---|---|
| <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>X</kbd> | **Global Secret Hotkey**: Opens Log Viewer in a new browser tab from any application page |
| <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>D</kbd> | **Emergency Panic Destroy**: Destroys active session and closes all log viewer tabs immediately |
| <kbd>j</kbd> or <kbd>↓</kbd> | Select next log entry |
| <kbd>k</kbd> or <kbd>↑</kbd> | Select previous log entry |
| Click row / <kbd>Enter</kbd> / <kbd>Space</kbd> | Expand or collapse detailed stack trace accordion |
| <kbd>c</kbd> | Copy selected log message to clipboard |
| <kbd>/</kbd> or <kbd>Ctrl</kbd> + <kbd>F</kbd> | Focus the real-time search input bar |
| <kbd>Esc</kbd> | Clear search input or deselect active log record |
| <kbd>Ctrl</kbd> + <kbd>B</kbd> | Expand or collapse the sidebar |

---

## 🔄 Upgrading

When upgrading the package to a new version:

```bash
# 1. Update package via Composer
composer update raytech/raytech-log-viewer

# 2. Update published assets (CSS, JS, fonts, images)
php artisan raytech-log-viewer:update

# 3. (Optional) Check config for newly added keys
php artisan raytech-log-viewer:update --config
```

---

## ❓ FAQ & Troubleshooting

#### Q: I get a 403 Forbidden error when opening `/log-viewer`.
- If you configured `LOG_VIEWER_ALLOWED_IPS`, ensure your client IP is in the allowed whitelist.
- If using `LOG_VIEWER_MIDDLEWARE="web,auth"`, ensure you are logged into your application first.
- If using programmatic gate closure `RaytechLogViewer::auth(...)`, ensure the closure returns `true` for your user.

#### Q: Will this package slow down my application or consume high memory on large log files?
- **No.** The tail reader reads files in reverse 64KB binary chunks and stops reading once the requested number of lines (e.g. 500 lines) is reached. It never loads the entire log file into PHP memory.

#### Q: Can I run this in offline, intranet, or air-gapped environments?
- **Yes.** By default (`LOG_VIEWER_USE_CDN=false`), all FontAwesome icons, fonts, CSS styles, and scripts are bundled locally inside the package. No external network requests are made.

#### Q: How do I change the default brand logo or title?
- Set `LOG_VIEWER_TITLE="My Company Logs"` and `LOG_VIEWER_LOGO="images/my-logo.png"` in your `.env` file or `config/raytech-log-viewer.php`.

#### Q: Can I view server logs like Apache, Nginx, or PHP error logs?
- **Yes.** Set `LOG_VIEWER_SHOW_SERVER_LOGS=true` and `LOG_VIEWER_SCAN_SERVER_LOGS=true` in your `.env`. The viewer will automatically scan common server log directories and `ini_get('error_log')`.

---

## 📄 License

RayTech Log Viewer is open-source software licensed under the **[MIT License](LICENSE)**.

Copyright &copy; 2026 **RayTech**. All rights reserved.
