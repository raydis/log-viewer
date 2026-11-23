<?php

namespace Raytech\LogViewer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Raytech\LogViewer\RaytechLogViewer;
use Raytech\LogViewer\Services\LogFileManager;
use Raytech\LogViewer\Services\LogParser;
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
     * Main display handler for logs page.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $allFiles = $this->fileManager->getAllLogFiles();
        $fileKey = $request->query('key');
        $fileName = $request->query('file');
        $selectedFile = $this->fileManager->resolveLogFile($fileKey, $fileName, $allFiles);

        $defaultLines = (int) config('raytech-log-viewer.features.default_lines', 500);
        $allowedLines = config('raytech-log-viewer.features.lines_options', [100, 250, 500, 1000, 2500, 5000]);

        $lineLimit = (int) $request->query('lines', $defaultLines);
        if (!in_array($lineLimit, $allowedLines, true)) {
            $lineLimit = $defaultLines;
        }

        $defaultOrder = config('raytech-log-viewer.features.default_order', 'desc');
        $order = $request->query('order', $defaultOrder);
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
            return response()->json([
                'status' => true,
                'file' => $selectedFile,
                'stats' => $stats,
                'lines' => $parsedLines,
                'date_groups' => $dateGroups,
                'total_lines' => count($parsedLines),
                'order' => $order,
            ]);
        }

        return view('raytech-log-viewer::index', [
            'allFiles' => $allFiles,
            'laravelLogs' => $laravelLogs,
            'serverLogs' => $serverLogs,
            'showServerLogs' => $this->fileManager->isServerLogsEnabled(),
            'selectedFile' => $selectedFile,
            'parsedLines' => $parsedLines,
            'dateGroups' => $dateGroups,
            'stats' => $stats,
            'lineLimit' => $lineLimit,
            'allowedLines' => $allowedLines,
            'order' => $order,
            'themeConfig' => config('raytech-log-viewer.theme', []),
            'features' => config('raytech-log-viewer.features', []),
            'assets' => config('raytech-log-viewer.assets', []),
            'logoUrl' => $this->resolveLogoUrl(),
            'version' => RaytechLogViewer::VERSION,
        ]);
    }

    /**
     * Clear / truncate selected log file.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function clear(Request $request)
    {
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

        if (!$targetFile || !file_exists($targetFile['path'])) {
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

            $routePrefix = config('raytech-log-viewer.route.name', 'raytech-log-viewer.') . 'index';
            return redirect()->route($routePrefix, ['key' => $targetFile['key']])->with('success', $msg);
        } else {
            $msg = "Unable to clear '{$targetFile['name']}'. File may be locked by a running process or requires write permissions.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $msg], 403);
            }

            $routePrefix = config('raytech-log-viewer.route.name', 'raytech-log-viewer.') . 'index';
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
        if (!config('raytech-log-viewer.features.allow_download', true)) {
            abort(403, 'Downloading log files is disabled in configuration.');
        }

        $allFiles = $this->fileManager->getAllLogFiles();
        $fileKey = $request->query('key');
        $fileName = $request->query('file');

        $targetFile = $this->fileManager->resolveLogFile($fileKey, $fileName, $allFiles);

        if (!$targetFile || !file_exists($targetFile['path'])) {
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
        if (!config('raytech-log-viewer.features.allow_raw', true)) {
            abort(403, 'Viewing raw log files is disabled in configuration.');
        }

        $allFiles = $this->fileManager->getAllLogFiles();
        $fileKey = $request->query('key');
        $fileName = $request->query('file');

        $targetFile = $this->fileManager->resolveLogFile($fileKey, $fileName, $allFiles);

        if (!$targetFile || !file_exists($targetFile['path'])) {
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

        $namePrefix = config('raytech-log-viewer.route.name', 'raytech-log-viewer.');

        // If no logo specified or empty
        if (empty($rawLogo)) {
            $rawLogo = 'img/default_logo.jpg';
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
            if ($trimmed === 'default_logo.jpg' && file_exists(public_path('vendor/raytech-log-viewer/img/default_logo.jpg'))) {
                return asset('vendor/raytech-log-viewer/img/default_logo.jpg');
            }
        }

        // Standardize default_logo.jpg to img/default_logo.jpg
        if ($trimmed === 'default_logo.jpg') {
            $trimmed = 'img/default_logo.jpg';
        }

        // Serve via package asset route
        try {
            return route($namePrefix . 'asset', ['file' => $trimmed]);
        } catch (\Throwable $e) {
            return asset($trimmed);
        }
    }

    /**
     * Serve static package assets (css, js, images) directly if not published.
     *
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function asset(string $file)
    {
        // Sanitize path against directory traversal
        $file = str_replace(['../', '..\\'], '', $file);
        $file = ltrim($file, '/\\');

        $targetPath = null;

        // Resolve package source asset
        $packagePublic = realpath(__DIR__ . '/../../public');
        $candidate = realpath(__DIR__ . '/../../public/' . $file);
        $packageAsset = ($packagePublic && $candidate && str_starts_with($candidate, $packagePublic) && is_file($candidate))
            ? $candidate
            : null;

        if (!$packageAsset && ($file === 'default_logo.jpg' || $file === 'img/default_logo.jpg')) {
            $logoCandidate = realpath(__DIR__ . '/../../public/img/default_logo.jpg');
            if ($logoCandidate && is_file($logoCandidate)) {
                $packageAsset = $logoCandidate;
            }
        }

        // 1. Check published public path
        if (function_exists('public_path')) {
            $published = public_path('vendor/raytech-log-viewer/' . $file);
            if (file_exists($published) && is_file($published)) {
                // Compare timestamps: if package has newer code, auto-sync and serve latest code
                if ($packageAsset && filemtime($packageAsset) > filemtime($published)) {
                    if (is_writable($published)) {
                        @copy($packageAsset, $published);
                    }
                    $targetPath = $packageAsset;
                } else {
                    $targetPath = $published;
                }
            }
        }

        // 2. Fallback to package source asset directly
        if (!$targetPath && $packageAsset) {
            $targetPath = $packageAsset;
        }

        if (!$targetPath || !file_exists($targetPath)) {
            abort(404, 'Asset not found.');
        }

        $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $contentTypes = [
            'css'   => 'text/css; charset=utf-8',
            'js'    => 'application/javascript; charset=utf-8',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
        ];

        $contentType = $contentTypes[$extension] ?? 'application/octet-stream';

        return response()->file($targetPath, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
