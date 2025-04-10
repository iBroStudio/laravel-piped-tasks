<?php

namespace IBroStudio\PipedTasks\Actions;

use Closure;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\ProcessContract;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Exceptions\PauseProcessException;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\PayloadAbstract;
use Lorisleiva\Actions\Concerns\AsAction;

final class RunProcess
{
    use AsAction;

    public function handle(
        ProcessContract|ProcessModelContract $process,
        Payload $payload
    ): Payload {
        return $process->run($payload);
    }

    public function asTask(Process $process, PayloadAbstract $payload, Closure $next): mixed
    {
        $parentProcess = $payload->process;

        UpdateTaskState::run(
            task: $parentProcess->taskModel(get_class($process)),
            state: ProcessStatesEnum::WAITING
        );

        PauseProcess::run(
            process: $parentProcess,
            payload: $payload
        );

        $payload = $process::makePayload($payload->toCollection());

        $process = $process::makeProcess(
            payload: $payload,
            parent_process: $parentProcess
        );

        // static::dispatch($process, $payload);

        return new PauseProcessException($process, $payload);
    }
}
