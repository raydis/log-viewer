<?php

namespace Raytech\LogViewer\Services;

class LogFileManager
{
    /**
     * In-memory cache of discovered log files to avoid repeated glob/stat disk overhead.
     *
     * @var array|null
     */
    protected static ?array $cachedLogFiles = null;

    /**
     * Timestamp of the cached log files.
     *
     * @var int
     */
    protected static int $cacheTimestamp = 0;

    /**
     * Programmatically registered extra log files.
     *
     * @var array
     */
    protected static array $runtimeFiles = [];

    /**
     * Programmatically registered extra directories.
     *
     * @var array
     */
    protected static array $runtimeDirectories = [];

    /**
     * Add a custom log file at runtime.
     *
     * @param string $path
     * @return void
     */
    public static function registerFile(string $path): void
    {
        if (!in_array($path, static::$runtimeFiles, true)) {
            static::$runtimeFiles[] = $path;
        }
    }

    /**
     * Add a custom log directory at runtime.
     *
     * @param string $directory
     * @return void
     */
    public static function registerDirectory(string $directory): void
    {
        if (!in_array($directory, static::$runtimeDirectories, true)) {
            static::$runtimeDirectories[] = $directory;
        }
    }

    /**
     * Retrieve custom directory and file entries configured by user.
     *
     * @return array
     */
    public function getCustomConfigEntries(): array
    {
        $entries = [];

        // 1. Config entries
        $configDirs = config('raytech-log-viewer.logs.extra_directories', []);
        $configFiles = config('raytech-log-viewer.logs.extra_files', []);

        if (is_array($configDirs)) {
            $entries = array_merge($entries, $configDirs);
        }
        if (is_array($configFiles)) {
            $entries = array_merge($entries, $configFiles);
        }

        // 2. Environment variables fallback
        $envPaths = env('LOG_VIEWER_DIRS')
            ?: env('LOG_VIEWER_FILES')
            ?: env('RAYTECH_LOG_VIEWER_DIRS')
            ?: env('RAYTECH_LOG_VIEWER_FILES')
            ?: env('SERVER_LOGS_PATH')
            ?: env('SERVER_LOG_FILES');

        if (!empty($envPaths)) {
            $items = is_array($envPaths) ? $envPaths : preg_split('/[,;|]+/', (string) $envPaths);
            $entries = array_merge($entries, $items);
        }

        // 3. Runtime registered items
        $entries = array_merge($entries, static::$runtimeDirectories, static::$runtimeFiles);

        return array_values(array_unique(array_filter(array_map('trim', $entries))));
    }

    /**
     * Check if server logs scanning and display is enabled in configuration.
     * Defaults to false.
     *
     * @return bool
     */
    public function isServerLogsEnabled(): bool
    {
        $showServer = config('raytech-log-viewer.logs.show_server_logs');
        if ($showServer !== null) {
            return (bool) $showServer;
        }

        $scanServer = config('raytech-log-viewer.logs.scan_server_logs');
        if ($scanServer !== null) {
            return (bool) $scanServer;
        }

        return false;
    }

