<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Bus\PendingDispatch;

trait IsProcessable
{
    public function processes(): MorphMany
    {
        return $this->morphMany(Process::class, 'processable');
    }

    /**
     * @return ($async is true ? PendingDispatch : Payload)
     */
    public function process(
        string $processClass,
        array $payload_properties = [],
        bool $async = false): Payload|PendingDispatch
    {
        return $processClass::process($payload_properties, $this, $async);
    }

    /**
     * @return ($async is true ? PendingDispatch : Payload)
     */
    public static function callProcess(
        string $processClass,
        array $payload_properties = [],
        bool $async = false): Payload|PendingDispatch
    {
        return (new static)->process($processClass, $payload_properties, $async);
    }
}
