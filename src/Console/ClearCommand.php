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
    protected $signature = 'raytech-log-viewer:clear {file? : Optional filename of the log to clear (e.g. laravel.log)} {--all : Clear all detected log files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear one or all log files tracked by RayTech Log Viewer';

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
        $allFiles = $fileManager->getAllLogFiles();

        if (empty($allFiles)) {
            $this->warn('No log files detected.');
            return Command::SUCCESS;
        }

        if ($clearAll) {
            if (!$this->confirm('Are you sure you want to clear ALL detected log files?', false)) {
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
