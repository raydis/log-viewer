<?php

namespace Raytech\LogViewer\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raytech-log-viewer:install {--force : Overwrite existing configuration file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and publish RayTech Log Viewer configuration and assets';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🚀 Installing RayTech Log Viewer Plugin...');

        $params = ['--tag' => 'raytech-log-viewer-config'];
        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        $assetParams = ['--tag' => 'raytech-log-viewer-assets'];
        if ($this->option('force')) {
            $assetParams['--force'] = true;
        }

        $this->call('vendor:publish', $assetParams);

        $prefix = config('raytech-log-viewer.route.prefix', 'log-viewer');
        $url = url($prefix);

        $this->newLine();
        $this->info('✅ RayTech Log Viewer installed successfully!');
        $this->line("📍 Access URL: <fg=cyan>{$url}</>");
        $this->line('⚙️  Config File: <fg=yellow>config/raytech-log-viewer.php</>');
        $this->newLine();
        $this->comment('Tip: You can customize theme color, route prefix, middleware, and extra log files in config/raytech-log-viewer.php');

        return Command::SUCCESS;
    }
}
