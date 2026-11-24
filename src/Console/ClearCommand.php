<?php

namespace Raytech\LogViewer\Console;

use Illuminate\Console\Command;
use Raytech\LogViewer\Services\LogFileManager;

class ClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raytech-log-viewer:clear 
                            {file? : Optional filename of the log to clear (e.g. laravel.log)} 
                            {--all : Clear all detected log files}
                            {--days= : Clear daily/rotated log files older than X days (requires features.allow_days_cleanup)}
                            {--force : Force execution without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear one, all, or aged daily log files tracked by RayTech Log Viewer';

    /**
     * Execute the console command.
     *
     * @param LogFileManager $fileManager
     * @return int
     */
    public function handle(LogFileManager $fileManager): int
    {
        $targetName = $this->argument('file');
        $clearAll = $this->option('all');
        $days = $this->option('days');
        $allFiles = $fileManager->getAllLogFiles(true);

        if (empty($allFiles)) {
            $this->warn('No log files detected.');
            return Command::SUCCESS;
        }

        // 1. Retention cleanup by age in days
        if ($days !== null) {
            $isAllowed = (bool) config('raytech-log-viewer.features.allow_days_cleanup', false);
            if (!$isAllowed) {
                $this->error("✖ Retention cleanup via --days is disabled in configuration. Enable 'features.allow_days_cleanup' or set LOG_VIEWER_ALLOW_DAYS_CLEANUP=true in your .env to use this feature.");
                return Command::FAILURE;
            }

            $daysInt = (int) $days;
            if ($daysInt <= 0) {
                $this->error("✖ The --days option must be a positive integer greater than 0.");
                return Command::FAILURE;
            }

            $cutoffTimestamp = strtotime("-{$daysInt} days");
            $eligibleFiles = [];

            foreach ($allFiles as $file) {
                $filePath = $file['path'];
                if (!file_exists($filePath)) {
                    continue;
                }

                // Never delete main active logs like 'laravel.log'
                if (strtolower($file['name']) === 'laravel.log') {
                    continue;
                }

                // Check date from filename (YYYY-MM-DD) or file modification timestamp
                $hasDate = preg_match('/(\d{4}-\d{2}-\d{2})/', $file['name'], $dm);
                $fileTime = $hasDate ? strtotime($dm[1]) : filemtime($filePath);

                if ($fileTime && $fileTime < $cutoffTimestamp) {
                    $eligibleFiles[] = $file;
                }
            }

            if (empty($eligibleFiles)) {
                $this->info("No log files found older than {$daysInt} day(s).");
                return Command::SUCCESS;
            }

            $this->warn("Found " . count($eligibleFiles) . " log file(s) older than {$daysInt} day(s):");
            foreach ($eligibleFiles as $ef) {
                $this->line(" - {$ef['name']} ({$ef['size_formatted']}, modified: {$ef['modified']})");
            }

            if (!$this->option('force') && !$this->confirm("Are you sure you want to delete these " . count($eligibleFiles) . " file(s)?", false)) {
                $this->info("Operation cancelled.");
                return Command::SUCCESS;
            }

            $deletedCount = 0;
            foreach ($eligibleFiles as $ef) {
                try {
                    if (@unlink($ef['path'])) {
                        $this->line("✔ Deleted: <fg=cyan>{$ef['name']}</>");
                        $deletedCount++;
                    } else {
                        // Fallback: truncate if cannot unlink
                        $fileManager->clearFile($ef['path']);
                        $this->line("✔ Truncated: <fg=cyan>{$ef['name']}</>");
                        $deletedCount++;
                    }
                } catch (\Throwable $e) {
                    $this->error("✖ Could not remove {$ef['name']}: " . $e->getMessage());
                }
            }

            $this->info("Successfully cleaned up {$deletedCount} log file(s) older than {$daysInt} day(s).");
            return Command::SUCCESS;
        }

        if ($clearAll) {
            if (!$this->option('force') && !$this->confirm('Are you sure you want to clear ALL detected log files?', false)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }

            foreach ($allFiles as $file) {
                if (file_exists($file['path'])) {
                    $fileManager->clearFile($file['path']);
                    $this->line("✔ Cleared: <fg=cyan>{$file['name']}</>");
                }
            }

            $this->info('All log files have been cleared.');
            return Command::SUCCESS;
        }

        $target = $fileManager->resolveLogFile(null, $targetName, $allFiles);

        if (!$target || !file_exists($target['path'])) {
            $this->error("Log file '{$targetName}' was not found.");
            return Command::FAILURE;
        }

        if ($fileManager->clearFile($target['path'])) {
            $this->info("✔ Log file '{$target['name']}' cleared successfully.");
            return Command::SUCCESS;
        } else {
            $this->error("✖ Failed to clear log file '{$target['name']}'. Check file write permissions.");
            return Command::FAILURE;
        }
    }
}
