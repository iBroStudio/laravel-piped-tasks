<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Contracts\Processable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait HasProcessable
{
    public function processable(): MorphTo
    {
        return $this->morphTo();
    }

    public function addProcessable(Processable $processable): bool
    {
        return $this->processable()
            ->associate($processable)
            ->save();
    }
}
