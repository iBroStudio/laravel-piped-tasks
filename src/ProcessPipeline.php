<?php

namespace IBroStudio\PipedTasks;

use Closure;
use IBroStudio\PipedTasks\Actions\RunProcess;
use IBroStudio\PipedTasks\Concerns\HasActions;
use IBroStudio\PipedTasks\Concerns\HasDatabaseTransactions;
use IBroStudio\PipedTasks\Concerns\HasEvents;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\Conditionable;
use RuntimeException;
use Throwable;

class ProcessPipeline
{
    use Conditionable;
    use HasActions;
    use HasDatabaseTransactions;
    use HasEvents;

    protected mixed $passable;

    protected string $method = 'asTask';

    protected ?Closure $onFailure = null;

    protected array $pipes = [];

    public function __construct(protected ?Container $container = null) {}

    public static function make(?Container $container = null): static
    {
        if (! $container) {
            $container = \Illuminate\Container\Container::getInstance();
        }

        return $container->make(static::class);
    }

    public function send(mixed $passable): static
    {
        $this->passable = $passable;

        return $this;
    }

    public function through(mixed $pipes): static
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    public function pipe(mixed $pipes): static
    {
        array_push($this->pipes, ...(is_array($pipes) ? $pipes : func_get_args()));

        return $this;
    }

    public function via(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function then(Closure $destination): mixed
    {
        try {
            $this->updateProcessAction(ProcessStatesEnum::STARTED);

            $this->fireEvent(Events\PipelineStarted::class,
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

            $this->fireEvent(Events\PipelineFinished::class,
                $destination,
                $this->passable,
                $this->pipes(),
                $this->useTransaction,
                $result,
            );

            if (($process = $this->passable->process) instanceof Process
                && $parent_process_id = $process->refresh()->parent_process_id
            ) {
                $parentProcess = Process::find($parent_process_id);
                $payload = $parentProcess->class::makePayload($this->passable->toCollection());
                $payload->process = $parentProcess;
                $parentProcess->class::resume($parentProcess->id, $payload);
            }

            return $result;

        } catch (Exceptions\PauseProcessException $e) {
            $this->commitTransaction();

            return $this->passable;

        } catch (Throwable $e) {
            $this->rollbackTransaction();

            if ($e instanceof Exceptions\AbortProcessException) {
                $this->updateProcessAction(ProcessStatesEnum::ABORTED);

                return $this->passable;
            } else {
                $this->updateProcessAction(ProcessStatesEnum::FAILED);
            }

            if ($this->onFailure) {
                return ($this->onFailure)($this->passable, $e);
            }

            return $this->handleException($this->passable, $e);
        }
    }

    public function thenReturn(): mixed
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                $this->updateTaskAction($pipe, ProcessStatesEnum::STARTED);

                $this->fireEvent(Events\PipeExecutionStarted::class, $pipe, $passable);

                if (is_callable($pipe)) {
                    // If the pipe is a callable, then we will call it directly, but otherwise we
                    // will resolve the pipes out of the dependency container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    $result = $pipe($passable, $stack);

                    $this->updateTaskAction($pipe, ProcessStatesEnum::COMPLETED);

                    $this->fireEvent(Events\PipeExecutionFinished::class, $pipe, $passable);

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

                $carry = match (true) {
                    is_a($pipe, Process::class) => RunProcess::make()->asTask($pipe, $passable, $stack),
                    method_exists($pipe, $this->method) => $pipe->{$this->method}(...$parameters),
                    default => $pipe(...$parameters),
                };

                if ($carry instanceof Exceptions\PauseProcessException) {

                    if (! in_array($passable->process->state, [ProcessStatesEnum::COMPLETED, ProcessStatesEnum::WAITING])) {
                        $this->updateTaskAction(get_class($pipe), ProcessStatesEnum::WAITING);

                        $this->fireEvent(Events\PipeExecutionPaused::class, $pipe, $passable);
                    }

                    throw $carry;
                }

                if ($carry instanceof Exceptions\AbortProcessException) {
                    $this->updateTaskAction(get_class($pipe), ProcessStatesEnum::ABORTED);
                    throw $carry;
                }

                if ($carry instanceof Exceptions\SkipTaskException) {
                    $this->updateTaskAction(get_class($pipe), ProcessStatesEnum::SKIPPED);
                    $carry = $carry->next;
                } else {
                    $this->updateTaskAction(get_class($pipe), ProcessStatesEnum::COMPLETED);
                }

                $this->fireEvent(Events\PipeExecutionFinished::class, $pipe, $passable);

                return $this->handleCarry($carry);
            };
        };
    }

    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    protected function pipes(): array
    {
        return $this->pipes;
    }

    protected function getContainer(): Container
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }

    public function setContainer(Container $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function onFailure(Closure $callback): static
    {
        $this->onFailure = $callback;

        return $this;
    }

    public function run(string $pipe, mixed $data = true): mixed
    {
        return $this
            ->send($data)
            ->through([$pipe])
            ->thenReturn();
    }

    protected function handleCarry(mixed $carry): mixed
    {
        return $carry;
    }

    protected function handleException(mixed $passable, Throwable $e): mixed
    {
        throw $e;
    }
}
