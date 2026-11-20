<?php

namespace Raytech\LogViewer\Console;

use Illuminate\Console\Command;
use Raytech\LogViewer\RaytechLogViewer;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raytech-log-viewer:update {--force : Force overwrite existing assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update RayTech Log Viewer published assets to latest version';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $version = RaytechLogViewer::VERSION;
        $this->info("🚀 Updating RayTech Log Viewer assets to v{$version}...");

        $this->call('vendor:publish', [
            '--tag' => 'raytech-log-viewer-assets',
            '--force' => true,
        ]);

        $this->newLine();
        $this->info("✅ RayTech Log Viewer assets updated to v{$version} successfully!");

        return Command::SUCCESS;
    }
}
