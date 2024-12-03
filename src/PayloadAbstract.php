<?php

namespace IBroStudio\PipedTasks;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\ProcessContract;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Transformers\DataTransformer;
use IBroStudio\PipedTasks\Transformers\TransformerContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use ReflectionAttribute;
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
            collect($property->getAttributes())->each(function (ReflectionAttribute $attribute) use ($property) {
               if (is_subclass_of($attribute->getName(), TransformerContract::class)) {
                   $this->{$property->getName()} = new ($attribute->getName())(
                       class: $attribute->getArguments()[0],
                       value: $this->{$property->getName()}
                   )->transform();
               }
            });
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
