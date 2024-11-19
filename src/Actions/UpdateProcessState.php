<?php

namespace IBroStudio\PipedTasks\Actions;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use Lorisleiva\Actions\Concerns\AsAction;

final class UpdateProcessState
{
    use AsAction;

    public function handle(
        Process $process,
        ProcessStatesEnum $state
    ): void {
        $process->update(['state' => $state]);
    }
}
