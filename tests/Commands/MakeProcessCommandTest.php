<?php

use IBroStudio\PipedTasks\Commands\MakeProcessCommand;
use IBroStudio\PipedTasks\Exceptions\BadProcessNameException;

use function Pest\Laravel\artisan;

it('can generate a new process', function () {

    artisan(MakeProcessCommand::class, ['name' => 'ActionFakeProcess', '--force' => true])
        ->assertExitCode(0);

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
        )->toContain('final class ActionFakePayload implements Payload, FakePayload')
        ->and(
            app_path('Processes/Payloads/Contracts/FakePayload.php')
        )->toBeFile()
        ->and(
            file_get_contents(app_path('Processes/Payloads/Contracts/FakePayload.php'))
        )->toContain('interface FakePayload');
});

it('controls the process name', function () {

    artisan(MakeProcessCommand::class, ['name' => 'ActionFake']);
})->throws(BadProcessNameException::class);
