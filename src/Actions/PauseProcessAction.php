<?php

namespace IBroStudio\PipedTasks\Actions;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\ProcessContract;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use Spatie\QueueableAction\QueueableAction;

final class PauseProcessAction
{
    use QueueableAction;

    public function execute(Process $process, Payload $payload)
    {
        $process->update([
            'payload' => serialize($payload),
            'state' => ProcessStatesEnum::WAITING,
        ]);
    }
}
