<?php

namespace Raytech\LogViewer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Raytech\LogViewer\Services\LogFileManager;
use Raytech\LogViewer\Services\LogParser;
use Raytech\LogViewer\RaytechLogViewer;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RaytechLogViewerController extends Controller
{
    /**
     * @var LogFileManager
     */
    protected LogFileManager $fileManager;

    /**
     * @var LogParser
     */
    protected LogParser $parser;

    /**
     * Create a new controller instance and register middleware.
     */
    public function __construct(LogFileManager $fileManager, LogParser $parser)
    {
        $this->fileManager = $fileManager;
        $this->parser = $parser;

        // 1. IP Whitelisting Check (when allowed_ips is null/empty, access is allowed to all)
        $this->middleware(function ($request, $next) {
            if (!$this->isIpAllowed($request)) {
                if ($request->ajax() || $request->query('ajax') == '1' || $request->wantsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Unauthorized: Your IP (' . ($request->ip() ?? 'unknown') . ') is not in the allowed whitelist.',
                    ], 403);
                }
                abort(403, 'Access denied: Your IP (' . ($request->ip() ?? 'unknown') . ') is not authorized to access the log viewer.');
            }
            return $next($request);
        })->except(['asset']);

        // 2. HTTP Security Headers (Clickjacking, MIME-sniffing, and Referrer leakage protection)
        $this->middleware(function ($request, $next) {
            $response = $next($request);
            if ($response instanceof SymfonyResponse) {
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
                $response->headers->set('X-Content-Type-Options', 'nosniff');
                $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            }
            return $response;
        })->except(['asset']);

        // 3. Custom route middlewares / callbacks
        $rawMiddlewares = $this->middlewareHandler();
        $middlewares = $this->parseMiddlewares($rawMiddlewares);

        // When null or empty, page & functions are allowed to all
        if (empty($middlewares)) {
            return;
        }

        foreach ($middlewares as $mw) {
            if ($mw === null || $mw === '') {
                continue;
            }

            if (is_callable($mw)) {
                $this->middleware(function ($request, $next) use ($mw) {
                    if ($mw instanceof \Closure) {
                        $reflection = new \ReflectionFunction($mw);
                        if ($reflection->getNumberOfParameters() >= 2) {
                            return $mw($request, $next);
                        }
                    }

                    $response = $mw($request, $next);

                    // If a redirect or JSON response is returned, pass it through
                    if ($response instanceof SymfonyResponse) {
                        return $response;
                    }

                    // If explicit false is returned, abort with 403 Forbidden
                    if ($response === false) {
                        abort(403, 'Unauthorized access to server logs.');
                    }

                    return $next($request);
                })->except(['asset']);
            } elseif (is_string($mw)) {
                $this->middleware($mw)->except(['asset']);
            }
        }
    }

    /**
     * Parse raw middlewares from comma-separated string, array, callable, or null.
     * Automatically splits comma-separated strings (e.g. "adminGard,web,UserGuest").
     *
     * @param mixed $raw
     * @return array
     */
    protected function parseMiddlewares($raw): array
    {
        if ($raw === null || $raw === false || $raw === '' || $raw === []) {
            return [];
        }

        if (is_callable($raw)) {
            return [$raw];
        }

        $items = is_array($raw) ? $raw : [$raw];
        $parsed = [];

        foreach ($items as $item) {
            if ($item === null || $item === false || $item === '') {
                continue;
            }

            if (is_callable($item)) {
                $parsed[] = $item;
            } elseif (is_string($item)) {
                $tokens = explode(',', $item);
                foreach ($tokens as $token) {
                    $token = trim($token);
                    if ($token !== '') {
                        $parsed[] = $token;
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * Retrieve access restriction middlewares or callback.
     *
     * @return callable|string|array|null
     */
    protected function middlewareHandler()
    {
        // 1. Check if set programmatically via RaytechLogViewer::auth(...)
        $restriction = RaytechLogViewer::getAccessRestriction();
        if ($restriction !== null) {
            return $restriction;
        }

        // 2. Configured middleware in config/raytech-log-viewer.php or environment
        $configMw = config('raytech-log-viewer.route.middleware')
            ?: env('RAYTECH_LOG_VIEWER_MIDDLEWARE')
            ?: config('logging.error_log_middleware')
            ?: env('ERROR_LOG_MIDDLEWARE');

        if (!empty($configMw)) {
            return $configMw;
        }

        return null;
    }

    /**
     * Determine whether the client IP address is permitted by the whitelist.
     * When LOG_VIEWER_ALLOWED_IPS is null, empty string, or empty array, access is allowed to all.
     *
     * @param Request $request
     * @return bool
     */
    protected function isIpAllowed(Request $request): bool
    {
        $allowed = config('raytech-log-viewer.security.allowed_ips');
        if ($allowed === null) {
            $allowed = env('LOG_VIEWER_ALLOWED_IPS', null);
        }

        // If null, empty string, or empty array, access is allowed to all
        if ($allowed === null || $allowed === '' || $allowed === []) {
            return true;
        }

        if (is_string($allowed)) {
            $allowed = array_filter(array_map('trim', explode(',', $allowed)));
        }

        if (empty($allowed)) {
            return true;
        }

        $clientIp = $request->ip();
        if (!$clientIp) {
            return false;
        }

        foreach ($allowed as $rule) {
            $rule = trim((string) $rule);
            if ($rule === '') {
                continue;
            }

            // Exact match
            if ($clientIp === $rule) {
                return true;
            }

            // Wildcard match (e.g. 192.168.1.*)
            if (strpos($rule, '*') !== false && fnmatch($rule, $clientIp)) {
                return true;
            }

            // CIDR subnet match (e.g. 10.0.0.0/8, 192.168.0.0/16)
            if (strpos($rule, '/') !== false && $this->ipMatchesCidr($clientIp, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address falls within a CIDR subnet range.
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    protected function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);

        // IPv4 CIDR matching
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskInt = (int) $mask;

            if ($maskInt < 0 || $maskInt > 32) {
                return false;
            }

            if ($maskInt === 0) {
                return true;
            }

            $netmask = -1 << (32 - $maskInt);
            return ($ipLong & $netmask) === ($subnetLong & $netmask);
        }

        // IPv6 CIDR matching
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            $maskInt = (int) $mask;

            if ($ipBin === false || $subnetBin === false || $maskInt < 0 || $maskInt > 128) {
                return false;
            }

            $bytes = (int) ($maskInt / 8);
            $bits = $maskInt % 8;

            if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }

            if ($bits > 0) {
                $maskByte = chr((0xFF << (8 - $bits)) & 0xFF);
                return ($ipBin[$bytes] & $maskByte) === ($subnetBin[$bytes] & $maskByte);
            }

            return true;
        }

        return false;
    }

    /**
     * Check if security password protection is enabled.
     *
     * @return bool
     */
    protected function isSecurityEnabled(): bool
    {
        return (bool) config('raytech-log-viewer.security.enabled', false);
    }

    /**
     * Check if the current session is verified and authenticated.
     *
     * @param Request $request
     * @return bool
     */
    protected function isSessionAuthenticated(Request $request): bool
    {
        if (!$this->isSecurityEnabled()) {
            return true;
        }

        $sessionKey = (string) config('raytech-log-viewer.security.session_key', 'log_viewer_authenticated');
        return (bool) $request->session()->get($sessionKey, false);
    }

    /**
     * Render the password prompt authentication screen.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    protected function renderPasswordPrompt(Request $request)
    {
        $lockoutKey = (string) config('raytech-log-viewer.security.lockout_key', 'log_viewer_locked_until');
        $lockoutUntil = (int) $request->session()->get($lockoutKey, 0);

        if ($lockoutUntil > time()) {
            $remaining = $lockoutUntil - time();
            $lockoutRedirect = config('raytech-log-viewer.security.lockout_redirect', '/');
            return redirect($lockoutRedirect)->with('error', "Too many failed password attempts. Access is locked for {$remaining} more second(s).");
        }

        $attemptsKey = (string) config('raytech-log-viewer.security.attempts_key', 'log_viewer_failed_attempts');
        $attempts = (int) $request->session()->get($attemptsKey, 0);
        $maxAttempts = (int) config('raytech-log-viewer.security.max_attempts', 3);

        $namePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');

        return view('raytech-log-viewer::auth', [
            'themeConfig' => config('raytech-log-viewer.theme', []),
            'assets' => $this->resolveConfiguredAssets(),
            'logoUrl' => $this->resolveLogoUrl(),
            'version' => RaytechLogViewer::VERSION,
            'attempts' => $attempts,
            'maxAttempts' => $maxAttempts,
            'authRoute' => $namePrefix . 'auth',
            'assetRoute' => $namePrefix . 'asset',
        ]);
    }

    /**
     * Main display handler for logs page.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        if ($this->isSecurityEnabled() && !$this->isSessionAuthenticated($request)) {
            if ($request->ajax() || $request->query('ajax') == '1' || $request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Password verification required.',
                    'auth_required' => true,
                ], 401);
            }
            return $this->renderPasswordPrompt($request);
        }

        $allFiles = $this->fileManager->getAllLogFiles();
        $fileKey = $request->query('key');
        $fileName = $request->query('file');
        $selectedFile = $this->fileManager->resolveLogFile($fileKey, $fileName, $allFiles);

        $defaultLines = (int) config('raytech-log-viewer.features.default_lines', 500);
        $allowedLines = config('raytech-log-viewer.features.lines_options', [100, 250, 500, 1000, 2500, 5000]);

        // Active session persistent line limit
        if ($request->has('lines')) {
            $lineLimit = (int) $request->query('lines');
            if (in_array($lineLimit, $allowedLines, true)) {
                $request->session()->put('log_viewer_line_limit', $lineLimit);
            }
        } else {
            $lineLimit = (int) $request->session()->get('log_viewer_line_limit', $defaultLines);
        }

        if (!in_array($lineLimit, $allowedLines, true)) {
            $lineLimit = $defaultLines;
        }

        // Active session persistent auto-refresh interval
        $defaultAutoRefresh = (int) config('raytech-log-viewer.features.auto_refresh_default', 10000);
        if ($request->has('auto_refresh')) {
            $autoInterval = (int) $request->input('auto_refresh');
            $request->session()->put('log_viewer_auto_refresh', $autoInterval);
        } else {
            $autoInterval = (int) $request->session()->get('log_viewer_auto_refresh', $defaultAutoRefresh);
        }

        // Active session persistent order
        $defaultOrder = config('raytech-log-viewer.features.default_order', 'desc');
        if ($request->has('order')) {
            $order = $request->query('order');
            if (in_array($order, ['desc', 'asc'], true)) {
                $request->session()->put('log_viewer_order', $order);
            }
        } else {
            $order = $request->session()->get('log_viewer_order', $defaultOrder);
        }
        if (!in_array($order, ['desc', 'asc'], true)) {
            $order = 'desc';
        }

        $parsedLines = [];
        $dateGroups = [];
        $stats = [
            'total' => 0,
            'error' => 0,
            'warning' => 0,
            'info' => 0,
            'debug' => 0,
            'trace' => 0,
        ];

        if ($selectedFile && file_exists($selectedFile['path'])) {
            $maxBytes = (int) config('raytech-log-viewer.features.max_read_bytes', 4194304);
            $rawLines = $this->fileManager->readTail($selectedFile['path'], $lineLimit, $maxBytes);
            $parsedData = $this->parser->parseLines($rawLines, $order);
            $parsedLines = $parsedData['lines'];
            $dateGroups = $parsedData['date_groups'] ?? [];
            $stats = $parsedData['stats'];
        }

        // Group files by category
        $laravelLogs = array_filter($allFiles, fn($f) => $f['type'] === 'laravel');
        $serverLogs = array_filter($allFiles, fn($f) => $f['type'] === 'server');

        // Check if AJAX request for live updating / file switching
        if ($request->ajax() || $request->query('ajax') == '1' || $request->wantsJson()) {
            return $this->compressJsonResponse([
                'status' => true,
                'file' => $selectedFile,
                'stats' => $stats,
                'lines' => $parsedLines,
                'date_groups' => $dateGroups,
                'total_lines' => count($parsedLines),
                'order' => $order,
            ], $request);
        }

        $namePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');
        $totalSizeBytes = $this->fileManager->getTotalSize($allFiles);
        $totalSizeFormatted = $this->fileManager->formatBytes($totalSizeBytes);

        return view('raytech-log-viewer::index', [
            'allFiles' => $allFiles,
            'totalSizeBytes' => $totalSizeBytes,
            'totalSizeFormatted' => $totalSizeFormatted,
            'laravelLogs' => $laravelLogs,
            'serverLogs' => $serverLogs,
            'showServerLogs' => $this->fileManager->isServerLogsEnabled(),
            'selectedFile' => $selectedFile,
            'parsedLines' => $parsedLines,
            'dateGroups' => $dateGroups,
            'stats' => $stats,
            'lineLimit' => $lineLimit,
            'allowedLines' => $allowedLines,
            'autoInterval' => $autoInterval,
            'order' => $order,
            'themeConfig' => config('raytech-log-viewer.theme', []),
            'features' => config('raytech-log-viewer.features', []),
            'security' => config('raytech-log-viewer.security', []),
            'isSecurityEnabled' => $this->isSecurityEnabled(),
            'logoutRoute' => $namePrefix . 'logout',
            'assets' => $this->resolveConfiguredAssets(),
            'logoUrl' => $this->resolveLogoUrl(),
            'version' => RaytechLogViewer::VERSION,
        ]);
    }

    /**
     * Process password authentication.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authenticate(Request $request)
    {
        if (!$this->isSecurityEnabled()) {
            $namePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');
            return redirect()->route($namePrefix . 'index');
        }

        $throttleKey = 'raytech_log_viewer_auth:' . ($request->ip() ?? 'unknown');
        $maxAttempts = (int) config('raytech-log-viewer.security.max_attempts', 3);
        $lockoutDuration = (int) config('raytech-log-viewer.security.lockout_duration', 60);

        // 1. IP-level rate limiting check (prevents cookie-dropping brute force scripts)
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            $lockoutRedirect = config('raytech-log-viewer.security.lockout_redirect', '/');
            return redirect($lockoutRedirect)->with('error', "Too many failed attempts from your IP. Access is locked for {$seconds} more second(s).");
        }

        // 2. Session-level lockout check
        $lockoutKey = (string) config('raytech-log-viewer.security.lockout_key', 'log_viewer_locked_until');
        $lockoutUntil = (int) $request->session()->get($lockoutKey, 0);

        if ($lockoutUntil > time()) {
            $remaining = $lockoutUntil - time();
            $lockoutRedirect = config('raytech-log-viewer.security.lockout_redirect', '/');
            return redirect($lockoutRedirect)->with('error', "Too many failed attempts. Access is locked for {$remaining} more second(s).");
        }

        $inputPassword = (string) $request->input('password', '');
        $configuredPassword = (string) config('raytech-log-viewer.security.password', '');
        $sessionKey = (string) config('raytech-log-viewer.security.session_key', 'log_viewer_authenticated');
        $attemptsKey = (string) config('raytech-log-viewer.security.attempts_key', 'log_viewer_failed_attempts');
        $namePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');

        // Validate strictly using cryptographic hash methods (never plain string comparison)
        $isMatch = $this->verifyPassword($inputPassword, $configuredPassword);

        if ($isMatch) {
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            $request->session()->forget($attemptsKey);
            $request->session()->forget($lockoutKey);
            $request->session()->regenerate();
            $request->session()->put($sessionKey, true);

            return redirect()->route($namePrefix . 'index');
        }

        // Hit IP-based rate limiter
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, $lockoutDuration);

        // Increment failed attempts in session
        $attempts = (int) $request->session()->get($attemptsKey, 0) + 1;
        $request->session()->put($attemptsKey, $attempts);

        if ($attempts >= $maxAttempts) {
            $request->session()->put($lockoutKey, time() + $lockoutDuration);
            $request->session()->forget($attemptsKey);
            $request->session()->forget($sessionKey);

            $lockoutRedirect = config('raytech-log-viewer.security.lockout_redirect', '/');
            return redirect($lockoutRedirect)->with('error', "Authentication failed {$maxAttempts} times. Access denied.");
        }

        $remaining = $maxAttempts - $attempts;
        return redirect()->back()->with('error', "Incorrect password. {$remaining} attempt(s) remaining.");
    }

    /**
     * Verify user input password against the configured hashed password using Hash::check.
     * Always validates with Hash::check method against the encrypted/hashed password.
     *
     * @param string $inputPassword
     * @param string $configuredPassword
     * @return bool
     */
    protected function verifyPassword(string $inputPassword, string $configuredPassword): bool
    {
        if ($inputPassword === '' || $configuredPassword === '') {
            return false;
        }

        // 1. Primary validation: Laravel Hash::check()
        try {
            if (\Illuminate\Support\Facades\Hash::check($inputPassword, $configuredPassword)) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        // 2. Direct password_verify fallback for standard PHP Bcrypt/Argon2 hashes
        try {
            if (function_exists('password_verify') && password_verify($inputPassword, $configuredPassword)) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        // 3. Optional fallback for SHA-256 or SHA-512 hex hashes
        $len = strlen($configuredPassword);
        if (($len === 64 || $len === 128) && ctype_xdigit($configuredPassword)) {
            $algo = ($len === 64) ? 'sha256' : 'sha512';
            if (hash_equals(strtolower($configuredPassword), hash($algo, $inputPassword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Terminate the log viewer session (Destroy Session & Logout).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $sessionKey = (string) config('raytech-log-viewer.security.session_key', 'log_viewer_authenticated');
        $request->session()->forget($sessionKey);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Log viewer session terminated successfully.',
            ]);
        }

        $namePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');
        return redirect()->route($namePrefix . 'index')->with('success', 'Session locked successfully.');
    }

    /**
     * Clear / truncate selected log file.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function clear(Request $request)
    {
        if ($this->isSecurityEnabled() && !$this->isSessionAuthenticated($request)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Unauthorized. Password verification required.'], 401);
            }
            abort(403, 'Unauthorized.');
        }
        if (!config('raytech-log-viewer.features.allow_clear', true)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Clearing logs is disabled in configuration.'], 403);
            }
            return redirect()->back()->with('error', 'Clearing logs is disabled in configuration.');
        }

        $allFiles = $this->fileManager->getAllLogFiles();
        $fileKey = $request->input('key');
        $fileName = $request->input('file');

        $targetFile = $this->fileManager->resolveLogFile($fileKey, $fileName, $allFiles);

        if (!$targetFile || !file_exists($targetFile['path']) || $this->fileManager->isSensitiveFile($targetFile['path'])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Log file not found or invalid.'], 404);
            }
            return redirect()->back()->with('error', 'Log file not found or invalid.');
        }

        $cleared = $this->fileManager->clearFile($targetFile['path']);

        if ($cleared) {
            $msg = "Log file '{$targetFile['name']}' has been cleared successfully.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => $msg,
                    'file' => [
                        'key' => $targetFile['key'],
                        'name' => $targetFile['name'],
                        'size' => 0,
                        'size_formatted' => '0 B',
                        'modified' => date('Y-m-d H:i:s'),
                    ]
                ]);
            }

            $routePrefix = config('raytech-log-viewer.route.name', 'log-viewer.') . 'index';
            return redirect()->route($routePrefix, ['key' => $targetFile['key']])->with('success', $msg);
        } else {
            $msg = "Unable to clear '{$targetFile['name']}'. File may be locked by a running process or requires write permissions.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $msg], 403);
            }

            $routePrefix = config('raytech-log-viewer.route.name', 'log-viewer.') . 'index';
            return redirect()->route($routePrefix, ['key' => $targetFile['key']])->with('error', $msg);
        }
    }

    /**
     * Download the selected log file.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Request $request)
    {
        if ($this->isSecurityEnabled() && !$this->isSessionAuthenticated($request)) {
            abort(403, 'Unauthorized. Password verification required.');
        }

        if (!config('raytech-log-viewer.features.allow_download', true)) {
            abort(403, 'Downloading log files is disabled in configuration.');
        }

        $allFiles = $this->fileManager->getAllLogFiles();
        $fileKey = $request->query('key');
        $fileName = $request->query('file');

        $targetFile = $this->fileManager->resolveLogFile($fileKey, $fileName, $allFiles);

        if (!$targetFile || !file_exists($targetFile['path']) || $this->fileManager->isSensitiveFile($targetFile['path'])) {
            abort(404, 'Log file not found.');
        }

        return response()->download($targetFile['path'], $targetFile['name'], [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * View raw content of the selected log file.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function raw(Request $request)
    {
        if ($this->isSecurityEnabled() && !$this->isSessionAuthenticated($request)) {
            abort(403, 'Unauthorized. Password verification required.');
        }

        if (!config('raytech-log-viewer.features.allow_raw', true)) {
            abort(403, 'Viewing raw log files is disabled in configuration.');
        }

        $allFiles = $this->fileManager->getAllLogFiles();
        $fileKey = $request->query('key');
        $fileName = $request->query('file');

        $targetFile = $this->fileManager->resolveLogFile($fileKey, $fileName, $allFiles);

        if (!$targetFile || !file_exists($targetFile['path']) || $this->fileManager->isSensitiveFile($targetFile['path'])) {
            abort(404, 'Log file not found.');
        }

        return response()->file($targetFile['path'], [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Resolve the browser icon and branding logo URL.
     *
     * @return string
     */
    protected function resolveLogoUrl(): string
    {
        $rawLogo = config('raytech-log-viewer.theme.brand_logo')
            ?: env('RAYTECH_LOG_VIEWER_LOGO')
            ?: env('RAYTECH_LOG_LOGOPATH');

        $namePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');

        // If no logo specified or empty
        if (empty($rawLogo)) {
            $rawLogo = 'img/default_logo.png';
        }

        // If fully-qualified URL or data URI
        if (preg_match('#^(https?:)?//#i', $rawLogo) || str_starts_with($rawLogo, 'data:image')) {
            return $rawLogo;
        }

        // Clean leading slashes
        $trimmed = ltrim($rawLogo, '/\\');

        // Check if file exists directly in public directory
        if (function_exists('public_path')) {
            if (file_exists(public_path($trimmed))) {
                return asset($trimmed);
            }
            if (file_exists(public_path('vendor/raytech-log-viewer/' . $trimmed))) {
                return asset('vendor/raytech-log-viewer/' . $trimmed);
            }
            if ($trimmed === 'default_logo.png' && file_exists(public_path('vendor/raytech-log-viewer/img/default_logo.png'))) {
                return asset('vendor/raytech-log-viewer/img/default_logo.png');
            }
        }

        // Standardize default_logo.png to img/default_logo.png
        if ($trimmed === 'default_logo.png') {
            $trimmed = 'img/default_logo.png';
        }

        // Serve via package asset route
        try {
            return route($namePrefix . 'asset', ['file' => $trimmed]);
        } catch (\Throwable $e) {
            return asset($trimmed);
        }
    }

    /**
     * Resolve an asset relative or remote path to a browser-loadable URL.
     *
     * @param string|null $path
     * @return string|null
     */
    public function resolveAssetUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // External URLs or protocol-relative URLs
        if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        $trimmed = ltrim($path, '/\\');
        $namePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');

        // 1. Check published asset in public_path
        if (function_exists('public_path')) {
            if (file_exists(public_path('vendor/raytech-log-viewer/' . $trimmed))) {
                return asset('vendor/raytech-log-viewer/' . $trimmed);
            }
            if (file_exists(public_path($trimmed))) {
                return asset($trimmed);
            }
        }

        // 2. Fallback to package asset streaming route
        try {
            return route($namePrefix . 'asset', ['file' => $trimmed]);
        } catch (\Throwable $e) {
            return asset($trimmed);
        }
    }

    /**
     * Resolve all configured assets (FontAwesome, SweetAlert2, etc.) to valid browser URLs.
     *
     * @return array
     */
    protected function resolveConfiguredAssets(): array
    {
        $raw = (array) config('raytech-log-viewer.assets', []);
        $useCdn = (bool) ($raw['use_cdn'] ?? false);

        if ($useCdn) {
            $faCss = $raw['fontawesome_css'] ?? 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
            if ($faCss === 'vendor/fontawesome-free/css/all.min.css') {
                $faCss = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
            }

            $swalCss = $raw['sweetalert2_css'] ?? 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.min.css';
            if ($swalCss === 'vendor/sweetalert2/sweetalert2.min.css') {
                $swalCss = 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.min.css';
            }

            $swalJs = $raw['sweetalert2_js'] ?? 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.all.min.js';
            if ($swalJs === 'vendor/sweetalert2/sweetalert2.all.min.js') {
                $swalJs = 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.all.min.js';
            }

            return [
                'use_cdn' => true,
                'fontawesome_css' => $this->resolveAssetUrl($faCss),
                'sweetalert2_css' => $this->resolveAssetUrl($swalCss),
                'sweetalert2_js' => $this->resolveAssetUrl($swalJs),
            ];
        }

        return [
            'use_cdn' => false,
            'fontawesome_css' => $this->resolveAssetUrl($raw['fontawesome_css'] ?? 'vendor/fontawesome-free/css/all.min.css'),
            'sweetalert2_css' => $this->resolveAssetUrl($raw['sweetalert2_css'] ?? 'vendor/sweetalert2/sweetalert2.min.css'),
            'sweetalert2_js' => $this->resolveAssetUrl($raw['sweetalert2_js'] ?? 'vendor/sweetalert2/sweetalert2.all.min.js'),
        ];
    }

    /**
     * Serve static package assets (css, js, images, fonts) directly if not published.
     * Hardened against directory traversal with strict extension whitelist and realpath verification.
     *
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function asset(string $file)
    {
        // 1. Strict extension whitelist & MIME resolution
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $contentTypes = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'map' => 'application/json; charset=utf-8',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
        ];

        if (!isset($contentTypes[$extension])) {
            abort(404, 'Asset type not permitted.');
        }

        // 2. Reject directory traversal attempts and null bytes
        if (str_contains($file, '..') || str_contains($file, "\0")) {
            abort(404, 'Invalid asset path.');
        }

        $file = ltrim(str_replace('\\', '/', $file), '/');

        // Protect unminified core files: always serve encrypted minified production assets
        if ($file === 'css/raytech-log-viewer.css') {
            $file = 'css/raytech-log-viewer.min.css';
        } elseif ($file === 'js/raytech-log-viewer.js') {
            $file = 'js/raytech-log-viewer.min.js';
        }

        $targetPath = null;

        // 3. Resolve package source asset root (3 levels up from Http/Controllers)
        $packagePublic = realpath(dirname(__DIR__, 3) . '/public');
        $candidate = $packagePublic ? realpath($packagePublic . '/' . $file) : null;
        $packageAsset = ($packagePublic && $candidate && str_starts_with($candidate, $packagePublic) && is_file($candidate))
            ? $candidate
            : null;

        if (!$packageAsset && ($file === 'default_logo.png' || $file === 'img/default_logo.png')) {
            $logoCandidate = $packagePublic ? realpath($packagePublic . '/img/default_logo.png') : null;
            if ($logoCandidate && is_file($logoCandidate)) {
                $packageAsset = $logoCandidate;
            }
        }

        // 4. Check published public path with strict realpath boundary verification
        if (function_exists('public_path')) {
            $publishedBase = realpath(public_path('vendor/raytech-log-viewer'));
            $publishedCandidate = public_path('vendor/raytech-log-viewer/' . $file);
            if (file_exists($publishedCandidate) && is_file($publishedCandidate)) {
                $realPublished = realpath($publishedCandidate);
                if ($publishedBase && $realPublished && str_starts_with($realPublished, $publishedBase)) {
                    // Compare timestamps: if package has newer code, auto-sync and serve latest code
                    if ($packageAsset && filemtime($packageAsset) > filemtime($realPublished)) {
                        if (is_writable($realPublished)) {
                            @copy($packageAsset, $realPublished);
                        }
                        $targetPath = $packageAsset;
                    } else {
                        $targetPath = $realPublished;
                    }
                }
            }
        }

        // 5. Fallback to package source asset directly
        if (!$targetPath && $packageAsset) {
            $targetPath = $packageAsset;
        }

        if (!$targetPath || !file_exists($targetPath)) {
            abort(404, 'Asset not found.');
        }

        $contentType = $contentTypes[$extension];
        $lastModified = filemtime($targetPath);
        $etag = '"' . md5($targetPath . $lastModified) . '"';

        // 6. HTTP 304 Not Modified validation for high performance
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        $headers = [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=31536000',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
        ];

        try {
            if (@is_readable($targetPath)) {
                return response()->file($targetPath, $headers);
            }
        } catch (\Throwable $e) {
        }

        return response(file_get_contents($targetPath), 200, $headers);
    }

    /**
     * Send JSON response with optional gzip/deflate compression if supported by client.
     * Reduces large log response network bandwidth by 80-90%.
     *
     * @param array $data
     * @param Request $request
     * @param int $status
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    protected function compressJsonResponse(array $data, Request $request, int $status = 200)
    {
        $acceptEncoding = (string) $request->header('Accept-Encoding', '');

        if (extension_loaded('zlib') && !ini_get('zlib.output_compression') && !ob_get_length()) {
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (strlen($json) > 1024) {
                if (str_contains($acceptEncoding, 'gzip')) {
                    $compressed = gzencode($json, 6);
                    if ($compressed !== false) {
                        return response($compressed, $status, [
                            'Content-Type' => 'application/json; charset=utf-8',
                            'Content-Encoding' => 'gzip',
                            'Vary' => 'Accept-Encoding',
                        ]);
                    }
                } elseif (str_contains($acceptEncoding, 'deflate')) {
                    $compressed = gzdeflate($json, 6);
                    if ($compressed !== false) {
                        return response($compressed, $status, [
                            'Content-Type' => 'application/json; charset=utf-8',
                            'Content-Encoding' => 'deflate',
                            'Vary' => 'Accept-Encoding',
                        ]);
                    }
                }
            }
        }

        return response()->json($data, $status);
    }
}
