<?php

namespace IBroStudio\PipedTasks\Concerns;

use Closure;
use IBroStudio\PipedTasks\Actions\RunProcess;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\Processable;
use IBroStudio\PipedTasks\ProcessPipeline;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use RuntimeException;

trait IsProcess
{
    protected array $tasks = [];

    protected bool $withEvents = false;

    protected bool $isEloquentProcess = false;

    protected bool $withTransaction = false;

    protected ?Closure $onFailure = null;

    protected ?Closure $onSuccess = null;

    public static ?string $logName = null;

    /**
     * @return ($async is true ? PendingDispatch : Payload)
     */
    public static function process(
        array|Collection $payload_properties = [],
        ?Processable $processable = null,
        bool $async = false): Payload|PendingDispatch
    {
        $payload = static::makePayload($payload_properties);

        $process = static::makeProcess(
            payload: $payload,
            processable: $processable
        );

        if ($async) {
            return RunProcess::dispatch(
                process: $process,
                payload: $payload,
            );
        }

        return RunProcess::run(
            process: $process,
            payload: $payload,
        );
    }

    public static function makeProcess(Payload $payload, ?Processable $processable = null): static
    {
        // @phpstan-ignore-next-line
        return new static;
    }

    public static function makePayload(array|Collection $payload_properties = []): Payload
    {
        $payloadClass = static::guessPayloadClass(get_called_class());

        return new $payloadClass(...$payload_properties);
    }

    public function run(Payload $payload): Payload
    {
        $payload->process = $this;

        $pipeline = ProcessPipeline::make();

        if ($this->isEloquentProcess) {
            $pipeline->isEloquentProcess();
        }

        if ($this->withEvents) {
            $pipeline->withEvents();
        }

        if ($this->withTransaction) {
            $pipeline->withTransaction();
        }

        $pipeline
            ->send($payload)
            ->through($this->getTasks());

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

    public function isEloquentProcess(): static
    {
        $this->isEloquentProcess = true;

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

    protected function getTasks(): array
    {
        if (Arr::exists(Config::get('piped-tasks.tasks', []), static::class)) {
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

    protected static function guessPayloadClass(string $processClass): string
    {
        $process_class = get_called_class();

        $payloadClass = Str::of($processClass)
            ->beforeLast('\\')
            ->append('\Payloads\\')
            ->append(
                Str::of($process_class)
                    ->afterLast('\\')
                    ->before('Process')
                    ->append('Payload')
            )
            ->toString();

        if (! class_exists($payloadClass)) {
            throw new RuntimeException("Payload class '{$payloadClass}' not found.");
        }

        return $payloadClass;
    }
}
