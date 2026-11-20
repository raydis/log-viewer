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
    protected $signature = 'raytech-log-viewer:update 
                            {--config : Overwrite and update config/raytech-log-viewer.php to latest default}
                            {--all : Update both published assets and configuration file}
                            {--force : Force overwrite assets and config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update RayTech Log Viewer published assets and configuration to latest version';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $version = RaytechLogViewer::VERSION;
        $this->info("🚀 Updating RayTech Log Viewer to v{$version}...");

        // 1. Update Assets
        $this->call('vendor:publish', [
            '--tag' => 'raytech-log-viewer-assets',
            '--force' => true,
        ]);
        $this->line('✅ Assets (CSS, JS, logo) updated in <fg=cyan>public/vendor/raytech-log-viewer/</>');

        // 2. Update Configuration if requested
        if ($this->option('config') || $this->option('all')) {
            $this->call('vendor:publish', [
                '--tag' => 'raytech-log-viewer-config',
                '--force' => true,
            ]);
            $this->line('✅ Configuration updated in <fg=cyan>config/raytech-log-viewer.php</>');
        } else {
            $this->newLine();
            $this->comment('ℹ️ Note: Your config/raytech-log-viewer.php was kept intact to protect custom settings.');
            $this->line('   To also overwrite it with the latest default config, run:');
            $this->line('   <fg=yellow>php artisan raytech-log-viewer:update --config</>');
        }

        $this->newLine();
        $this->info("🎉 RayTech Log Viewer v{$version} update completed!");

        return Command::SUCCESS;
    }
}
