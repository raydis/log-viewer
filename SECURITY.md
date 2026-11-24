# Security Policy

RayTech takes the security of our software products and open-source packages seriously. This document outlines the security policies, supported versions, vulnerability reporting guidelines, and built-in security features for **RayTech Log Viewer** (`raytech/raytech-log-viewer`).

---

## 🛡️ Supported Versions

We actively provide security patches, bug fixes, and maintenance updates for the following versions:

| Version | Supported | PHP Versions | Laravel Framework Versions |
|---|---|---|---|
| **1.0.x** | :white_check_mark: Active | `^8.0`, `^8.1`, `^8.2`, `^8.3`, `^8.4`, `^8.5` | `^8.0` through `^12.0` |
| **< 1.0.0** | :x: End of Life | None | None |

We recommend always running the latest minor release of the package to ensure you have the latest security hardening and features.

---

## 🚨 Reporting a Vulnerability

If you discover a security vulnerability within RayTech Log Viewer, **please do NOT open a public GitHub issue, pull request, or discussion**. Disclosing vulnerabilities publicly before a patch is released can put production applications at risk.

### Responsible Disclosure Process

1. **Email or GitHub Advisory**:
   - Submit a report privately via **[GitHub Security Advisories](https://github.com/umesh240/log-viewer/security/advisories/new)**.
   - Alternatively, email the RayTech Core Security Team at **`dev@raytech.local`**.

2. **Information to Include in Your Report**:
   - A detailed description of the vulnerability and its potential impact.
   - Step-by-step instructions or a minimal Proof of Concept (PoC) to reproduce the issue.
   - Affected package version, PHP version, and Laravel framework version.
   - Any proposed fix or remediation (if available).

3. **Response Timeline**:
   - **Initial Acknowledgment**: Within **48 hours** of receiving your report.
   - **Triage & Assessment**: Within **3 to 5 business days**.
   - **Patch & Advisory Release**: A security release will be tagged and published on Packagist and GitHub as quickly as possible.
   - **Credit**: We will gladly credit you in the release notes and security advisory (unless you request anonymity).

---

## 🔒 Built-In Security Architecture & Hardening

RayTech Log Viewer includes multiple defense-in-depth security layers to protect your application logs, server diagnostics, and sensitive credentials:

### 1. Strict Path Traversal & Sensitive File Blocker
To prevent Local File Inclusion (LFI) and directory traversal attacks (`../../`), the file manager:
- Resolves canonical paths using `realpath()` and verifies paths against designated search directories.
- Strictly blocks access to sensitive system and credential files:
  ```
  .env, .env.*, *.php, *.key, *.pem, *.crt, *.cer, *.sql, *.sqlite, *.db, id_rsa*, id_ed25519*
  ```
- Any attempt to read or register sensitive paths will be rejected with an `Access denied` error.
- For compressed archives (`*.gz`), the uncompressed target name is evaluated to prevent archive bypasses (e.g. `database.sql.gz`).

### 2. Multi-Layer Route Authorization
Access to the `/log-viewer` dashboard and API endpoints must satisfy three layers of security:
- **Route Middleware**: Configurable via `LOG_VIEWER_MIDDLEWARE` (supports `auth`, `admin`, `can:view-logs`).
- **Programmatic Auth Gates**: Custom closures via `RaytechLogViewer::auth(function ($request) { ... })`.
- **IP Address & CIDR Subnet Whitelisting**: Restrict access via `LOG_VIEWER_ALLOWED_IPS`. Supports exact IPs (`127.0.0.1`), wildcards (`192.168.1.*`), and IPv4/IPv6 CIDR subnets (`10.0.0.0/8`).

### 3. Standalone Password Protection & Brute-Force Rate Limiting
- When `LOG_VIEWER_PASSWORD_PROTECTED=true` is enabled, visitors must verify with a Bcrypt/SHA-256 hashed password (`LOG_VIEWER_PASSWORD`).
- Protected by rate limiting: visitors receive up to 3 failed attempts before being locked out and redirected away for 60 seconds (`LOG_VIEWER_LOCKOUT_DURATION`).

### 4. Emergency Multi-Tab Panic Destroy Shortcut
- Pressing <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>D</kbd> instantly destroys the session and broadcasts a termination signal via `BroadcastChannel('log_viewer_sync')` and `localStorage`, automatically closing all open log viewer tabs across the user's browser.

### 5. Automated Sensitive Data Redaction
- When `LOG_VIEWER_REDACTION_ENABLED=true` (enabled by default), sensitive parameters are masked with `[REDACTED]` before being sent to the browser:
  - Passwords and confirmation tokens (`password`, `password_confirmation`, `secret`)
  - Authorization headers and Bearer tokens (`Bearer eyJ...`, `access_token`, `api_key`)
  - Database credentials (`DB_PASSWORD`, `DATABASE_URL`)
  - Credit card numbers (13–16 digit PAN patterns)
  - Laravel encryption keys (`APP_KEY`)

### 6. HTTP Security Headers
Every HTTP response from the log viewer automatically includes standard security headers:
- `X-Frame-Options: SAMEORIGIN` (prevents clickjacking attacks)
- `X-Content-Type-Options: nosniff` (prevents MIME-type confusion attacks)
- `Referrer-Policy: strict-origin-when-cross-origin` (prevents token leakage in referrer headers)

### 7. Memory Protection & DoS Prevention
- Multi-gigabyte log files are read in reverse 64KB binary chunks (`fseek`) with a hard memory limit (`max_read_bytes = 4MB`). The package never loads entire files into PHP memory, eliminating memory exhaustion vulnerabilities.

### 8. Bytecode Obfuscation for Global Hotkeys
- When the global hotkey (<kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>X</kbd>) is enabled, the injected script is compiled into an encrypted dynamic XOR bytecode stream (`LOG_VIEWER_SHORTCUT_ENCRYPT=true`). The target URL, trigger key, and logout routes are completely hidden from HTML source inspection.

---

## ⚙️ Production Hardening Best Practices

When deploying RayTech Log Viewer in production environments, follow these operational best practices:

1. **Never Leave Routes Open to the Public**:
   Always protect the log viewer using application authentication middleware:
   ```env
   LOG_VIEWER_MIDDLEWARE="web,auth,can:view-logs"
   ```
2. **Restrict Access by Network or VPN**:
   Set `LOG_VIEWER_ALLOWED_IPS` to your office network, developer static IPs, or internal VPN subnet:
   ```env
   LOG_VIEWER_ALLOWED_IPS="10.0.0.0/8, 192.168.1.*"
   ```
3. **Protect Log Integrity**:
   If log files must remain strictly immutable for compliance or auditing, disable truncation:
   ```env
   LOG_VIEWER_ALLOW_CLEAR=false
   LOG_VIEWER_ALLOW_DAYS_CLEANUP=false
   ```
4. **Use HTTPS**:
   Always serve your Laravel application over TLS/HTTPS to protect session cookies and log data in transit.
5. **Keep Package Updated**:
   Regularly run `composer update raytech/raytech-log-viewer && php artisan raytech-log-viewer:update` to maintain up-to-date security patches.

---

## 📄 License & Attribution

This security policy is maintained by the **RayTech Engineering Team** and applies to all releases of `raytech/raytech-log-viewer`.
