<?php

namespace IBroStudio\PipedTasks\Tests\Support\Processes;

use IBroStudio\PipedTasks\Process;
use IBroStudio\PipedTasks\Tests\Support\Processes\Tasks\ResumableFakeTask;
use IBroStudio\PipedTasks\Tests\Support\Processes\Tasks\ResumableFakeTask2;

class ResumableProcess extends Process
{
    use \IBroStudio\PipedTasks\Concerns\ResumableProcess;

    protected array $tasks = [
        ResumableFakeTask::class,
        ResumableFakeTask2::class,
    ];
}
