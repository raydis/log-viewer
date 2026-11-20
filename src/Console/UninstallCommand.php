<?php

namespace Raytech\LogViewer\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UninstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raytech-log-viewer:uninstall 
                            {--force : Force removal without interactive confirmation}
                            {--keep-config : Keep published config/raytech-log-viewer.php}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall RayTech Log Viewer and remove all published assets and configuration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->warn('⚠️  Preparing to uninstall RayTech Log Viewer...');

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to remove all published RayTech Log Viewer assets and configuration files?', true)) {
                $this->info('Uninstall cancelled.');
                return Command::SUCCESS;
            }
        }

        // 1. Remove published assets
        if (function_exists('public_path')) {
            $assetsPath = public_path('vendor/raytech-log-viewer');
            if (File::exists($assetsPath)) {
                File::deleteDirectory($assetsPath);
                $this->line('🗑️  Removed published assets: <fg=yellow>public/vendor/raytech-log-viewer/</>');
            }
        }

        // 2. Remove published views (if any)
        if (function_exists('resource_path')) {
            $viewsPath = resource_path('views/vendor/raytech-log-viewer');
            if (File::exists($viewsPath)) {
                File::deleteDirectory($viewsPath);
                $this->line('🗑️  Removed published views: <fg=yellow>resources/views/vendor/raytech-log-viewer/</>');
            }
        }

        // 3. Remove published configuration
        if (!$this->option('keep-config')) {
            if (function_exists('config_path')) {
                $configPath = config_path('raytech-log-viewer.php');
                if (File::exists($configPath)) {
                    File::delete($configPath);
                    $this->line('🗑️  Removed configuration file: <fg=yellow>config/raytech-log-viewer.php</>');
                }
            }
        } else {
            $this->line('ℹ️  Retained configuration file: <fg=cyan>config/raytech-log-viewer.php</>');
        }

        $this->newLine();
        $this->info('✅ RayTech Log Viewer published files removed successfully!');
        $this->newLine();
        $this->comment('To completely remove the package dependency from your project, run:');
        $this->line('  <fg=yellow>composer remove raytech/raytech-log-viewer</>');
        $this->newLine();

        return Command::SUCCESS;
    }
}
