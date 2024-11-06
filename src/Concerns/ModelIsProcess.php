<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\Processable;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use Illuminate\Support\Arr;

/**
 * @property \Illuminate\Database\Eloquent\Model $parent_process
 */
trait ModelIsProcess
{
    use IsProcess { IsProcess::getTasks as IsProcessGetTasks; }

    public function __construct(array $attributes = [])
    {
        $this
            ->isEloquentProcess()
            ->withEvents();

        parent::__construct($attributes);
    }

    public static function makeProcess(
        Payload $payload,
        ?Processable $processable = null,
        ?ProcessModelContract $parent_process = null): self
    {
        $process = static::create([
            'class' => get_called_class(),
            'payload' => serialize($payload),
            'state' => ProcessStatesEnum::PENDING,
            // @phpstan-ignore-next-line
            'parent_process_id' => $parent_process?->id,
        ]);

        $process->taskModels()->createMany(
            Arr::map($process->getTasks(), function (string $task) {
                return [
                    'class' => $task,
                    'state' => ProcessStatesEnum::PENDING,
                ];
            })
        );

        if (! is_null($processable)) {
            $process->addProcessable($processable);
        }

        return $process;
    }

    protected function getTasks(): array
    {
        if ($this->taskModels()->count()) {
            return $this->taskModels()
                ->whereState(ProcessStatesEnum::PENDING)
                ->pluck('class')
                ->toArray();
        }

        return $this->IsProcessGetTasks();
    }
}
