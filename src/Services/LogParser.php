<?php

namespace Raytech\LogViewer\Services;

class LogParser
{
    /**
     * Detect log level of a single log line.
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
     * Parse raw lines into structured array and compute level statistics.
     *
     * @param array $rawLines
     * @return array
     */
    public function parseLines(array $rawLines): array
    {
        $parsed = [];
        $stats = [
            'total' => 0,
            'error' => 0,
            'warning' => 0,
            'info' => 0,
            'debug' => 0,
            'trace' => 0,
        ];

        foreach ($rawLines as $idx => $line) {
            $level = $this->detectLogLevel($line);
            if (isset($stats[$level])) {
                $stats[$level]++;
            }
            $stats['total']++;

            $parsed[] = [
                'num' => $idx + 1,
                'level' => $level,
                'text' => $line,
            ];
        }

        return [
            'lines' => $parsed,
            'stats' => $stats,
        ];
    }
}
