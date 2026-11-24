<?php

namespace Raytech\LogViewer\Console;

use Illuminate\Console\Command;
use Raytech\LogViewer\Services\AssetCompiler;

class BuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raytech-log-viewer:build 
                            {--css : Only compile minified CSS}
                            {--js : Only compile obfuscated JS}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile and obfuscate RayTech Log Viewer CSS and JS assets into production .min files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('⚙️  Compiling RayTech Log Viewer production assets...');

        $basePath = dirname(__DIR__, 2);
        $results = AssetCompiler::compile($basePath);

        if (isset($results['css']) && !$this->option('js')) {
            $c = $results['css'];
            $kb = round($c['min_size'] / 1024, 1);
            $this->line("✔ <fg=green>CSS</>: public/css/raytech-log-viewer.min.css ({$kb} KB, {$c['reduction']} reduction)");
        }

        if (isset($results['js']) && !$this->option('css')) {
            $j = $results['js'];
            $kb = round($j['min_size'] / 1024, 1);
            $this->line("✔ <fg=green>JS</>:  public/js/raytech-log-viewer.min.js ({$kb} KB, {$j['reduction']} reduction, obfuscated & string-encrypted)");
        }

        $this->newLine();
        $this->info('✅ Production assets compiled successfully!');
        $this->comment('Tip: Run "php artisan raytech-log-viewer:update" to re-publish compiled assets to your host application public/ folder.');

        return Command::SUCCESS;
    }
}
