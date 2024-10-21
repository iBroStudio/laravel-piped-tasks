<?php

use IBroStudio\PipedTasks\Commands\MakeProcessCommand;
use Illuminate\Console\Command;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function Pest\Laravel\artisan;

it('can generate a new process', function () {
    /*
     *
     *     Prompt::fake([
        'A','c','t','i','o','n','F','a','k','e','P','r','o','c','e','s','s', Key::ENTER, // Process name
        Key::ENTER, // Process type = process
        Key::ENTER, // Process location = app
        Key::DOWN, Key::ENTER, // Force creation = yes
    ]);
    Prompt::fake([ Key::DOWN, Key::ENTER])
     */
    artisan(MakeProcessCommand::class)
        ->expectsQuestion('What should the process be named?', 'ActionFakeProcess')
        ->expectsQuestion('What kind of process do you want to create?', 'process')
        ->expectsQuestion('Where do you want to create the process?', 'app')
        ->expectsQuestion('Force creation?', 'yes')
        ->assertExitCode(Command::SUCCESS);

    expect(
        app_path('Processes/ActionFakeProcess.php')
    )->toBeFile()
        ->and(
            file_get_contents(app_path('Processes/ActionFakeProcess.php'))
        )->toContain('class ActionFakeProcess extends Process')
        ->and(
            app_path('Processes/Payloads/ActionFakePayload.php')
        )->toBeFile()
        ->and(
            file_get_contents(app_path('Processes/Payloads/ActionFakePayload.php'))
        )->toContain('final class ActionFakePayload extends PayloadAbstract implements FakePayload')
        ->and(
            app_path('Processes/Payloads/Contracts/FakePayload.php')
        )->toBeFile()
        ->and(
            file_get_contents(app_path('Processes/Payloads/Contracts/FakePayload.php'))
        )->toContain('interface FakePayload');
});

it('controls the process name', function () {
    artisan(MakeProcessCommand::class)
        ->expectsQuestion('What should the process be named?', 'ActionFake')
        ->expectsOutputToContain('Incorrect process name. Use "<Action><Domain>Process" format')
        ->assertExitCode(Command::FAILURE);
});
