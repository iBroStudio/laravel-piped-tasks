<?php

namespace IBroStudio\PipedTasks;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\ProcessContract;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionProperty;

/**
 * @property ProcessContract|ProcessModelContract $process
 */
abstract class PayloadAbstract implements Arrayable, Payload
{
    use SerializesModels;

    protected ProcessContract|ProcessModelContract|null $process = null;

    public function setProcess(ProcessContract|ProcessModelContract $process): void
    {
        $this->process = $process;
    }

    public function getProcess(): ProcessContract|ProcessModelContract|null
    {
        return $this->process;
    }

    public function toCollection(): Collection
    {
        $reflection = new ReflectionClass($this);

        return collect($reflection->getProperties())
            ->filter(function (ReflectionProperty $property) {
                return $property->getName() !== 'process';
            })
            ->mapWithKeys(function (ReflectionProperty $property) {
                return [$property->getName() => $property->getValue($this)];
            });
    }

    public function toArray(): array
    {
        return $this->toCollection()->toArray();
    }
}
