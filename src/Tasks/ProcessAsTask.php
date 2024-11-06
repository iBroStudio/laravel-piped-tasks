<?php

namespace IBroStudio\PipedTasks\Tasks;

use Closure;
use IBroStudio\PipedTasks\Actions\PauseProcessAction;
use IBroStudio\PipedTasks\Actions\RunProcessAction;
use IBroStudio\PipedTasks\Actions\UpdateProcessStateAction;
use IBroStudio\PipedTasks\Actions\UpdateTaskStateAction;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\PauseProcess;
use IBroStudio\PipedTasks\PayloadAbstract;

/**
 * @property Process $parentProcess
 */
class ProcessAsTask
{
    public function __construct(
        protected UpdateProcessStateAction $updateEloquentProcessStateAction,
        protected UpdateTaskStateAction $updateEloquentTaskStateAction,
        protected RunProcessAction $runProcessAction,
        protected PauseProcessAction $pauseProcessAction,
    ) {}

    public function handle(Process $process, PayloadAbstract $payload, Closure $next): mixed
    {
        $parentProcess = $payload->getProcess();

        $this->updateEloquentTaskStateAction
            ->execute(
                // @phpstan-ignore-next-line
                task: $parentProcess->taskModel(get_class($process)),
                state: ProcessStatesEnum::WAITING
            );

        // @phpstan-ignore-next-line
        $this->pauseProcessAction->execute($parentProcess, $payload);

        $payload = $process::makePayload($payload->toCollection());

        $process = $process::makeProcess(
            payload: $payload,
            parent_process: $parentProcess
        );

        $this->runProcessAction
            ->onQueue()
            ->execute(
                process: $process,
                payload: $payload,
            );

        return new PauseProcess;
    }
}
