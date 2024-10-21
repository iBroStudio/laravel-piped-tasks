<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Actions\LogProcessAction;
use IBroStudio\PipedTasks\Actions\UpdateTaskStateAction;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use Illuminate\Foundation\Bus\PendingDispatch;
use Spatie\QueueableAction\ActionJob;

trait HasActions
{
    protected bool $isEloquentProcess = false;

    public function isEloquentProcess(): static
    {
        $this->isEloquentProcess = true;

        return $this;
    }

    public function updateProcessAction(ProcessStatesEnum $state): ?PendingDispatch
    {
        if (! $this->isEloquentProcess
            || $this->passable->getProcess()->refresh()->state === ProcessStatesEnum::RESUME
        ) {
            return null;
        }

        return $this->updateEloquentProcessStateAction
            ->onQueue()
            ->execute(
                process: $this->passable->getProcess(),
                state: $state
            )->chain([
                new ActionJob(LogProcessAction::class, [$this->passable->getProcess(), $this->passable]),
            ]);
    }

    public function updateTaskAction(string $taskClass, ProcessStatesEnum $state): ?PendingDispatch
    {
        if (! $this->isEloquentProcess) {
            return null;
        }

        $currentTask = $this->passable->getProcess()->taskModel($taskClass);

        if ($state === ProcessStatesEnum::WAITING) {
            return $this->pauseProcessAction
                ->onQueue()
                ->execute($this->passable->getProcess(), $this->passable)
                ->chain([
                    new ActionJob(UpdateTaskStateAction::class, [$currentTask, $state]),
                    new ActionJob(LogProcessAction::class, [$this->passable->getProcess(), $this->passable, $currentTask]),
                ]);
        }

        return $this->updateEloquentTaskStateAction
            ->onQueue()
            ->execute(
                task: $currentTask,
                state: $state
            )->chain([
                new ActionJob(LogProcessAction::class, [$this->passable->getProcess(), $this->passable, $currentTask]),
            ]);
    }
}