    /**
     * Dynamically resolve server log directories without hardcoding drive letters.
     *
     * @return array
     */
    public function getServerLogDirectories(): array
    {
        if (!$this->isServerLogsEnabled()) {
            return [];
        }

        $dirs = [];

        // 1. Dynamic PHP error log directory from server runtime configuration
        if (config('raytech-log-viewer.logs.scan_php_error_log', true)) {
            $phpErrorLog = ini_get('error_log');
            if (!empty($phpErrorLog) && is_string($phpErrorLog)) {
                $dir = dirname($phpErrorLog);
                if (is_dir($dir)) {
                    $dirs[] = $dir;
                }
            }
        }

        // 2. Dynamic server logs directory relative to current project root (e.g. {server}/logs)
        if (function_exists('base_path')) {
            $relativeServerLogs = realpath(base_path('../../logs'));
            if ($relativeServerLogs && is_dir($relativeServerLogs)) {
                $dirs[] = $relativeServerLogs;
            }

            $relativeParentLogs = realpath(base_path('../logs'));
            if ($relativeParentLogs && is_dir($relativeParentLogs)) {
                $dirs[] = $relativeParentLogs;
            }

            // 3. Server Apache logs relative to root (e.g. {server}/bin/apache/*/logs)
            $serverRoot = realpath(base_path('../..'));
            if ($serverRoot && is_dir($serverRoot . '/bin/apache')) {
                $apacheGlob = glob($serverRoot . '/bin/apache/*/logs');
                if (!empty($apacheGlob)) {
                    $dirs = array_merge($dirs, $apacheGlob);
                }
            }
        }

        // 4. Custom directories from configuration / environment
        foreach ($this->getCustomConfigEntries() as $entry) {
            if (is_dir($entry)) {
                $dirs[] = $entry;
            } elseif (function_exists('base_path') && is_dir(base_path($entry))) {
                $dirs[] = base_path($entry);
            }
        }

        // 5. Standard Linux / container web server paths
        $standardServerDirs = [
            '/var/log/apache2',
            '/var/log/nginx',
            '/var/log/httpd',
            '/var/log/php',
            '/var/log',
        ];
        foreach ($standardServerDirs as $stdDir) {
            if (is_dir($stdDir)) {
                $dirs[] = $stdDir;
            }
        }

        // Return unique, existing directory paths
        $validDirs = [];
        foreach ($dirs as $dir) {
            if (!empty($dir) && is_dir($dir)) {
                $real = realpath($dir);
                if ($real && !in_array($real, $validDirs, true)) {
                    $validDirs[] = $real;
                }
            }
        }

        return $validDirs;
    }

    /**
     * Retrieve all available log files (both Laravel and Server logs).
     * Leverages lightweight short-lived caching to avoid repeated disk I/O on auto-refresh polling.
     *
     * @param bool $forceRefresh
     * @return array
     */
    public function getAllLogFiles(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && static::$cachedLogFiles !== null && (time() - static::$cacheTimestamp) < 4) {
            return static::$cachedLogFiles;
        }

        $logFiles = [];

        // 1. Laravel Logs
        if (config('raytech-log-viewer.logs.scan_laravel_logs', true) && function_exists('storage_path')) {
            $laravelLogsPath = storage_path('logs');
            if (is_dir($laravelLogsPath)) {
                $files = glob($laravelLogsPath . '/*.log');
                if ($files) {
                    foreach ($files as $filePath) {
                        $realPath = realpath($filePath) ?: $filePath;
                        $key = md5('laravel_' . $realPath);
                        $fileSize = file_exists($realPath) ? filesize($realPath) : 0;
                        $mtime = file_exists($realPath) ? filemtime($realPath) : 0;

                        $logFiles[$key] = [
                            'key' => $key,
                            'name' => basename($realPath),
                            'category' => 'Laravel Logs',
                            'type' => 'laravel',
                            'path' => $realPath,
                            'size' => $fileSize,
                            'size_formatted' => $this->formatBytes($fileSize),
                            'modified' => $mtime ? date('Y-m-d H:i:s', $mtime) : '-',
                            'modified_timestamp' => $mtime,
                            'is_empty' => $fileSize === 0,
                        ];
                    }
                }
            }
        }

