<?php

namespace IBroStudio\PipedTasks;

use Closure;
use IBroStudio\PipedTasks\Concerns\CanBindProcessModel;
use IBroStudio\PipedTasks\Contracts\UseProcessModel;
use IBroStudio\PipedTasks\Enums\StatesEnum;
use IBroStudio\PipedTasks\Events\ResumablePipeExecutionFinished;
use IBroStudio\PipedTasks\Events\ResumablePipeExecutionStarted;
use IBroStudio\PipedTasks\Events\ResumablePipelineFinished;
use IBroStudio\PipedTasks\Events\ResumablePipelineStarted;
use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Container\Container as ContainerConcrete;
use Illuminate\Contracts\Container\Container;
use MichaelRubel\EnhancedPipeline\Pipeline;
use Throwable;

class ResumablePipeline extends Pipeline implements UseProcessModel
{
    use CanBindProcessModel;

    protected Process $process;

    public static function make(?Container $container = null): ResumablePipeline
    {
        if (! $container) {
            $container = ContainerConcrete::getInstance();
        }

        return $container->make(static::class);
    }

    public function then(Closure $destination): mixed
    {
        try {
            $this->process->update(['state' => StatesEnum::PROCESSING]);

            $this->fireEvent(ResumablePipelineStarted::class,
                $this->process,
                $destination,
                $this->passable,
                $this->pipes(),
                $this->useTransaction,
            );

            $this->beginTransaction();

            $pipeline = array_reduce(
                array_reverse($this->pipes()),
                $this->carry(),
                $this->prepareDestination($destination)
            );

            $result = $pipeline($this->passable);

            $this->commitTransaction();

            $this->process->update(['state' => StatesEnum::COMPLETED]);

            $this->fireEvent(ResumablePipelineFinished::class,
                $this->process,
                $destination,
                $this->passable,
                $this->pipes(),
                $this->useTransaction,
                $result,
            );

            return $result;
        } catch (PauseProcess $e) {

            $this->commitTransaction();

            $this->process->update([
                'payload' => serialize($this->passable),
                'state' => StatesEnum::PENDING,
            ]);

            return $this->passable;
        } catch (Throwable $e) {
            $this->rollbackTransaction();

            $this->process->update(['state' => StatesEnum::FAILED]);

            $this->process->currentTask()->update(['state' => StatesEnum::FAILED]);

            if ($this->onFailure) {
                return ($this->onFailure)($this->passable, $e);
            }

            return $this->handleException($this->passable, $e);
        }
    }

    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                $currentTask = $this->process->task($pipe);
                $currentTask->update(['state' => StatesEnum::PROCESSING]);

                $this->fireEvent(ResumablePipeExecutionStarted::class, $this->process, $pipe, $passable);

                if (is_callable($pipe)) {
                    // If the pipe is a callable, then we will call it directly, but otherwise we
                    // will resolve the pipes out of the dependency container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    $result = $pipe($passable, $stack);

                    $currentTask->update(['state' => StatesEnum::COMPLETED]);

                    $this->fireEvent(ResumablePipeExecutionFinished::class, $this->process, $pipe, $passable);

                    return $result;
                } elseif (! is_object($pipe)) {

                    [$name, $parameters] = $this->parsePipeString($pipe);

                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    $pipe = $this->getContainer()->make($name);

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    // If the pipe is already an object we'll just make a callable and pass it to
                    // the pipe as-is. There is no need to do any extra parsing and formatting
                    // since the object we're given was already a fully instantiated object.
                    $parameters = [$passable, $stack];
                }

                $carry = method_exists($pipe, $this->method)
                    ? $pipe->{$this->method}(...$parameters)
                    : $pipe(...$parameters);

                $currentTask->update(['state' => StatesEnum::COMPLETED]);

                $this->fireEvent(ResumablePipeExecutionFinished::class, $this->process, $pipe, $passable);

                if ($carry instanceof PauseProcess) {
                    throw $carry;
                }

                return $this->handleCarry($carry);
            };
        };
    }
}
