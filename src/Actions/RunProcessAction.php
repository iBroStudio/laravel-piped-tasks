<?php

namespace IBroStudio\PipedTasks\Actions;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\ProcessContract;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use Spatie\QueueableAction\QueueableAction;

final class RunProcessAction
{
    use QueueableAction;

    public function execute(
        ProcessContract|ProcessModelContract $process,
        Payload $payload
    ) {
        $process->run($payload);
    }
}
