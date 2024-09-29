<?php

use IBroStudio\PipedTasks\Commands\MakeTaskCommand;
use Symfony\Component\Console\Command\Command;

use function Pest\Laravel\artisan;

it('can generate a new task', function () {

    artisan(MakeTaskCommand::class, [
        'name' => 'ActionFakeTask',
        '--payload' => 'FakePayload',
        '--force' => true,
    ])
        ->assertExitCode(0);

    expect(
        app_path('Processes/Tasks/ActionFakeTask.php')
    )->toBeFile()
        ->and(
            file_get_contents(app_path('Processes/Tasks/ActionFakeTask.php'))
        )->toContain('final readonly class ActionFakeTask')
        ->and(
            app_path('Actions/ActionFakeAction.php')
        )->toBeFile()
        ->and(
            file_get_contents(app_path('Actions/ActionFakeAction.php'))
        )->toContain('final readonly class ActionFakeAction');
});

it('asks for the payload when it is missing', function () {

    artisan(MakeTaskCommand::class, [
        'name' => 'ActionFakeTask',
        '--force' => true,
    ])
        ->expectsQuestion('What payload do you want to use for this task?', 'FakePayload')
        ->assertExitCode(0);

    expect(
        app_path('Processes/Tasks/ActionFakeTask.php')
    )->toBeFile()
        ->and(
            file_get_contents(app_path('Processes/Tasks/ActionFakeTask.php'))
        )->toContain('use App\Processes\Payloads\Contracts\FakePayload;');
});
