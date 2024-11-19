<?php

namespace IBroStudio\PipedTasks\Actions;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Activitylog\Facades\LogBatch;

final class LogProcess
{
    use AsAction;

    public function handle(
        Process $process,
        Payload $payload,
        ?Task $task = null): void
    {
        if (config('piped-tasks.log_processes')) {

            if ($task instanceof Task) {
                $processEventLogData = $process->logTaskData($task, $payload);

                if ($process->state === ProcessStatesEnum::RESUME) {
                    LogBatch::startBatch();
                    LogBatch::setBatch($process->log_batch_uuid);
                }
            } else {
                $processEventLogData = $process->logData($payload);

                if ($process->state === ProcessStatesEnum::STARTED) {
                    LogBatch::startBatch();
                    $process->update([
                        'log_batch_uuid' => LogBatch::getUuid(),
                    ]);
                }
            }

            activity($processEventLogData->logName)
                //->causedBy($userModel) // agent model or user model
                ->performedOn($processEventLogData->performedOn)
                ->event($processEventLogData->event->getLabel())
                ->withProperties($processEventLogData->properties)
                ->log($processEventLogData->description);

            if (
                (! $task instanceof Task && $process->state === ProcessStatesEnum::COMPLETED)
                || ($task instanceof Task && $task->state === ProcessStatesEnum::WAITING)
            ) {
                LogBatch::endBatch();
                $process->ensureLogPerformedOn();
            }
        }
    }
}
