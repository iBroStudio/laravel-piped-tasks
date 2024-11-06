<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Actions\LogProcessAction;
use IBroStudio\PipedTasks\Actions\RunProcessAction;
use IBroStudio\PipedTasks\Actions\UpdateTaskStateAction;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\URL;
use Spatie\QueueableAction\ActionJob;

trait CanBeResumed
{
    public function resumeUrl(): string
    {
        return URL::signedRoute('piped-tasks-process', [
            'process_id' => $this,
            'batch' => '',
        ]);
    }

    public static function resume(int $process_id, ?Payload $payload = null): ?PendingDispatch
    {
        $process = self::whereId($process_id)
            ->whereState(ProcessStatesEnum::WAITING)
            ->firstOrFail();

        if (! is_null($payload)) {
            $process->update(['payload' => serialize($payload)]);
            $process->refresh();
        }

        $process->payload->setProcess($process);

        return (new UpdateTaskStateAction)
            ->onQueue()
            ->execute(
                task: $waitingTask = $process->waitingTask(),
                state: ProcessStatesEnum::RESUME
            )->chain([
                new ActionJob(LogProcessAction::class, [$process, $process->payload, $waitingTask]),
                new ActionJob(RunProcessAction::class, [$process, $process->payload]),
            ]);
    }
}
