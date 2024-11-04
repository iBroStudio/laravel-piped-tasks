<?php

namespace IBroStudio\PipedTasks;

use Closure;
use IBroStudio\PipedTasks\Actions\PauseProcessAction;
use IBroStudio\PipedTasks\Actions\UpdateProcessStateAction;
use IBroStudio\PipedTasks\Actions\UpdateTaskStateAction;
use IBroStudio\PipedTasks\Concerns\HasActions;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use IBroStudio\PipedTasks\Contracts\ProcessContract;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Events\PipeExecutionPaused;
use Illuminate\Container\Container as ContainerConcrete;
use Illuminate\Contracts\Container\Container;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionFinished;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionStarted;
use MichaelRubel\EnhancedPipeline\Events\PipelineFinished;
use MichaelRubel\EnhancedPipeline\Events\PipelineStarted;
use MichaelRubel\EnhancedPipeline\Pipeline;
use Throwable;
use IBroStudio\TestSupport\Processes\Tasks\LongFakeActionTask;

class ProcessPipeline extends Pipeline
{
    use HasActions;

    public function __construct(
        protected UpdateProcessStateAction $updateEloquentProcessStateAction,
        protected UpdateTaskStateAction $updateEloquentTaskStateAction,
        protected PauseProcessAction $pauseProcessAction,
        ?Container $container = null
    ) {
        parent::__construct($container);
    }

    public static function make(?Container $container = null): ProcessPipeline
    {
        if (! $container) {
            $container = ContainerConcrete::getInstance();
        }

        return $container->make(static::class);
    }

    public function then(Closure $destination)
    {
        try {

            $this->updateProcessAction(ProcessStatesEnum::STARTED);

            $this->fireEvent(PipelineStarted::class,
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

            $this->updateProcessAction(ProcessStatesEnum::COMPLETED);

            dd($this->passable->getProcess()->refresh());
            $this->fireEvent(PipelineFinished::class,
                $destination,
                $this->passable,
                $this->pipes(),
                $this->useTransaction,
                $result,
            );

            return $result;
        } catch (PauseProcess $e) {
            $this->commitTransaction();

            return $this->passable;
        } catch (Throwable $e) {
            $this->rollbackTransaction();

            if ($this->onFailure) {
                return ($this->onFailure)($this->passable, $e);
            }

            return $this->handleException($this->passable, $e);
        }
    }

    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {

                $this->updateTaskAction($pipe, ProcessStatesEnum::STARTED);

                $this->fireEvent(PipeExecutionStarted::class, $pipe, $passable);

                if (is_callable($pipe)) {
                    // If the pipe is a callable, then we will call it directly, but otherwise we
                    // will resolve the pipes out of the dependency container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    $result = $pipe($passable, $stack);

                    $this->updateTaskAction($pipe, ProcessStatesEnum::COMPLETED);

                    $this->fireEvent(PipeExecutionFinished::class, $pipe, $passable);

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

                $carry = match(true) {
                    is_a($pipe, ProcessModelContract::class) => $pipe::process($passable->toCollection()),
                    is_a($pipe, ProcessContract::class) => $pipe::process($passable->toCollection()),
                    method_exists($pipe, $this->method) => dd('method_exists'),//$pipe->{$this->method}(...$parameters),
                    default => ! $pipe instanceof LongFakeActionTask ? dd($pipe, $stack) : $pipe(...$parameters),
                };

                /*
                $carry = method_exists($pipe, $this->method)
                    ? $pipe->{$this->method}(...$parameters)
                    : $pipe(...$parameters);
                */

                if ($carry instanceof PauseProcess) {
                    $this->updateTaskAction(get_class($pipe), ProcessStatesEnum::WAITING);

                    $this->fireEvent(PipeExecutionPaused::class, $pipe, $passable);

                    throw $carry;
                }

                $this->updateTaskAction(get_class($pipe), ProcessStatesEnum::COMPLETED);

                $this->fireEvent(PipeExecutionFinished::class, $pipe, $passable);

                return $this->handleCarry($carry);
            };
        };
    }
}