        // 2. Server Logs (WAMP / Apache / PHP / MySQL / Nginx)
        if ($this->isServerLogsEnabled()) {
            $serverDirs = $this->getServerLogDirectories();
            foreach ($serverDirs as $dir) {
                $files = glob(rtrim($dir, '/\\') . '/*.log');
                if ($files) {
                    foreach ($files as $filePath) {
                        $realPath = realpath($filePath) ?: $filePath;
                        $key = md5('server_' . $realPath);
                        if (isset($logFiles[$key])) {
                            continue;
                        }
                        $fileSize = file_exists($realPath) ? filesize($realPath) : 0;
                        $mtime = file_exists($realPath) ? filemtime($realPath) : 0;

                        $logFiles[$key] = [
                            'key' => $key,
                            'name' => basename($realPath),
                            'category' => 'Server Logs',
                            'type' => 'server',
                            'path' => $realPath,
                            'size' => $fileSize,
                            'size_formatted' => $this->formatBytes($fileSize),
                            'modified' => $mtime ? date('Y-m-d H:i:s', $mtime) : '-',
                            'modified_timestamp' => $mtime,
                            'is_empty' => $fileSize === 0,
                        ];
                    }
                }
            }

            // Check if PHP error log is explicitly located somewhere else
            if (config('raytech-log-viewer.logs.scan_php_error_log', false)) {
                $phpErrorLog = ini_get('error_log');
                if (!empty($phpErrorLog) && file_exists($phpErrorLog)) {
                    $realPath = realpath($phpErrorLog) ?: $phpErrorLog;
                    $key = md5('server_' . $realPath);
                    if (!isset($logFiles[$key])) {
                        $fileSize = filesize($realPath);
                        $mtime = filemtime($realPath);
                        $logFiles[$key] = [
                            'key' => $key,
                            'name' => basename($realPath),
                            'category' => 'Server Logs',
                            'type' => 'server',
                            'path' => $realPath,
                            'size' => $fileSize,
                            'size_formatted' => $this->formatBytes($fileSize),
                            'modified' => $mtime ? date('Y-m-d H:i:s', $mtime) : '-',
                            'modified_timestamp' => $mtime,
                            'is_empty' => $fileSize === 0,
                        ];
                    }
                }
            }
        }

        // 3. Custom extra log files specified in config/env
        $customEntries = $this->getCustomConfigEntries();
        if (!empty($customEntries)) {
            $searchDirs = array_unique(array_filter([
                function_exists('storage_path') ? storage_path('logs') : null,
                ...$serverDirs,
                function_exists('base_path') ? base_path() : null,
            ]));

            foreach ($customEntries as $entry) {
                // If it is a directory, it was already handled by getServerLogDirectories
                if (is_dir($entry) || (function_exists('base_path') && is_dir(base_path($entry)))) {
                    continue;
                }

                $resolvedPath = null;

                // Check exact file path
                if (file_exists($entry) && is_file($entry)) {
                    $resolvedPath = realpath($entry) ?: $entry;
                } elseif (function_exists('base_path') && file_exists(base_path($entry)) && is_file(base_path($entry))) {
                    $resolvedPath = realpath(base_path($entry)) ?: base_path($entry);
                } else {
                    // Check known search directories
                    foreach ($searchDirs as $sDir) {
                        if (empty($sDir)) continue;
                        $candidate = rtrim($sDir, '/\\') . DIRECTORY_SEPARATOR . $entry;
                        if (file_exists($candidate) && is_file($candidate)) {
                            $resolvedPath = realpath($candidate) ?: $candidate;
                            break;
                        }
                    }
                }

                if (!$resolvedPath && function_exists('storage_path')) {
                    $resolvedPath = storage_path('logs/' . ltrim($entry, '/\\'));
                }

                if (!$resolvedPath) {
                    continue;
                }

                $realPath = realpath($resolvedPath) ?: $resolvedPath;
                $normalizedPath = str_replace('\\', '/', $realPath);
                $type = str_contains($normalizedPath, 'storage/logs') ? 'laravel' : 'server';
                $category = $type === 'laravel' ? 'Laravel Logs' : 'Custom Logs';
                $key = md5($type . '_' . $realPath);

                if (!isset($logFiles[$key])) {
                    $fileSize = file_exists($realPath) ? filesize($realPath) : 0;
                    $mtime = file_exists($realPath) ? filemtime($realPath) : 0;

                    $logFiles[$key] = [
                        'key' => $key,
                        'name' => basename($realPath),
                        'category' => $category,
                        'type' => $type,
                        'path' => $realPath,
                        'size' => $fileSize,
                        'size_formatted' => $this->formatBytes($fileSize),
                        'modified' => $mtime ? date('Y-m-d H:i:s', $mtime) : '-',
                        'modified_timestamp' => $mtime,
                        'is_empty' => $fileSize === 0,
                    ];
                }
            }
        }

