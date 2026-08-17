<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeAll extends Command
{
    protected $signature = 'serve:full
                            {--host=127.0.0.1 : The host address to serve the application on}
                            {--port=8000 : The port to serve the application on}';

    protected $description = 'Start both the Laravel API server and the Vite dev server simultaneously';

    public function handle(): int
    {
        $this->info('🚀 TelScout — Starting development servers...');
        $this->newLine();
        $this->line('  <fg=blue>Laravel API</>  → http://' . $this->option('host') . ':' . $this->option('port'));
        $this->line('  <fg=green>Vite (React)</> → http://localhost:5173');
        $this->newLine();

        $laravelProcess = new Process([
            PHP_BINARY,
            'artisan',
            'serve',
            '--host=' . $this->option('host'),
            '--port=' . $this->option('port'),
        ], base_path());

        // Detect npm on the system
        $npmBin = PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';

        $viteProcess = new Process(
            [$npmBin, 'run', 'dev'],
            base_path(),
            ['FORCE_COLOR' => '1']
        );

        $laravelProcess->start(function ($type, $buffer) {
            $this->output->write('<fg=blue>[Laravel]</> ' . $buffer);
        });

        $viteProcess->start(function ($type, $buffer) {
            $this->output->write('<fg=green>[Vite]</>    ' . $buffer);
        });

        $this->info('Both servers are running. Press Ctrl+C to stop.');

        // Keep both alive
        while ($laravelProcess->isRunning() || $viteProcess->isRunning()) {
            sleep(1);
        }

        return self::SUCCESS;
    }
}
