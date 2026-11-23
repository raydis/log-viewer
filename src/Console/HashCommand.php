<?php

namespace Raytech\LogViewer\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class HashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raytech-log-viewer:hash 
                            {password? : The plain-text password to hash} 
                            {--algo=bcrypt : Hash algorithm (bcrypt, sha256, or encrypt)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a secure hash or encrypted string for LOG_VIEWER_PASSWORD';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $plain = $this->argument('password');

        if (empty($plain)) {
            $plain = $this->secret('Enter the password to hash');
        }

        if (empty($plain)) {
            $this->error('Password cannot be empty.');
            return 1;
        }

        $algo = strtolower((string) $this->option('algo'));

        $this->newLine();
        $this->info('🔒 Generating Secure Hash for RayTech Log Viewer...');

        $hashed = match ($algo) {
            'sha256' => hash('sha256', $plain),
            'sha512' => hash('sha512', $plain),
            'encrypt' => Crypt::encryptString($plain),
            default => Hash::make($plain),
        };

        $this->table(
            ['Method', 'Hashed Output / Env Value'],
            [
                [strtoupper($algo), $hashed],
            ]
        );

        $this->newLine();
        $this->info("Paste this in your .env file:");
        $this->line("<comment>LOG_VIEWER_PASSWORD=\"{$hashed}\"</comment>");
        $this->newLine();

        return 0;
    }
}
