<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Actions\LogProcess;
use IBroStudio\PipedTasks\Actions\PauseProcess;
use IBroStudio\PipedTasks\Actions\UpdateProcessState;
use IBroStudio\PipedTasks\Actions\UpdateTaskState;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;

trait HasActions
{
    protected bool $isEloquentProcess = false;

    public function isEloquentProcess(): static
    {
        $this->isEloquentProcess = true;

        return $this;
    }

    public function updateProcessAction(ProcessStatesEnum $state): void
    {
        $process = $this->passable->process;

        if ($this->isEloquentProcess
            && $process->state !== ProcessStatesEnum::RESUME
        ) {
            UpdateProcessState::run(
                process: $process,
                state: $state
            );

            LogProcess::dispatch(
                process: $process,
                payload: $this->passable
            );
        }
    }

    public function updateTaskAction(string $taskClass, ProcessStatesEnum $state): void
    {
        if ($this->isEloquentProcess) {
            $process = $this->passable->process;
            $currentTask = $process->taskModel($taskClass);

            if ($state === ProcessStatesEnum::WAITING) {
                PauseProcess::run(
                    process: $process,
                    payload: $this->passable
                );
            }

            UpdateTaskState::run(
                task: $currentTask,
                state: $state
            );

            LogProcess::dispatch(
                process: $process,
                payload: $this->passable,
                task: $currentTask
            );
        }
    }
}
