<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Data\ProcessLogData;
use IBroStudio\PipedTasks\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasLogData
{
    protected Collection $parsedProcessClass;

    protected Collection $parsedTaskClass;

    public function logData(Payload $payload): ProcessLogData
    {
        $this->parsedProcessClass = $this->parseClass($this->class);

        return new ProcessLogData(
            logName: $this->logName($payload),
            performedOn: $this->processable ?? $this->parent ?? $this,
            event: $this->state,
            description: $this->logDescription(),
            properties: $payload->toArray(),
        );
    }

    public function logTaskData(Task $task, Payload $payload): ProcessLogData
    {
        $this->parsedProcessClass = $this->parseClass($this->class);
        $this->parsedTaskClass = $this->parseClass($task->class);

        return new ProcessLogData(
            logName: $this->logName($payload),
            performedOn: $this->processable ?? $this->parent ?? $this,
            event: $this->state,
            description: $this->logDescription($task),
            properties: $payload->toArray(),
        );
    }

    protected function logName(Payload $payload): string
    {
        if (! is_null($this->parent)) {
            return $this->parent->logData($payload)->logName;
        }

        return is_null($this->class::$logName)
            ? $this->parsedProcessClass->implode('-')
            : Str::slug($this->class::$logName);
    }

    protected function logDescription(?Task $task = null): string
    {
        return (
            $task instanceof Task ?
                $this->parsedTaskClass->push($task->state->getLabel())
                : $this->parsedProcessClass->push($this->state->getLabel())
        )
            ->implode(' ');
    }

    protected function parseClass(string $string): Collection
    {
        return Str::of($string)
            ->classBasename()
            ->chopEnd('Process')
            ->chopEnd('Task')
            ->split('/(?<=[a-z])(?=[A-Z])|(?=[A-Z][a-z])/', -1, PREG_SPLIT_NO_EMPTY)
            ->map(function (string $item) {
                return mb_lcfirst($item);
            });
    }
}
