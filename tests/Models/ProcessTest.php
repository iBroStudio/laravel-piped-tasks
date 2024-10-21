<?php

use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;

use function Pest\Laravel\assertModelExists;

it('can create a process', function () {
    assertModelExists(
        Process::factory()->create()
    );
});

it('can create a task', function () {
    assertModelExists(
        Task::factory()
            ->for(Process::factory())
            ->create()
    );
});
