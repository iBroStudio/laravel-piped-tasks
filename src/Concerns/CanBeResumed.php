<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Actions\LogProcess;
use IBroStudio\PipedTasks\Actions\RunProcess;
use IBroStudio\PipedTasks\Actions\UpdateTaskState;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use Illuminate\Support\Facades\URL;

trait CanBeResumed
{
    public function resumeUrl(): string
    {
        return URL::signedRoute('piped-tasks-process', [
            'process_id' => $this,
            'batch' => '',
        ]);
    }

    public static function resume(int $process_id, ?Payload $payload = null): Payload
    {
        $process = self::whereId($process_id)
            ->whereState(ProcessStatesEnum::WAITING)
            ->firstOrFail();

        if (! is_null($payload)) {
            $process->update(['payload' => serialize($payload)]);
            $process->refresh();
        }

        $process->payload->setProcess($process);

        UpdateTaskState::run(
            task: $waitingTask = $process->waitingTask(),
            state: ProcessStatesEnum::RESUME
        );

        LogProcess::dispatch(
            process: $process,
            payload: $process->payload,
            task: $waitingTask
        );

        return RunProcess::run(
            process: $process,
            payload: $process->payload,
        );
    }
}
