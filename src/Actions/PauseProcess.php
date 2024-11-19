<?php

namespace IBroStudio\PipedTasks\Actions;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use Lorisleiva\Actions\Concerns\AsAction;

final class PauseProcess
{
    use AsAction;

    public function handle(
        Process $process,
        Payload $payload
    ): void {
        $process->update([
            'payload' => serialize($payload),
            'state' => ProcessStatesEnum::WAITING,
        ]);
    }
}
