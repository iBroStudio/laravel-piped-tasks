<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\UseProcessModel;
use IBroStudio\PipedTasks\Enums\StatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\ResumablePipeline;

trait ResumableProcess
{
    use CanBindProcessModel;

    protected Process $process;

    public function run(Payload|UseProcessModel $payload): mixed
    {
        $pipeline = ResumablePipeline::make();

        if (! isset($this->process)) {
            $this->makeAndBindProcessModel($payload, $this->tasks());
        }

        $pipeline
            ->bindProcessModel($this->process)
            ->withEvents();

        $payload->bindProcessModel($this->process);

        if ($this->withTransaction) {
            $pipeline->withTransaction();
        }

        $pipeline
            ->send($payload)
            ->through($this->tasks());

        if (! is_null($this->onFailure)) {
            $pipeline->onFailure($this->onFailure);
        }

        $this
            ->onSuccess()
            ->onFailure();

        if (! is_null($this->onSuccess)) {
            return $pipeline->then($this->onSuccess);
        }

        return $pipeline->thenReturn();
    }

    public static function resume(Process $process): mixed
    {
        return (new static)
            ->bindProcessModel($process)
            ->run(unserialize($process->payload));
    }

    protected function tasks(): array
    {
        if (isset($this->process)) {
            return $this->process->tasks()
                ->whereState(StatesEnum::PENDING)
                ->pluck('class')
                ->toArray();
        }

        return parent::tasks();
    }
}
