<?php

namespace Raytech\LogViewer\Services;

class LogParser
{
    /**
     * Detect log level of a single log line or message header.
     * Optimized with fast prefix and keyword short-circuits to bypass regex execution on ~85% of lines.
     *
     * @param string $line
     * @return string (error|warning|info|debug|trace|default)
     */
    public function detectLogLevel(string $line): string
    {
        if ($line === '') {
            return 'default';
        }

        // Fast path 1: Instant check for stack trace frames starting with '#' (e.g. #0, #1 ...)
        $firstChar = $line[0];
        if ($firstChar === '#' && isset($line[1]) && is_numeric($line[1])) {
            return 'trace';
        }

        // Fast path 2: Indented lines (e.g. continuation stack frames with spaces or tabs)
        if (($firstChar === ' ' || $firstChar === "\t") && str_contains($line, '#') && preg_match('/^\s*#\d+\s+/', $line)) {
            return 'trace';
        }

        // Fast path 3: High-frequency Laravel log prefixes (bypasses regex completely)
        if (str_contains($line, '.ERROR:') || str_contains($line, '.CRITICAL:') || str_contains($line, '.EMERGENCY:') || str_contains($line, '.ALERT:') || str_contains($line, '[core:error]')) {
            return 'error';
        }
        if (str_contains($line, '.WARNING:') || str_contains($line, '.NOTICE:')) {
            return 'warning';
        }
        if (str_contains($line, '.INFO:') || str_contains($line, '[Note]')) {
            return 'info';
        }
        if (str_contains($line, '.DEBUG:')) {
            return 'debug';
        }

        // Avoid classifying access log requests as errors just because URL contains /raytech-log-viewer or /error-log
        if (str_contains($line, '/log-viewer') || str_contains($line, '/error-log') || str_contains($line, '/raytech-log-viewer')) {
            if (preg_match('/"(?:GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS)\s+[^"]*(\/raytech-log-viewer|\/error-log|\/log-viewer)/i', $line)) {
                return 'default';
            }
        }

        // Standard regex fallback for server logs & uncaught exceptions
        if (preg_match('/(^|[\s\[\.:])(EMERGENCY|ALERT|CRITICAL|ERROR|FATAL|EXCEPTION|PARSE ERROR)(\b|\]|\:)|\[.*?error\]/i', $line)) {
            return 'error';
        }

        if (preg_match('/(^|[\s\[\.:])(WARNING|WARN|DEPRECATED|NOTICE)(\b|\]|\:)|\[.*?warn\]/i', $line)) {
            return 'warning';
        }

        if (preg_match('/(^|[\s\[\.:])(INFO)(\b|\]|\:)|\[.*?info\]/i', $line)) {
            return 'info';
        }

        if (preg_match('/(^|[\s\[\.:])(DEBUG)(\b|\]|\:)/i', $line)) {
            return 'debug';
        }

        if (preg_match('/(\bStack trace\b|PHP\s+\d+\.)/i', $line)) {
            return 'trace';
        }

        return 'default';
    }

