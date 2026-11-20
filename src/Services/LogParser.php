<?php

namespace Raytech\LogViewer\Services;

class LogParser
{
    /**
     * Detect log level of a single log line or message header.
     *
     * @param string $line
     * @return string (error|warning|info|debug|trace|default)
     */
    public function detectLogLevel(string $line): string
    {
        // Avoid classifying access log requests as errors just because URL contains /raytech-log-viewer or /error-log
        if (preg_match('/"(GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS)\s+[^"]*(\/raytech-log-viewer|\/error-log)/i', $line)) {
            return 'default';
        }

        if (preg_match('/(^|[\s\[\.:])(EMERGENCY|ALERT|CRITICAL|ERROR|FATAL|EXCEPTION|PARSE ERROR)(\b|\]|\:)|\[core:error\]|\[.*?error\]|\.ERROR:/i', $line)) {
            return 'error';
        }

        if (preg_match('/(^|[\s\[\.:])(WARNING|WARN|DEPRECATED|NOTICE)(\b|\]|\:)|\[.*?warn\]|\.WARNING:|\.NOTICE:/i', $line)) {
            return 'warning';
        }

        if (preg_match('/(^|[\s\[\.:])(INFO)(\b|\]|\:)|\[.*?info\]|\[Note\]|\.INFO:/i', $line)) {
            return 'info';
        }

        if (preg_match('/(^|[\s\[\.:])(DEBUG)(\b|\]|\:)|\.DEBUG:/i', $line)) {
            return 'debug';
        }

        if (preg_match('/(\bStack trace\b|PHP\s+\d+\.|\s*#\d+\s+)/i', $line)) {
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
     * Check if a line is a continuation line of a multi-line log entry.
     *
     * @param string $line
     * @return bool
     */
    public function isContinuationLine(string $line): bool
    {
        // Indented lines (tabs or 2+ spaces at start) are continuation lines
        if (preg_match('/^(\t|\s{2,})/', $line)) {
            return true;
        }

        $trimmed = trim($line);

        // Empty line in the middle of a log entry
        if ($trimmed === '') {
            return true;
        }

        // Stack trace frame (e.g. #0 /path/to/file.php(42): ...)
        if (preg_match('/^#\d+\s+/', $trimmed)) {
            return true;
        }

        // Common trace headers and exception markers
        if (preg_match('/^(Stack trace:|\[stacktrace\]|\{main\}|\.{3}\s*\d+\s*more|thrown in\b)/i', $trimmed)) {
            return true;
        }

        // JSON or array data lines
        if (preg_match('/^("[^"]+":|\{|\}|\[|\])/', $trimmed)) {
            return true;
        }

        return false;
    }

    /**
     * Extract a clean single-line short summary from the main header line.
     *
     * @param string $line
     * @return string
     */
    public function extractShortText(string $line): string
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

        $text = trim($text);

        if ($text === '') {
            $text = trim($line);
        }

        return $text;
    }

    /**
     * Parse raw lines into structured log entries.
     * Supports multi-line grouping and ordering ('desc' for latest on top).
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
            $isContinuation = ($currentEntry !== null) && $this->isContinuationLine($rawLine);

            if ($isContinuation) {
                // Append line to current log entry
                $currentEntry['lines'][] = $rawLine;
            } else {
                // Save previous entry if exists
                if ($currentEntry !== null) {
                    $entries[] = $this->finalizeEntry($currentEntry);
                }

                // Start new log entry
                $level = $this->detectLogLevel($rawLine);
                $timestamp = $this->extractTimestamp($rawLine);
                $shortText = $this->extractShortText($rawLine);

                $currentEntry = [
                    'num' => $lineNum,
                    'level' => $level,
                    'timestamp' => $timestamp,
                    'short_text' => $shortText,
                    'lines' => [$rawLine],
                ];
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

        return [
            'lines' => $entries,
            'stats' => $stats,
            'order' => $order,
        ];
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

        // Determine if this entry has details to expand
        $hasDetails = ($lineCount > 1) || (mb_strlen($firstLine) > 110);

        return [
            'id' => 'log-entry-' . $entry['num'],
            'num' => $entry['num'],
            'level' => $level,
            'timestamp' => $entry['timestamp'],
            'short_text' => $entry['short_text'],
            'full_text' => $fullText,
            'has_details' => $hasDetails,
            'lines_count' => $lineCount,
        ];
    }
}
