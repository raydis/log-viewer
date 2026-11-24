# RayTech Log Viewer — AI Agent Operating Manual & Architecture Guide

> **Target File:** `_backup/AGENT.md`  
> **Role:** Mandatory instruction manual, architectural context, and task playbooks for AI agents working on `raytech/raytech-log-viewer`.  
> **Rule for AI Agents:** Read this document whenever you begin a new task or session on this repository to understand system invariants, forbidden patterns, and standard development workflows.

---

## 📑 Table of Contents

1. [AI Agent Identity & Codebase Mission](#1-ai-agent-identity--codebase-mission)
2. [Absolute Golden Invariants (NEVER Break These)](#2-absolute-golden-invariants-never-break-these)
3. [Fast Codebase Reference for Agents](#3-fast-codebase-reference-for-agents)
4. [Internal System Architecture & Data Flow](#4-internal-system-architecture--data-flow)
   - [Reverse Binary Seek Tail Engine](#reverse-binary-seek-tail-engine)
   - [Log Entry Grouping & Parsing Pipeline](#log-entry-grouping--parsing-pipeline)
   - [Pure-PHP Asset Compilation & Encryption](#pure-php-asset-compilation--encryption)
   - [Triple-Layer Access & Security Machine](#triple-layer-access--security-machine)
   - [Multi-Tab Emergency Broadcast Shutdown](#multi-tab-emergency-broadcast-shutdown)
5. [Standard AI Agent Task Playbooks](#5-standard-ai-agent-task-playbooks)
   - [Playbook 1: Modifying Front-End Scripts or Styles](#playbook-1-modifying-front-end-scripts-or-styles)
   - [Playbook 2: Adding a New Log Driver or Format](#playbook-2-adding-a-new-log-driver-or-format)
   - [Playbook 3: Introducing New Configuration Directives](#playbook-3-introducing-new-configuration-directives)
   - [Playbook 4: Adding New Artisan Commands](#playbook-4-adding-new-artisan-commands)
   - [Playbook 5: Updating Documentation](#playbook-5-updating-documentation)
6. [Key Variable, Session & Event Dictionary](#6-key-variable-session--event-dictionary)
7. [Pre-Flight Verification Checklist for Agents](#7-pre-flight-verification-checklist-for-agents)

---

## 1. AI Agent Identity & Codebase Mission

You are an expert AI software engineer maintaining and upgrading **`raytech/raytech-log-viewer`**, an enterprise-grade Laravel package designed to view, analyze, and stream application and server logs.

### Key Characteristics:
- **Zero Database Footprint**: Never uses migrations, database connections, or tables.
- **Zero Node.js/npm Dependencies**: Uses an internal, pure-PHP AST compiler (`AssetCompiler.php`) for asset minification and dynamic XOR encryption.
- **Extreme Scale & Speed**: Reads multi-gigabyte log files in milliseconds using 64KB reverse binary seeks (`fseek`) without exceeding PHP memory limits.
- **Air-Gapped Ready**: Contains 100% offline bundled FontAwesome icons, fonts, and SweetAlert2 scripts.

---

## 2. Absolute Golden Invariants (NEVER Break These)

As an AI agent, you must strictly obey these technical rules:

1. ❌ **NEVER edit `.min.js` or `.min.css` directly!**
   - The files `public/js/raytech-log-viewer.min.js` and `public/css/raytech-log-viewer.min.css` are machine-compiled artifacts.
   - **Always edit the source files:** `public/js/raytech-log-viewer.js` and `public/css/raytech-log-viewer.css`.
   - **Then recompile:** run `php artisan raytech-log-viewer:build`.

2. ❌ **NEVER introduce database tables, migrations, or Eloquent models.**
   - All persistence must remain purely file-based, session-based, or handled via `localStorage`.

3. ❌ **NEVER introduce `npm`, `package.json`, Webpack, or Vite.**
   - All frontend compilation must be handled exclusively by `AssetCompiler.php`.

4. ❌ **NEVER load entire log files into memory using `file()`, `file_get_contents()`, or standard unbounded loops.**
   - Multi-gigabyte logs will crash PHP with memory exhaustion.
   - Always route log reads through `LogFileManager::readTail()` or `readGzTail()`.

5. ❌ **NEVER strip spaces around `+` or `-` in `AssetCompiler::minifyCss()`.**
   - Stripping spaces around arithmetic operators breaks CSS `calc()` expressions (e.g. `calc(100% - 20px)` becomes invalid syntax `calc(100%-20px)`).

6. ❌ **NEVER put developer architecture or internal package structure into `README.md`.**
   - `README.md` is strictly user-facing (Installation, Usage, Configuration, Commands).
   - Developer architecture and implementation details belong in `_backup/DEVELOPER.md`.
   - AI agent operating instructions belong in `_backup/AGENT.md`.

7. ❌ **NEVER bypass `isSensitiveFile()` checks.**
   - Any custom log file or directory path must be validated against `isSensitiveFile()` to prevent exposure of `.env*`, `*.php`, `*.key`, `*.pem`, `*.sql`, or `id_rsa*`.

---

## 3. Fast Codebase Reference for Agents

| File Path | Role & Agent Responsibility |
|---|---|
| `composer.json` | Package manifest, PSR-4 autoloading (`Raytech\LogViewer\`), supported PHP (`^8.0-^8.5`) and Laravel (`^8.0-^12.0`). |
| `config/raytech-log-viewer.php` | Canonical configuration template published to user applications. |
| `routes/web.php` | Package web route definitions (`/log-viewer`, `/auth`, `/logout`, `/clear`, `/download`, `/raw`, `/assets/{file}`). |
| `src/RaytechLogViewerServiceProvider.php` | DI container bindings, recursive configuration merging, view/route loading, and Blade directives. |
| `src/RaytechLogViewer.php` | Static runtime facade accessor, authorization gate callbacks, and hotkey renderer. |
| `src/Facades/RaytechLogViewer.php` | Laravel Facade proxying calls to `raytech-log-viewer`. |
| `src/Services/LogFileManager.php` | File scanner, sensitive file blocker, 64KB reverse chunked tail reader, and `.gz` streaming engine. |
| `src/Services/LogParser.php` | 3-tier fast log-level classifier, multiline record aggregation, stack trace frame parser, context extractor, and redaction. |
| `src/Services/AssetCompiler.php` | Pure-PHP CSS minifier and JavaScript dynamic XOR bytecode cipher. |
| `src/Http/Controllers/RaytechLogViewerController.php` | HTTP coordinator, IP/CIDR whitelist verifier, rate-limiting lockout state machine, Gzip AJAX response streamer, and asset server. |
| `src/Http/Middleware/InjectLogViewerShortcutMiddleware.php` | Global middleware injecting obfuscated hotkey listener into application HTML responses. |
| `src/Console/*.php` | Artisan commands: `InstallCommand`, `UpdateCommand`, `BuildCommand`, `ClearCommand`, `HashCommand`, `UninstallCommand`. |
| `resources/views/index.blade.php` | Main Log Viewer blade template (loads minified assets). |
| `resources/views/auth.blade.php` | Standalone password challenge screen blade template. |
| `public/js/raytech-log-viewer.js` | **Source** JavaScript application (Vanilla ES6+, debounced rAF search, lazy DOM hydration). |
| `public/css/raytech-log-viewer.css` | **Source** stylesheet with light and dark mode CSS custom properties. |
| `public/img/default_logo.png` | Default brand logo and favicon asset. |

---

## 4. Internal System Architecture & Data Flow

### Reverse Binary Seek Tail Engine
When a log file is requested, `LogFileManager::readTail($path, $maxLines, $maxBytes)` executes:
1. Calculates file size via `filesize($filePath)`.
2. Sets pointer `$pos = $fsize`.
3. In a `while` loop, decrements `$pos` by 64KB chunks (`min(65536, $pos)`).
4. Calls `fseek($fp, $pos)` and reads binary chunk via `fread($fp, $readSize)`.
5. Prepends chunk to `$buffer` and increments `$newlineCount += substr_count($chunk, "\n")`.
6. Breaks when `$newlineCount > $maxLines` or `$bytesToRead` is reached.
7. Splits by `\n` and slices exact tail: `array_slice($lines, -$maxLines)`.

### Log Entry Grouping & Parsing Pipeline
In `LogParser::parseLines(array $rawLines, string $order)`:
1. Reads lines sequentially.
2. If a line begins with a timestamp (detected via `extractTimestamp()`), it marks the **start of a new record**.
3. All following lines without timestamps (stack traces, SQL snippets, multiline exception text) are appended to `$currentEntry['lines']`.
4. When the next timestamp or end of file is reached, `finalizeEntry()` is called:
   - Extracts exception title and file/line references via `parseException()`.
   - Tokenizes stack frames via `parseStackFrames()`.
   - Separates JSON context block from header message via `extractHeaderMessage()` and `parseContext()`.
   - Runs sensitive credential redaction via `redactSensitiveData()`.
5. Groups records by date and orders them (`desc` = newest logs on top).

### Pure-PHP Asset Compilation & Encryption
In `AssetCompiler.php`:
- **CSS**: Strips comments, collapses redundant whitespace, removes trailing semicolons before `}`, compresses zero units, compresses hex codes, and **preserves spaces in `calc()`**.
- **JavaScript**:
  - Minifies code by removing comments and collapsing spaces.
  - Generates a dynamic XOR key: `'RayTech_LogViewer_' . dechex(crc32($minCode)) . '_2026'`.
  - Encrypts each character: `ord($byte) ^ ord($keyByte)`, converted to hex pairs.
  - Hex-scrambles the key array (`\x52\x61...`) so the key is never exposed in plaintext.
  - Emits self-evaluating decryption wrapper function: `(new Function(s))()`.

### Triple-Layer Access & Security Machine
In `RaytechLogViewerController::__construct()`:
1. **Layer 1: IP Whitelist**: Checked via `isIpAllowed()`. Supports exact IPs, wildcards (`192.168.1.*`), and IPv4/IPv6 CIDR subnets (`10.0.0.0/8`).
2. **Layer 2: HTTP Security Headers**: Injects `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, and `Referrer-Policy: strict-origin-when-cross-origin`.
3. **Layer 3: Middlewares & Closures**: Resolves `RaytechLogViewer::getAccessRestriction()` first, falling back to `LOG_VIEWER_MIDDLEWARE` in `.env`.
4. **Session Password Gate**: If `LOG_VIEWER_PASSWORD_PROTECTED=true`, checks `session('log_viewer_authenticated')`. Locks out after 3 failed attempts with rate-limiting timer.

### Multi-Tab Emergency Broadcast Shutdown
When password protection is active, pressing <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>D</kbd> triggers:
- Client creates `new BroadcastChannel('log_viewer_sync')` and posts `{ action: 'destroy_and_close' }`.
- Sets `localStorage.setItem('log_viewer_session_destroyed', Date.now())`.
- Sends async POST request to `/log-viewer/logout`.
- Calls `window.close()`.
- All other open tabs receive the BroadcastChannel message and close immediately.

---

## 5. Standard AI Agent Task Playbooks

### Playbook 1: Modifying Front-End Scripts or Styles

When asked to fix a UI bug, improve search, or change styles:
1. **Edit the source files:**
   - Styles: `public/css/raytech-log-viewer.css`
   - Scripts: `public/js/raytech-log-viewer.js`
   - HTML Structure: `resources/views/index.blade.php`
2. **Recompile production assets:**
   ```powershell
   php artisan raytech-log-viewer:build
   ```
3. **Verify build output:** Ensure both `.min.css` and `.min.js` were updated with size reduction reported.
4. **If testing inside a host app:** Run `php artisan raytech-log-viewer:update` to sync public assets.

---

### Playbook 2: Adding a New Log Driver or Format

When asked to support a new log format (e.g. Docker logs, Nginx access logs, or JSON lines):
1. Open `src/Services/LogParser.php`.
2. In `extractTimestamp()`, add a regex matching the new driver's timestamp format.
3. In `detectLogLevel()`, add fast-path checks for any custom level tokens.
4. In `parseLines()`, verify that multi-line entries are grouped cleanly without splitting single records into fragments.
5. In `LogFileManager.php`, add any new standard directories to `getServerLogDirectories()` if applicable.

---

### Playbook 3: Introducing New Configuration Directives

When adding a new configurable feature:
1. Open `config/raytech-log-viewer.php`.
2. Add the new key with default value and `env()` fallback:
   ```php
   'new_feature_key' => env('LOG_VIEWER_NEW_FEATURE_KEY', true),
   ```
3. Read the value in code using `config('raytech-log-viewer.new_feature_key')`.
4. **Sync Documentation:**
   - In `README.md`: Add the variable to the **One-Click Copy Complete .env Configuration** block and the reference table.
   - Mention that users only need to add variables they wish to override.

---

### Playbook 4: Adding New Artisan Commands

When creating a new CLI command:
1. Create command class in `src/Console/MyNewCommand.php` extending `Illuminate\Console\Command`.
2. Register the command in `src/RaytechLogViewerServiceProvider.php` inside the `$this->commands([...])` array.
3. Document the command in `README.md` under **Artisan Commands Reference**.

---

### Playbook 5: Updating Documentation

When asked to update documentation:
- **`README.md`**: Keep it 100% focused on end-user installation, configuration, features, commands, and usage. **Never add internal code trees, architecture guides, or developer build ideas here.**
- **`_backup/DEVELOPER.md`**: Update this file when changing algorithms, class structures, or internal architecture.
- **`_backup/AGENT.md`**: Update this file when changing AI agent playbooks, golden invariants, or core system workflows.

---

## 6. Key Variable, Session & Event Dictionary

### Session Keys
| Session Key | Purpose |
|---|---|
| `log_viewer_authenticated` | Boolean: true when visitor has successfully entered the password. |
| `log_viewer_failed_attempts` | Integer: counter for failed password attempts. |
| `log_viewer_locked_until` | Timestamp: unix epoch when brute-force lockout expires. |
| `log_viewer_line_limit` | Integer: active user preference for lines to display (e.g. 500). |
| `log_viewer_auto_refresh` | Integer: active user interval in milliseconds (e.g. 10000). |

### LocalStorage & Broadcast Keys
| Key / Channel Name | Purpose |
|---|---|
| `log_viewer_theme` | String: `'light'` or `'dark'` active theme mode. |
| `log_viewer_order` | String: `'desc'` or `'asc'` active sort order. |
| `log_viewer_sync` | `BroadcastChannel` name for multi-tab event synchronization. |
| `log_viewer_session_destroyed` | LocalStorage trigger key for emergency panic shutdown across tabs. |

---

## 7. Pre-Flight Verification Checklist for Agents

Before completing any task or concluding your turn, verify:

- [ ] **No edits to `.min.js` or `.min.css` directly**: All modifications were made to clean source files in `public/js/` or `public/css/`.
- [ ] **`raytech-log-viewer:build` executed**: If CSS or JS was modified, the build command was run and verified.
- [ ] **No broken `calc()` syntax in CSS**: Spaces around `+` and `-` inside `calc()` were preserved.
- [ ] **No database tables or npm dependencies introduced**.
- [ ] **Memory safety verified**: Tail reading uses `LogFileManager::readTail()` with reverse binary chunks.
- [ ] **`README.md` stays user-centric**: Developer architecture remains in `_backup/DEVELOPER.md`.
- [ ] **Git diff is clean and minimal**: No temporary or scratch files left behind.

---

*Manual maintained for AI Agents working on RayTech Log Viewer.*  
*Version: 1.0.0 — Last Updated: 2026-11-24*
