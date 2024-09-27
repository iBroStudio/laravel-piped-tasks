<?php

namespace IBroStudio\PipedTasks;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use MichaelRubel\EnhancedPipeline\Pipeline;

abstract class Process
{
    protected array $tasks = [];

    protected bool $withEvents = false;

    protected bool $withTransaction = false;

    protected ?Closure $onFailure = null;

    protected ?Closure $onSuccess = null;

    public function __invoke(Payload $payload, Closure $next): mixed
    {
        $this->run($payload);

        return $next($payload);
    }

    public function run(Payload $payload): mixed
    {
        $pipeline = Pipeline::make();

        if ($this->withEvents) {
            $pipeline->withEvents();
        }

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

    public function onSuccess(): static
    {
        return $this;
    }

    public function onFailure(): static
    {
        return $this;
    }

    public function withEvents(): static
    {
        $this->withEvents = true;

        return $this;
    }

    public function withTransaction(): static
    {
        $this->withTransaction = true;

        return $this;
    }

    protected function tasks(): array
    {
        if (Arr::exists(Config::get('piped-tasks.tasks'), static::class)) {
            $this->tasks = array_merge(
                $this->tasks,
                Arr::wrap(Config::get('piped-tasks.tasks.'.static::class.'.append'))
            );
            $this->tasks = array_merge(
                Arr::wrap(Config::get('piped-tasks.tasks.'.static::class.'.prepend')),
                $this->tasks
            );
        }

        return $this->tasks;
    }
}
