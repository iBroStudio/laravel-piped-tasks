<?php

namespace IBroStudio\PipedTasks\Actions;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Task;
use Spatie\QueueableAction\QueueableAction;

final class UpdateTaskStateAction
{
    use QueueableAction;

    public function execute(
        Task $task,
        ProcessStatesEnum $state
    ) {
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