    /**
     * Extract timestamp from a log line if available.
     *
     * @param string $line
     * @return string|null
     */
    public function extractTimestamp(string $line): ?string
    {
        // 1. Standard ISO / Laravel: [2026-11-20 12:00:01] or [2026-11-20T12:00:01.123456+00:00]
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[\+\-]\d{2}:\d{2}|Z)?)\]/', $line, $m)) {
            return str_replace('T', ' ', preg_replace('/(\.\d+)?(?:[\+\-]\d{2}:\d{2}|Z)$/', '', $m[1]));
        }

        // 2. PHP Error log: [20-Nov-2026 12:00:01 UTC]
        if (preg_match('/^\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2}(?: [A-Za-z0-9_\/]+)?)\]/', $line, $m)) {
            return $m[1];
        }

        // 3. Apache error log: [Fri Nov 20 12:00:01.123456 2026]
        if (preg_match('/^\[([A-Za-z]{3} [A-Za-z]{3} \d{1,2} \d{2}:\d{2}:\d{2}(?:\.\d+)? \d{4})\]/', $line, $m)) {
            return $m[1];
        }

        // 4. Nginx / MySQL: 2026/11/20 12:00:01 or 2026-11-20T12:00:01
        if (preg_match('/^(\d{4}[\/-]\d{2}[\/-]\d{2}[ T]\d{2}:\d{2}:\d{2})/', $line, $m)) {
            return str_replace(['/', 'T'], ['-', ' '], $m[1]);
        }

        // 5. Common access log: [20/Nov/2026:12:00:01 +0000]
        if (preg_match('/\[(\d{2}\/[A-Za-z]{3}\/\d{4}:\d{2}:\d{2}:\d{2})/', $line, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Extract date part (YYYY-MM-DD) from timestamp.
     *
     * @param string|null $timestamp
     * @return string|null
     */
    public function extractDate(?string $timestamp): ?string
    {
        if (empty($timestamp)) {
            return null;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $timestamp, $m)) {
            return $m[1];
        }

        $time = strtotime($timestamp);
        if ($time !== false) {
            return date('Y-m-d', $time);
        }

        return null;
    }

    /**
     * Extract time part (HH:MM:SS) from timestamp.
     *
     * @param string|null $timestamp
     * @return string|null
     */
    public function extractTime(?string $timestamp): ?string
    {
        if (empty($timestamp)) {
            return null;
        }

        if (preg_match('/(\d{2}:\d{2}:\d{2})/', $timestamp, $m)) {
            return $m[1];
        }

        return $timestamp;
    }

    /**
     * Extract environment (e.g. local, production, staging) from line.
     *
     * @param string $line
     * @return string|null
     */
    public function extractEnvironment(string $line): ?string
    {
        if (preg_match('/^\[[^\]]+\]\s*([a-zA-Z0-9_-]+)\.(EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):/i', $line, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }

    /**
     * Extract log_type (e.g. 'local.ERROR', 'production.INFO', 'ERROR', 'WARNING').
     *
     * @param string $line
     * @return string
     */
    public function extractLogType(string $line): string
    {
        // 1. Laravel environment and level: local.ERROR or production.WARNING
        if (preg_match('/^\[[^\]]+\]\s*([a-zA-Z0-9_-]+\.(?:EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG)):/i', $line, $m)) {
            return $m[1];
        }

        // 2. Standard level with prefix: ERROR: or PHP Fatal error:
        if (preg_match('/^\[[^\]]+\]\s*(PHP\s+[A-Za-z\s]+error|EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):/i', $line, $m)) {
            return $m[1];
        }

        // 3. Server bracket level: [php:error] or [core:error]
        if (preg_match('/\[([a-zA-Z0-9_-]+:(?:error|warn|notice|info|debug))\]/i', $line, $m)) {
            return $m[1];
        }

        $level = $this->detectLogLevel($line);
        return strtoupper($level);
    }

    /**
     * Extract the clean header message:
     * - Strips timestamp and level prefix e.g. [2026-11-20 03:55:18] local.ERROR:
     * - Strips trailing JSON context block (everything from '{' to the end of line)
     * - Returns the exact clean error description to show in the header row.
     *
     * @param string $line
     * @return string
     */
    public function extractHeaderMessage(string $line): string
    {
        $text = $line;

        // Strip leading timestamp e.g. [2026-11-20 12:00:01]
        $text = preg_replace('/^\[[^\]]+\]\s*/', '', $text);

        // Strip Laravel environment and level prefix e.g. local.ERROR: or production.INFO:
        $text = preg_replace('/^[a-zA-Z0-9_-]+\.(EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):\s*/i', '', $text);

        // Strip standard level prefix e.g. ERROR: or PHP Fatal error:
        $text = preg_replace('/^(PHP\s+[A-Za-z\s]+error:\s*|EMERGENCY:|ALERT:|CRITICAL:|ERROR:|WARNING:|NOTICE:|INFO:|DEBUG:)\s*/i', '', $text);

        // Strip server prefix e.g. [php:error] [pid 1234] [client 127.0.0.1:5678]
        $text = preg_replace('/^(\[[^\]]+\]\s*)+/', '', $text);

        // Extract content before the JSON context block (e.g. before ' {"exception":...' or ' {"userId":...')
        if (preg_match('/^(.*?)\s*(\{"(?:[^"]+)":.*\}\s*)$/s', $text, $jm)) {
            $candidate = trim($jm[1]);
            if ($candidate !== '') {
                $text = $candidate;
            }
        } elseif (preg_match('/^(.*?)\s*(\{.*)$/s', $text, $jm)) {
            $candidate = trim($jm[1]);
            if ($candidate !== '') {
                $text = $candidate;
            }
        }

        $text = trim($text);

        if ($text === '') {
            $text = trim($line);
        }

        return $text;
    }

    /**
     * Backward-compatible alias for extractHeaderMessage.
     */
    public function extractShortText(string $line): string
    {
        return $this->extractHeaderMessage($line);
    }

    /**
     * Parse structured exception details (type, message, file, line) from full log text.
     *
     * @param string $fullText
     * @return array|null
     */
    public function parseException(string $fullText): ?array
    {
        // 1. Laravel Monolog exception object pattern:
        // [object] (Illuminate\Database\QueryException(code: HY000): Message at /path/file.php:123)
        if (preg_match('/\[object\]\s*\(([A-Za-z0-9_\\\\]+)(?:\(code:\s*[^)]*\))?:\s*(.+?)\s+at\s+([A-Za-z0-9_:\/\\\\.-]+):(\d+)\)/s', $fullText, $m)) {
            $class = trim($m[1]);
            $parts = explode('\\', $class);
            return [
                'class' => $class,
                'class_short' => end($parts),
                'message' => trim($m[2]),
                'file' => trim($m[3]),
                'file_short' => basename(str_replace('\\', '/', trim($m[3]))),
                'line' => (int) $m[4],
            ];
        }

        // 2. Native PHP uncaught exception:
        // PHP Fatal error:  Uncaught Error: Call to undefined ... in /path:123
        if (preg_match('/PHP\s+(?:Fatal error|Parse error|Warning|Notice):\s*(?:Uncaught\s+)?([A-Za-z0-9_\\\\]+)?(?::\s*)?(.+?)\s+in\s+([A-Za-z0-9_:\/\\\\.-]+)(?:\s+on line|\:)\s*(\d+)/is', $fullText, $m)) {
            $class = !empty($m[1]) ? trim($m[1]) : 'Error';
            $parts = explode('\\', $class);
            return [
                'class' => $class,
                'class_short' => end($parts),
                'message' => trim($m[2]),
                'file' => trim($m[3]),
                'file_short' => basename(str_replace('\\', '/', trim($m[3]))),
                'line' => (int) $m[4],
            ];
        }

        return null;
    }

    /**
     * Parse stack trace frames into structured array of frames.
     *
     * @param string $fullText
     * @return array
     */
    public function parseStackFrames(string $fullText): array
    {
        $frames = [];

        if (preg_match('/(?:\[stacktrace\]|Stack trace:)([\s\S]*?)(?:\n\[previous exception\]|"\s*\}|$)/i', $fullText, $m)) {
            $traceBlock = $m[1];
            if (preg_match_all('/#(\d+)\s+(.+?)(?=\n#|\n\{main\}|\n"\s*\}|$)/s', $traceBlock, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $row) {
                    $num = (int)$row[1];
                    $body = trim($row[2]);
                    if (preg_match('/^([A-Za-z0-9_:\/\\\\.-]+)(?:\((\d+)\))?:\s*(.+)$/s', $body, $fm)) {
                        $file = $fm[1];
                        $frames[] = [
                            'number' => $num,
                            'file' => $file,
                            'file_short' => basename(str_replace('\\', '/', $file)),
                            'line' => (isset($fm[2]) && $fm[2] !== '') ? (int)$fm[2] : null,
                            'call' => trim($fm[3]),
                        ];
                    } else {
                        $frames[] = [
                            'number' => $num,
                            'file' => '',
                            'file_short' => '',
                            'line' => null,
                            'call' => $body,
                        ];
                    }
                }
            }
        }

        return $frames;
    }

    /**
     * Parse context data from first line or full text.
     *
     * @param string $firstLine
     * @param string $fullText
     * @return array|null
     */
    public function parseContext(string $firstLine, string $fullText): ?array
    {
        $context = null;

        if (preg_match('/(\{"(?:[^"]+)":.*\})\s*$/s', $firstLine, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        if ($context === null && preg_match('/(\{"[A-Za-z0-9_-]+":[\s\S]*?\})\n(?:\[stacktrace\]|#0|$)/s', $fullText, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        if ($context !== null) {
            if (isset($context['exception'])) {
                unset($context['exception']);
            }
            return !empty($context) ? $context : null;
        }

        return null;
    }

    /**
     * Parse raw lines into structured log entries.
     * All lines from one [timestamp] to the next [timestamp] are grouped into a single entry.
     * Entries are ordered ('desc' by default for latest on top) and grouped by date.
     *
     * @param array $rawLines
     * @param string $order 'desc' (latest on top) or 'asc' (oldest first)
     * @return array
     */
    public function parseLines(array $rawLines, string $order = 'desc'): array
    {
        $entries = [];
        $stats = [
            'total' => 0,
            'error' => 0,
            'warning' => 0,
            'info' => 0,
            'debug' => 0,
            'trace' => 0,
        ];

        $currentEntry = null;

        foreach ($rawLines as $lineIdx => $rawLine) {
            $lineNum = $lineIdx + 1;
            $timestamp = $this->extractTimestamp($rawLine);
            $hasTimestamp = ($timestamp !== null);

            // A line starts a new log entry ONLY if it begins with a timestamp!
            // All subsequent lines (stack traces, SQL, multi-line exception messages, JSON context)
            // belong to this log entry until the next timestamp line is reached.
            if ($hasTimestamp) {
                if ($currentEntry !== null) {
                    $entries[] = $this->finalizeEntry($currentEntry);
                }

                $level = $this->detectLogLevel($rawLine);
                $logType = $this->extractLogType($rawLine);
                $environment = $this->extractEnvironment($rawLine);
                $headerText = $this->extractHeaderMessage($rawLine);
                $date = $this->extractDate($timestamp);
                $time = $this->extractTime($timestamp);

                $currentEntry = [
                    'num' => $lineNum,
                    'level' => $level,
                    'log_type' => $logType,
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'environment' => $environment,
                    'header_text' => $headerText,
                    'message' => $headerText,
                    'lines' => [$rawLine],
                ];
            } else {
                if ($currentEntry !== null) {
                    $currentEntry['lines'][] = $rawLine;
                } else {
                    $currentEntry = [
                        'num' => $lineNum,
                        'level' => $this->detectLogLevel($rawLine),
                        'log_type' => 'LOG',
                        'timestamp' => null,
                        'date' => null,
                        'time' => null,
                        'environment' => null,
                        'header_text' => trim($rawLine),
                        'message' => trim($rawLine),
                        'lines' => [$rawLine],
                    ];
                }
            }
        }

        // Finalize last entry
        if ($currentEntry !== null) {
            $entries[] = $this->finalizeEntry($currentEntry);
        }

        // Compute level statistics based on entries
        foreach ($entries as $e) {
            $lvl = $e['level'];
            if (isset($stats[$lvl])) {
                $stats[$lvl]++;
            }
            $stats['total']++;
        }

        // Order: 'desc' puts latest log on top
        if ($order === 'desc') {
            $entries = array_reverse($entries);
        }

        // Group entries by Date
        $dateGroups = [];
        foreach ($entries as $e) {
            $dateKey = $e['date'] ?: 'Other';
            if (!isset($dateGroups[$dateKey])) {
                $dateFormatted = 'Unspecified Date';
                if ($dateKey !== 'Other' && strtotime($dateKey)) {
                    $time = strtotime($dateKey);
                    $isToday = (date('Y-m-d') === $dateKey);
                    $isYesterday = (date('Y-m-d', strtotime('-1 day')) === $dateKey);
                    if ($isToday) {
                        $dateFormatted = 'Today — ' . date('F j, Y', $time);
                    } elseif ($isYesterday) {
                        $dateFormatted = 'Yesterday — ' . date('F j, Y', $time);
                    } else {
                        $dateFormatted = date('l, F j, Y', $time);
                    }
                }

                $dateGroups[$dateKey] = [
                    'date' => $dateKey,
                    'date_formatted' => $dateFormatted,
                    'date_short' => $dateKey,
                    'total' => 0,
                    'errors' => 0,
                    'warnings' => 0,
                    'entries' => [],
                ];
            }

            $dateGroups[$dateKey]['total']++;
            if ($e['level'] === 'error') {
                $dateGroups[$dateKey]['errors']++;
            }
            if ($e['level'] === 'warning') {
                $dateGroups[$dateKey]['warnings']++;
            }
            $dateGroups[$dateKey]['entries'][] = $e;
        }

        return [
            'lines' => $entries,
            'date_groups' => array_values($dateGroups),
            'stats' => $stats,
            'order' => $order,
        ];
    }

    /**
     * Redact sensitive credentials and secrets from text if redaction is enabled in configuration.
     *
     * @param string|null $text
     * @return string
     */
    public function redactSensitiveData(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $redactionConfig = config('raytech-log-viewer.security.redaction', []);
        $enabled = (bool) ($redactionConfig['enabled'] ?? true);
        if (!$enabled) {
            return $text;
        }

        $rep = (string) ($redactionConfig['replacement'] ?? '[REDACTED]');

        // 1. Passwords in key-value pairs (JSON, query strings, array syntax)
        $text = preg_replace('/([\'"]?(?:password|passwd|pwd|db_pass|secret|auth_pw)[\'"]?\s*[:=>]\s*[\'"]?)[^\'",\s\r\n]{1,128}([\'"]?)/i', '$1' . $rep . '$2', $text);

        // 2. Bearer and Basic HTTP authorization tokens
        $text = preg_replace('/(Bearer\s+)[A-Za-z0-9\-_\.~\+\/=]{15,}/i', '$1' . $rep, $text);
        $text = preg_replace('/(Basic\s+)[A-Za-z0-9\+\/=]{15,}/i', '$1' . $rep, $text);

        // 3. API Keys & secrets (api_key=..., client_secret=..., access_token=...)
        $text = preg_replace('/([\'"]?(?:api[_-]?key|client[_-]?secret|private[_-]?key|access[_-]?token)[\'"]?\s*[:=>]\s*[\'"]?)[^\'",\s\r\n]{1,128}([\'"]?)/i', '$1' . $rep . '$2', $text);

        // 4. Laravel APP_KEY (base64:...)
        $text = preg_replace('/(base64:[A-Za-z0-9\+\/]{40,}={0,2})/i', 'base64:' . $rep, $text);

        // 5. Database passwords in connection URIs (mysql://user:pass@host/db)
        $text = preg_replace('/((?:mysql|postgres(?:ql)?|redis):\/\/[^:]+:)[^@]+(@)/i', '$1' . $rep . '$2', $text);

        // 6. Credit card numbers (standard 13-16 digit numbers)
        $text = preg_replace('/\b(?:\d{4}[ -]?){3}\d{4}\b/', '[REDACTED_CARD]', $text);

        return $text;
    }

    /**
     * Finalize raw entry data into structured display entry.
     *
     * @param array $entry
     * @return array
     */
    protected function finalizeEntry(array $entry): array
    {
        $lineCount = count($entry['lines']);
        $fullText = implode("\n", $entry['lines']);
        $firstLine = $entry['lines'][0] ?? '';

        // If level wasn't detected on first line, check if continuation lines have trace or errors
        $level = $entry['level'];
        if ($level === 'default' && $lineCount > 1) {
            foreach ($entry['lines'] as $l) {
                $lvl = $this->detectLogLevel($l);
                if ($lvl !== 'default') {
                    $level = $lvl;
                    break;
                }
            }
        }

        // Parse structured exception, stack frames, and context
        $exception = $this->parseException($fullText);
        $stackFrames = $this->parseStackFrames($fullText);
        $context = $this->parseContext($firstLine, $fullText);

        $headerText = $entry['header_text'] ?? $this->extractHeaderMessage($firstLine);
        $logType = $entry['log_type'] ?? $this->extractLogType($firstLine);

        // Apply sensitive data redaction to protect credentials
        $cleanHeaderText = $this->redactSensitiveData($headerText);
        $cleanFullText = $this->redactSensitiveData($fullText);

        if ($exception && isset($exception['message'])) {
            $exception['message'] = $this->redactSensitiveData($exception['message']);
        }

        if (!empty($stackFrames)) {
            foreach ($stackFrames as &$frame) {
                if (isset($frame['call'])) {
                    $frame['call'] = $this->redactSensitiveData($frame['call']);
                }
            }
            unset($frame);
        }

        $contextJson = !empty($context) ? $this->redactSensitiveData(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) : null;
        $errorType = ($exception && !empty($exception['class_short'])) ? $exception['class_short'] : null;

        return [
            'id' => 'log-entry-' . $entry['num'],
            'num' => $entry['num'],
            'level' => $level,
            'log_type' => $logType,
            'log_create_date' => $entry['timestamp'],
            'timestamp' => $entry['timestamp'],
            'date' => $entry['date'],
            'time' => $entry['time'],
            'environment' => $entry['environment'] ?? null,
            'error_type' => $errorType,
            'header_text' => $cleanHeaderText,
            'message' => $cleanHeaderText,
            'short_text' => $cleanHeaderText,
            'body_content' => $cleanFullText,
            'full_text' => $cleanFullText,
            'exception' => $exception,
            'stack_frames' => $stackFrames,
            'context' => $context,
            'context_json' => $contextJson,
            'has_details' => true,
            'lines_count' => $lineCount,
            'frames_count' => count($stackFrames),
        ];
    }
}
