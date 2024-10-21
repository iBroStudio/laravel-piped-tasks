<?php

use IBroStudio\PipedTasks\Commands\PipedTasksInstallCommand;
use Symfony\Component\Console\Command\Command;

use function Pest\Laravel\artisan;

it('can run the install command', function () {
    $config = config_path('piped-tasks.php');
    File::delete($config);

    artisan(PipedTasksInstallCommand::class)
        ->expectsOutput('Installing Piped Tasks package...')
        ->expectsOutput('Piped Tasks installed')
        ->assertExitCode(Command::SUCCESS);

    expect($config)->toBeFile();
});
