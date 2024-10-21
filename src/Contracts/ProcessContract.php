<?php

namespace IBroStudio\PipedTasks\Contracts;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use Illuminate\Foundation\Bus\PendingDispatch;

/**
 * @property int $id
 * @property string $class
 * @property string $payload
 * @property ProcessStatesEnum $state
 * @property string $log_batch_uuid
 */
interface ProcessContract
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
}
