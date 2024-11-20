<?php

use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\TestSupport\Models\ProcessableFakeModel;

it('can have a processable', function () {
    $processable = ProcessableFakeModel::factory()->create();
    $process = Process::factory()->create();

    expect($process->addProcessable($processable))->toBeTrue()
        ->and($process->processable->is($processable))->toBeTrue();
});
