<?php

namespace IBroStudio\PipedTasks\Commands;

use Illuminate\Console\Command;

class PipedTasksInstallCommand extends Command
{
    protected $signature = 'piped-tasks:install';

    protected $description = 'Piped Tasks installer';

    public function handle(): int
    {
        $this->comment('Installing Piped Tasks package...');

        $this->callSilently('vendor:publish', [
            '--tag' => 'piped-tasks-config',
        ]);

        $this->callSilently('vendor:publish', [
            '--tag' => 'piped-tasks-migrations',
        ]);

        $this->call('migrate');

        $this->info('Piped Tasks installed');

        return self::SUCCESS;
    }
}
