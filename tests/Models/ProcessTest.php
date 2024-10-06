<?php

use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;
use IBroStudio\PipedTasks\Tests\Support\Processes\ResumableProcess;

use function Pest\Laravel\assertModelExists;

it('can create a process', function () {
    assertModelExists(
        Process::factory()->create(['class' => ResumableProcess::class])
    );
});

it('can create a task', function () {
    assertModelExists(
        Task::factory()
            ->for(Process::factory()->create(['class' => ResumableProcess::class]))
            ->create()
    );
});
