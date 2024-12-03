<?php

namespace IBroStudio\PipedTasks;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\ProcessContract;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Transformers\DataTransformer;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionProperty;

abstract class PayloadAbstract implements Arrayable, Payload
{
    use SerializesModels;

    public function __construct()
    {
        collect(
            new \ReflectionClass($this)->getProperties()
        )->each(function (ReflectionProperty $property) {
            if ($transformer = $property->getAttributes(DataTransformer::class)) {
                $this->{$property->getName()} = new DataTransformer(
                    class: $transformer[0]->getArguments()[0],
                    value: $this->{$property->getName()})->transform();
            }
        });
    }

    public Process|ProcessContract|ProcessModelContract $process {
        get => $this->process instanceof ProcessContract ?
            $this->process : $this->process->refresh();
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
