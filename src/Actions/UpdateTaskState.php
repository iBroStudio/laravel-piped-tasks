<?php

namespace IBroStudio\PipedTasks\Actions;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Task;
use Lorisleiva\Actions\Concerns\AsAction;

final class UpdateTaskState
{
    use AsAction;

    public function handle(
        Task $task,
        ProcessStatesEnum $state
    ): void {
        $task->process->update([
            'state' => match ($state) {
                ProcessStatesEnum::WAITING => ProcessStatesEnum::WAITING,
                ProcessStatesEnum::RESUME => ProcessStatesEnum::RESUME,
                default => ProcessStatesEnum::PROCESSING,
            },
        ]);

        $task->update([
            'state' => match ($state) {
                ProcessStatesEnum::RESUME => ProcessStatesEnum::COMPLETED,
                default => $state,
            },
        ]);
    }
}