        // Apply ignore filter patterns if configured
        $ignorePatterns = config('raytech-log-viewer.logs.ignore_files', []);
        if (!empty($ignorePatterns) && is_array($ignorePatterns)) {
            $logFiles = array_filter($logFiles, function ($file) use ($ignorePatterns) {
                foreach ($ignorePatterns as $pattern) {
                    if (fnmatch($pattern, $file['name'])) {
                        return false;
                    }
                }
                return true;
            });
        }

        static::$cachedLogFiles = $logFiles;
        static::$cacheTimestamp = time();

        return $logFiles;
    }

    /**
     * Resolve a requested log file safely.
     *
     * @param string|null $fileKey
     * @param string|null $fileName
     * @param array $allFiles
     * @return array|null
     */
    public function resolveLogFile(?string $fileKey, ?string $fileName, array $allFiles): ?array
    {
        if (empty($allFiles)) {
            return null;
        }

        // Lookup by key
        if (!empty($fileKey) && isset($allFiles[$fileKey])) {
            return $allFiles[$fileKey];
        }

        // Lookup by name
        if (!empty($fileName)) {
            foreach ($allFiles as $file) {
                if (strtolower($file['name']) === strtolower($fileName)) {
                    return $file;
                }
            }
        }

        // Default preference: 'laravel.log' first
        foreach ($allFiles as $file) {
            if (strtolower($file['name']) === 'laravel.log') {
                return $file;
            }
        }

        // If 'laravel.log' does not exist, select the 1st log file
        return reset($allFiles) ?: null;
    }

    /**
     * Efficiently read the tail of a log file without loading large files into memory.
     * Uses reverse chunked seeks so multi-gigabyte log files are handled instantaneously.
     *
     * @param string $filePath
     * @param int $maxLines
     * @param int $maxBytes
     * @return array
     */
    public function readTail(string $filePath, int $maxLines = 500, int $maxBytes = 4194304): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $fsize = filesize($filePath);
        if ($fsize === 0) {
            return [];
        }

        $fp = @fopen($filePath, 'rb');
        if (!$fp) {
            return [];
        }

        $buffer = '';
        $chunkSize = 65536;  // 64KB chunk buffer (reduces disk seek/read syscalls by 4x)
        $bytesToRead = min($fsize, $maxBytes);
        $pos = $fsize;
        $newlineCount = 0;

        while ($pos > 0 && strlen($buffer) < $bytesToRead && $newlineCount <= $maxLines) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            fseek($fp, $pos);
            $chunk = fread($fp, $readSize);
            $buffer = $chunk . $buffer;
            $newlineCount += substr_count($chunk, "\n");
        }
        fclose($fp);

        $allLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $buffer));

        // Trim empty trailing element if exists
        if (!empty($allLines) && end($allLines) === '') {
            array_pop($allLines);
        }

        if (count($allLines) > $maxLines) {
            $allLines = array_slice($allLines, -$maxLines);
        }

        return $allLines;
    }

    /**
     * Truncate / clear a log file.
     *
     * @param string $filePath
     * @return bool
     */
    public function clearFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $cleared = false;
        try {
            $fp = @fopen($filePath, 'w');
            if ($fp !== false) {
                fclose($fp);
                $cleared = true;
            } else {
                $cleared = @file_put_contents($filePath, '') !== false;
            }
        } catch (\Throwable $e) {
            $cleared = false;
        }

        if ($cleared) {
            static::$cachedLogFiles = null;
        }

        return $cleared;
    }

    /**
     * Format byte sizes into human readable strings.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min((int) $pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
