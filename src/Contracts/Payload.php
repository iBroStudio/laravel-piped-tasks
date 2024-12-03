<?php

namespace IBroStudio\PipedTasks\Contracts;

use IBroStudio\PipedTasks\Models\Process;

interface Payload
{
    public Process|ProcessContract|ProcessModelContract $process { get; }

    public function toArray(): array;
}
