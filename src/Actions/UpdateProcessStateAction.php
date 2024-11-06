<?php

namespace IBroStudio\PipedTasks\Actions;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use Spatie\QueueableAction\QueueableAction;

final class UpdateProcessStateAction
{
    use QueueableAction;

    public function execute(
        Process $process,
        ProcessStatesEnum $state
    ) {
        $process->update(['state' => $state]);
    }
}
