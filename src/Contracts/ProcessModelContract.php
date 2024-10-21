<?php

namespace IBroStudio\PipedTasks\Contracts;

use IBroStudio\PipedTasks\Data\ProcessLogData;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Task;
use Illuminate\Foundation\Bus\PendingDispatch;

/**
 * @property int $id
 * @property string $class
 * @property Payload $payload
 * @property ProcessStatesEnum $state
 * @property string $log_batch_uuid
 */
interface ProcessModelContract
{
    public static function process(
        array $payload_properties = [],
        ?Processable $processable = null,
        bool $async = false): Payload|PendingDispatch;

    public function run(Payload $payload): Payload;

    public function onSuccess(): static;

    public function onFailure(): static;

    public function withEvents(): static;

    public function withTransaction(): static;

    public function addProcessable(Processable $processable): bool;

    public function resumeUrl(): string;

    public function logData(Payload $payload): ProcessLogData;

    public function logTaskData(Task $task, Payload $payload): ProcessLogData;
}
