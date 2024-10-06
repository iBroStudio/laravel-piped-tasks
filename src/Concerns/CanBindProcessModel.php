<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\StatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Support\Arr;

trait CanBindProcessModel
{
    protected Process $process;

    public function bindProcessModel(Process $process): static
    {
        $this->process = $process;

        return $this;
    }

    public function makeAndBindProcessModel(Payload $payload, array $tasks): static
    {
        $this->process = Process::create([
            'class' => get_class($this),
            'payload' => serialize($payload),
            'state' => StatesEnum::PENDING,
        ]);

        $this->process->tasks()->createMany(
            Arr::map($tasks, function (string $task) {
                return [
                    'class' => $task,
                    'state' => StatesEnum::PENDING,
                ];
            })
        );

        return $this;
    }

    public function getProcessModel(): Process
    {
        return $this->process;
    }
}
